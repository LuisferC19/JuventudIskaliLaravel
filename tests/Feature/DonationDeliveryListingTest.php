<?php

namespace Tests\Feature;

use App\Http\Controllers\DonacionController;
use App\Http\Controllers\EntregaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DonationDeliveryListingTest extends TestCase
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
    }

    public function test_donaciones_sin_relaciones_siguen_listandose_y_las_estadisticas_no_dependen_del_filtro(): void
    {
        DB::table('donaciones')->insert([
            ['id_donacion' => 1, 'id_donador' => 999, 'id_campana' => 999, 'id_usuario_registrador' => 999, 'fecha_recepcion' => '2026-10-01', 'estado' => 'verificada'],
            ['id_donacion' => 2, 'id_donador' => 999, 'id_campana' => 999, 'id_usuario_registrador' => 999, 'fecha_recepcion' => '2026-10-02', 'estado' => 'pendiente'],
        ]);

        $vista = app(DonacionController::class)->index(Request::create('/donaciones?estado=verificada', 'GET'));
        $datos = $vista->getData();

        $this->assertSame(1, $datos['donaciones']->count());
        $this->assertSame(2, $datos['totalDonaciones']);
        $this->assertSame(1, $datos['donacionesPendientes']);
        $this->assertSame(1, $datos['donacionesVerificadas']);
        $this->assertSame('Donador no disponible', $datos['donaciones']->first()->donador);
        $this->assertSame('Campaña no disponible', $datos['donaciones']->first()->campana);
    }

    public function test_entregas_sin_relaciones_siguen_listandose_con_etiquetas_de_respaldo(): void
    {
        DB::table('entregas')->insert([
            'id_entrega' => 1,
            'id_donacion' => 999,
            'id_beneficiario' => 999,
            'id_usuario_responsable' => 999,
            'cantidad_entregada' => 5,
            'fecha_entrega' => '2026-10-06 14:27:00',
            'estado' => 'completada',
        ]);

        $vista = app(EntregaController::class)->index();
        $datos = $vista->getData();

        $this->assertSame(1, $datos['entregas']->count());
        $this->assertSame(1, $datos['totalEntregas']);
        $this->assertSame('Donador no disponible', $datos['entregas']->first()->donador);
        $this->assertSame('Beneficiario no disponible', $datos['entregas']->first()->beneficiario);
        $this->assertSame('Campaña no disponible', $datos['entregas']->first()->campana);
    }

    private function crearEsquemaAislado(): void
    {
        Schema::create('donaciones', function ($table): void {
            $table->increments('id_donacion');
            $table->unsignedInteger('id_donador')->nullable();
            $table->unsignedInteger('id_campana')->nullable();
            $table->unsignedInteger('id_usuario_registrador')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->string('estado');
            $table->string('evidencia_url')->nullable();
        });
        Schema::create('donadores', function ($table): void {
            $table->increments('id_donador');
        });
        Schema::create('donadores_fisicos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
        });
        Schema::create('donadores_morales', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('razon_social')->nullable();
        });
        Schema::create('donadores_grupos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre_grupo')->nullable();
        });
        Schema::create('campanas', function ($table): void {
            $table->increments('id_campana');
            $table->string('nombre');
        });
        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
        });
        Schema::create('donaciones_especie', function ($table): void {
            $table->unsignedInteger('id_donacion')->primary();
            $table->unsignedInteger('id_tipo_bien')->nullable();
            $table->decimal('cantidad', 12, 2)->nullable();
            $table->string('unidad_medida')->nullable();
        });
        Schema::create('tipos_bien', function ($table): void {
            $table->increments('id_tipo_bien');
            $table->string('nombre')->nullable();
        });
        Schema::create('donaciones_economicas', function ($table): void {
            $table->unsignedInteger('id_donacion')->primary();
            $table->decimal('monto', 12, 2)->nullable();
            $table->string('moneda')->nullable();
        });
        Schema::create('entregas', function ($table): void {
            $table->increments('id_entrega');
            $table->unsignedInteger('id_donacion')->nullable();
            $table->unsignedInteger('id_beneficiario')->nullable();
            $table->unsignedInteger('id_usuario_responsable')->nullable();
            $table->decimal('cantidad_entregada', 12, 2)->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->string('estado');
            $table->string('evidencia_url')->nullable();
            $table->text('observaciones')->nullable();
        });
        Schema::create('beneficiarios', function ($table): void {
            $table->increments('id_beneficiario');
        });
        Schema::create('beneficiarios_fisicos', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
        });
        Schema::create('beneficiarios_morales', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('razon_social')->nullable();
        });
    }
}
