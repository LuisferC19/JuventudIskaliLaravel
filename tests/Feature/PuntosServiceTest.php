<?php

namespace Tests\Feature;

use App\Services\PuntosService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PuntosServiceTest extends TestCase
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

        Schema::create('actividades', function (Blueprint $table): void {
            $table->increments('id_actividad');
            $table->unsignedInteger('puntos');
            $table->string('estado')->default('planeada');
        });
        Schema::create('voluntarios', function (Blueprint $table): void {
            $table->increments('id_voluntario');
            $table->boolean('activo')->default(true);
        });
        Schema::create('asistencia_voluntarios', function (Blueprint $table): void {
            $table->increments('id_asistencia');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_actividad');
            $table->dateTime('fecha_hora')->nullable();
            $table->string('metodo')->default('manual');
            $table->boolean('presente')->default(false);
            $table->string('token_qr')->nullable();
        });
        Schema::create('bolsa_voluntario', function (Blueprint $table): void {
            $table->increments('id_bolsa');
            $table->unsignedInteger('id_voluntario')->unique();
            $table->unsignedInteger('puntos_canjeables')->default(0);
            $table->unsignedInteger('puntos_campamento')->default(0);
            $table->unsignedInteger('faltas_injustificadas')->default(0);
            $table->boolean('campamento_bloqueado')->default(false);
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('cat_premios', function (Blueprint $table): void {
            $table->increments('id_premio');
            $table->string('nombre');
            $table->unsignedInteger('costo_puntos');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('activo')->default(true);
        });
        Schema::create('canjes_premios', function (Blueprint $table): void {
            $table->increments('id_canje');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_premio');
            $table->unsignedInteger('puntos_usados');
            $table->string('estado');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_entrega')->nullable();
            $table->unsignedInteger('id_usuario_registra')->nullable();
            $table->string('observaciones')->nullable();
        });
        Schema::create('movimientos_puntos', function (Blueprint $table): void {
            $table->increments('id_movimiento_puntos');
            $table->unsignedInteger('id_voluntario');
            $table->string('tipo');
            $table->integer('puntos_canjeables')->default(0);
            $table->integer('puntos_campamento')->default(0);
            $table->unsignedInteger('id_actividad')->nullable();
            $table->unsignedInteger('id_canje')->nullable();
            $table->string('motivo')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->dateTime('fecha');
            $table->unique(['id_voluntario', 'id_actividad', 'tipo']);
        });
    }

    public function test_attendance_points_are_awarded_to_both_bags_only_once(): void
    {
        DB::table('actividades')->insert(['id_actividad' => 3, 'puntos' => 10]);
        $puntos = app(PuntosService::class);

        $this->assertTrue($puntos->otorgarPorAsistencia(2, 3));
        $this->assertFalse($puntos->otorgarPorAsistencia(2, 3));
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 10,
            'puntos_campamento' => 10,
        ]);
        $this->assertDatabaseCount('movimientos_puntos', 1);
    }

    public function test_cancelled_redemption_restores_only_the_redeemable_bag_and_stock(): void
    {
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 20,
            'puntos_campamento' => 30,
        ]);
        DB::table('cat_premios')->insert([
            'id_premio' => 4,
            'nombre' => 'Premio de prueba',
            'costo_puntos' => 15,
            'stock' => 2,
            'activo' => true,
        ]);
        $puntos = app(PuntosService::class);

        $idCanje = $puntos->canjear(2, 4);
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 5,
            'puntos_campamento' => 30,
        ]);
        $this->assertDatabaseHas('cat_premios', ['id_premio' => 4, 'stock' => 1]);
        $this->assertDatabaseHas('movimientos_puntos', [
            'id_voluntario' => 2,
            'tipo' => 'canje',
            'puntos_canjeables' => -15,
            'puntos_campamento' => 0,
            'id_canje' => $idCanje,
        ]);

        $puntos->cancelarCanje($idCanje);
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 20,
            'puntos_campamento' => 30,
        ]);
        $this->assertDatabaseHas('cat_premios', ['id_premio' => 4, 'stock' => 2]);
        $this->assertDatabaseHas('canjes_premios', ['id_canje' => $idCanje, 'estado' => 'cancelado']);
        $this->assertDatabaseHas('movimientos_puntos', [
            'id_voluntario' => 2,
            'tipo' => 'ajuste',
            'puntos_canjeables' => 15,
            'puntos_campamento' => 0,
            'id_canje' => $idCanje,
        ]);
    }

    public function test_redemption_rejects_insufficient_points_without_changing_stock(): void
    {
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 10,
            'puntos_campamento' => 20,
        ]);
        DB::table('cat_premios')->insert([
            'id_premio' => 4,
            'nombre' => 'Premio de prueba',
            'costo_puntos' => 15,
            'stock' => 1,
            'activo' => true,
        ]);

        try {
            app(PuntosService::class)->canjear(2, 4);
            $this->fail('Se esperaba una excepción por puntos insuficientes.');
        } catch (DomainException $exception) {
            $this->assertSame('El voluntario no tiene puntos canjeables suficientes para este premio.', $exception->getMessage());
        }

        $this->assertDatabaseHas('bolsa_voluntario', ['id_voluntario' => 2, 'puntos_canjeables' => 10]);
        $this->assertDatabaseHas('cat_premios', ['id_premio' => 4, 'stock' => 1]);
        $this->assertDatabaseCount('canjes_premios', 0);
        $this->assertDatabaseCount('movimientos_puntos', 0);
    }

    public function test_redemption_rejects_inactive_or_out_of_stock_rewards(): void
    {
        DB::table('cat_premios')->insert([
            'id_premio' => 4,
            'nombre' => 'Premio de prueba',
            'costo_puntos' => 5,
            'stock' => 1,
            'activo' => false,
        ]);
        $puntos = app(PuntosService::class);

        try {
            $puntos->canjear(2, 4);
            $this->fail('Se esperaba una excepción para un premio inactivo.');
        } catch (DomainException $exception) {
            $this->assertSame('El premio no existe o está inactivo.', $exception->getMessage());
        }

        DB::table('cat_premios')->where('id_premio', 4)->update(['activo' => true, 'stock' => 0]);
        try {
            $puntos->canjear(2, 4);
            $this->fail('Se esperaba una excepción para un premio sin existencias.');
        } catch (DomainException $exception) {
            $this->assertSame('El premio no tiene existencias disponibles.', $exception->getMessage());
        }

        $this->assertDatabaseCount('canjes_premios', 0);
        $this->assertDatabaseCount('movimientos_puntos', 0);
    }

    public function test_campamento_qualification_respects_threshold_and_block(): void
    {
        config(['iskali.umbral_campamento' => 100]);
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 0,
            'puntos_campamento' => 100,
            'campamento_bloqueado' => false,
        ]);
        $puntos = app(PuntosService::class);

        $this->assertTrue($puntos->calificaCampamento(2));
        DB::table('bolsa_voluntario')->where('id_voluntario', 2)->update(['campamento_bloqueado' => true]);
        $this->assertFalse($puntos->calificaCampamento(2));
        $this->assertFalse($puntos->calificaCampamento(99));
    }

    public function test_manual_attendance_awards_points_only_on_the_first_presence(): void
    {
        DB::table('voluntarios')->insert(['id_voluntario' => 2, 'activo' => true]);
        DB::table('actividades')->insert(['id_actividad' => 3, 'puntos' => 10, 'estado' => 'planeada']);
        $flujo = app(\App\Services\VoluntarioWorkflowService::class);

        $flujo->registrarAsistenciaManual(2, 3);
        $flujo->registrarAsistenciaManual(2, 3);

        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 10,
            'puntos_campamento' => 10,
        ]);
        $this->assertDatabaseCount('movimientos_puntos', 1);
    }
}
