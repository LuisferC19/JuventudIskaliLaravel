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
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('zona_asignada')->default('san_martin');
            $table->boolean('activo')->default(true);
        });
        Schema::create('usuarios', function (Blueprint $table): void {
            $table->increments('id_usuario');
            $table->string('nombre')->default('Voluntario');
            $table->string('apellido')->default('Prueba');
        });
        Schema::create('campamentos', function (Blueprint $table): void {
            $table->increments('id_campamento');
            $table->string('nombre');
            $table->date('fecha_inicio');
            $table->string('zona');
            $table->unsignedInteger('umbral_puntos');
            $table->string('estado')->default('abierto');
        });
        Schema::create('penalizaciones_asistencia', function (Blueprint $table): void {
            $table->increments('id_penalizacion');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_actividad');
            $table->string('tipo');
            $table->unsignedInteger('puntos_descontados');
            $table->string('motivo')->nullable();
            $table->unsignedInteger('id_usuario_registra')->nullable();
            $table->dateTime('fecha');
            $table->boolean('anulada')->default(false);
            $table->unique(['id_voluntario', 'id_actividad']);
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
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 0,
            'puntos_campamento' => 100,
            'campamento_bloqueado' => false,
        ]);
        $puntos = app(PuntosService::class);

        $this->assertTrue($puntos->calificaCampamento(2, 100));
        $this->assertFalse($puntos->calificaCampamento(2, 101));
        DB::table('bolsa_voluntario')->where('id_voluntario', 2)->update(['campamento_bloqueado' => true]);
        $this->assertFalse($puntos->calificaCampamento(2, 100));
        $this->assertFalse($puntos->calificaCampamento(99, 100));
    }

    public function test_campamento_eligibility_uses_each_threshold_and_zone(): void
    {
        DB::table('voluntarios')->insert(['id_voluntario' => 2, 'id_usuario' => 22, 'zona_asignada' => 'san_martin']);
        DB::table('usuarios')->insert(['id_usuario' => 22, 'nombre' => 'Ana', 'apellido' => 'Prueba']);
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 0,
            'puntos_campamento' => 115,
            'campamento_bloqueado' => false,
        ]);
        DB::table('campamentos')->insert([
            ['id_campamento' => 1, 'nombre' => 'San Martín', 'fecha_inicio' => '2027-01-01', 'zona' => 'san_martin', 'umbral_puntos' => 100, 'estado' => 'abierto'],
            ['id_campamento' => 2, 'nombre' => 'Tlaxcala', 'fecha_inicio' => '2027-01-01', 'zona' => 'tlaxcala', 'umbral_puntos' => 100, 'estado' => 'abierto'],
            ['id_campamento' => 3, 'nombre' => 'Umbral alto', 'fecha_inicio' => '2027-01-01', 'zona' => 'san_martin', 'umbral_puntos' => 150, 'estado' => 'abierto'],
            ['id_campamento' => 4, 'nombre' => 'Cerrado', 'fecha_inicio' => '2027-01-01', 'zona' => 'san_martin', 'umbral_puntos' => 100, 'estado' => 'cerrado'],
        ]);

        $puntos = app(PuntosService::class);

        $this->assertSame([1], $puntos->campamentosParaVoluntario(2)->pluck('id_campamento')->all());
        $this->assertSame([2], $puntos->voluntariosQueCalifican(1)->pluck('id_voluntario')->all());
        $this->assertCount(0, $puntos->voluntariosQueCalifican(3));
    }

    public function test_penalties_cap_each_deduction_and_reversal_restores_exact_movement(): void
    {
        config([
            'iskali.penalizacion_retardo' => 2,
            'iskali.penalizacion_inasistencia' => 5,
        ]);
        DB::table('voluntarios')->insert(['id_voluntario' => 2]);
        DB::table('actividades')->insert([
            ['id_actividad' => 3, 'puntos' => 10],
            ['id_actividad' => 4, 'puntos' => 10],
        ]);
        DB::table('bolsa_voluntario')->insert([
            'id_voluntario' => 2,
            'puntos_canjeables' => 6,
            'puntos_campamento' => 7,
            'faltas_injustificadas' => 0,
            'campamento_bloqueado' => false,
        ]);
        $puntos = app(PuntosService::class);

        $idPenalizacion = $puntos->penalizar(2, 3, 'inasistencia_injustificada', 'Ausencia', 9);
        $this->assertDatabaseHas('penalizaciones_asistencia', [
            'id_penalizacion' => $idPenalizacion,
            'puntos_descontados' => 5,
            'anulada' => false,
        ]);
        $this->assertDatabaseHas('movimientos_puntos', [
            'id_voluntario' => 2,
            'id_actividad' => 3,
            'tipo' => 'penalizacion',
            'puntos_canjeables' => -5,
            'puntos_campamento' => -5,
        ]);
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 1,
            'puntos_campamento' => 2,
            'faltas_injustificadas' => 1,
            'campamento_bloqueado' => false,
        ]);

        $segundaPenalizacion = $puntos->penalizar(2, 4, 'inasistencia_injustificada', null, 9);
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 0,
            'puntos_campamento' => 0,
            'faltas_injustificadas' => 2,
            'campamento_bloqueado' => true,
        ]);

        $puntos->anularPenalizacion($segundaPenalizacion, 9);
        $this->assertDatabaseHas('penalizaciones_asistencia', ['id_penalizacion' => $segundaPenalizacion, 'anulada' => true]);
        $this->assertDatabaseHas('bolsa_voluntario', [
            'id_voluntario' => 2,
            'puntos_canjeables' => 1,
            'puntos_campamento' => 2,
            'faltas_injustificadas' => 1,
            'campamento_bloqueado' => false,
        ]);
        $this->assertDatabaseHas('movimientos_puntos', [
            'id_voluntario' => 2,
            'tipo' => 'ajuste',
            'puntos_canjeables' => 1,
            'puntos_campamento' => 2,
            'motivo' => 'Anulación de penalización',
        ]);
    }

    public function test_penalty_cannot_be_recorded_twice_for_the_same_volunteer_activity(): void
    {
        DB::table('voluntarios')->insert(['id_voluntario' => 2]);
        DB::table('actividades')->insert(['id_actividad' => 3, 'puntos' => 10]);
        $puntos = app(PuntosService::class);
        $puntos->penalizar(2, 3, 'retardo', null, null);

        $this->expectException(DomainException::class);
        $puntos->penalizar(2, 3, 'inasistencia_injustificada', null, null);
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
