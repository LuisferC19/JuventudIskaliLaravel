<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\DonacionWorkflowService;
use App\Services\EntregaWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseTwoInventoryWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        $this->crearEsquemaAislado();
        DB::table('tipos_bien')->insert([
            'id_tipo_bien' => 1,
            'nombre' => 'Despensas',
            'unidad_medida' => 'paquete',
            'activo' => true,
        ]);
    }

    public function test_verificar_donacion_de_especie_registra_entrada_y_estado(): void
    {
        DB::table('donaciones')->insert([
            'id_donacion' => 1,
            'estado' => 'recibida',
        ]);
        DB::table('donaciones_especie')->insert([
            'id_donacion' => 1,
            'id_tipo_bien' => 1,
            'cantidad' => 12,
        ]);

        app(DonacionWorkflowService::class)->cambiarEstado(1, 'verificada', 1);

        $this->assertSame('verificada', DB::table('donaciones')->where('id_donacion', 1)->value('estado'));
        $this->assertSame(12.0, (float) DB::table('inventario')->value('cantidad_total_recibida'));
        $this->assertSame(12.0, (float) DB::table('inventario')->value('cantidad_disponible'));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo_movimiento' => 'entrada',
            'tipo_referencia' => 'donacion',
            'id_referencia' => 1,
        ]);
    }

    public function test_stock_insuficiente_revierte_estado_y_movimientos_de_entrega(): void
    {
        $this->crearDonacionVerificada();
        DB::table('inventario')->insert([
            'id_tipo_bien' => 1,
            'cantidad_total_recibida' => 2,
            'cantidad_total_entregada' => 0,
            'id_usuario_actualizador' => 1,
        ]);
        DB::table('entregas')->insert([
            'id_entrega' => 1,
            'id_donacion' => 1,
            'cantidad_entregada' => 3,
            'estado' => 'programada',
        ]);

        $usuario = new Usuario;
        $usuario->forceFill(['id_usuario' => 1]);
        $this->withoutMiddleware()
            ->actingAs($usuario)
            ->from('/entregas/1')
            ->post(route('entregas.cambiarEstado', 1), ['estado' => 'completada'])
            ->assertRedirect('/entregas/1')
            ->assertSessionHasErrors([
                'estado' => 'No hay suficiente inventario para completar esta entrega.',
            ]);

        $this->assertSame('programada', DB::table('entregas')->where('id_entrega', 1)->value('estado'));
        $this->assertSame(0, DB::table('movimientos_inventario')->count());
        $this->assertSame(0.0, (float) DB::table('inventario')->value('cantidad_total_entregada'));
    }

    public function test_cancelar_entrega_completada_registra_entrada_compensatoria(): void
    {
        $this->crearDonacionVerificada();
        DB::table('inventario')->insert([
            'id_tipo_bien' => 1,
            'cantidad_total_recibida' => 10,
            'cantidad_total_entregada' => 2,
            'id_usuario_actualizador' => 1,
        ]);
        DB::table('entregas')->insert([
            'id_entrega' => 1,
            'id_donacion' => 1,
            'cantidad_entregada' => 2,
            'estado' => 'completada',
        ]);

        app(EntregaWorkflowService::class)->cambiarEstado(1, 'cancelada', 1);

        $this->assertSame('cancelada', DB::table('entregas')->where('id_entrega', 1)->value('estado'));
        $this->assertSame(12.0, (float) DB::table('inventario')->value('cantidad_total_recibida'));
        $this->assertSame(2.0, (float) DB::table('inventario')->value('cantidad_total_entregada'));
        $this->assertSame(10.0, (float) DB::table('inventario')->value('cantidad_disponible'));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo_movimiento' => 'entrada',
            'tipo_referencia' => 'entrega',
            'id_referencia' => 1,
            'motivo' => 'Reversión por cancelación de entrega completada',
        ]);
    }

    private function crearDonacionVerificada(): void
    {
        DB::table('donaciones')->insert([
            'id_donacion' => 1,
            'estado' => 'verificada',
        ]);
        DB::table('donaciones_especie')->insert([
            'id_donacion' => 1,
            'id_tipo_bien' => 1,
            'cantidad' => 10,
        ]);
    }

    private function crearEsquemaAislado(): void
    {
        Schema::create('tipos_bien', function ($table): void {
            $table->increments('id_tipo_bien');
            $table->string('nombre');
            $table->string('unidad_medida');
            $table->boolean('activo')->default(true);
        });
        Schema::create('donaciones', function ($table): void {
            $table->increments('id_donacion');
            $table->string('estado');
        });
        Schema::create('donaciones_especie', function ($table): void {
            $table->unsignedInteger('id_donacion')->primary();
            $table->unsignedInteger('id_tipo_bien');
            $table->decimal('cantidad', 12, 2);
        });
        Schema::create('inventario', function ($table): void {
            $table->increments('id_inventario');
            $table->unsignedInteger('id_tipo_bien')->unique();
            $table->decimal('cantidad_total_recibida', 12, 2)->default(0);
            $table->decimal('cantidad_total_entregada', 12, 2)->default(0);
            $table->decimal('cantidad_disponible', 12, 2)
                ->storedAs('cantidad_total_recibida - cantidad_total_entregada');
            $table->unsignedInteger('id_usuario_actualizador');
        });
        Schema::create('movimientos_inventario', function ($table): void {
            $table->increments('id_movimiento');
            $table->unsignedInteger('id_inventario');
            $table->unsignedInteger('id_usuario');
            $table->string('tipo_movimiento');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('cantidad_anterior', 12, 2);
            $table->decimal('cantidad_posterior', 12, 2);
            $table->string('tipo_referencia');
            $table->unsignedInteger('id_referencia')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamp('fecha')->useCurrent();
        });
        Schema::create('entregas', function ($table): void {
            $table->increments('id_entrega');
            $table->unsignedInteger('id_donacion');
            $table->decimal('cantidad_entregada', 12, 2);
            $table->string('estado');
        });
    }
}
