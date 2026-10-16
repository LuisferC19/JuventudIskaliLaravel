<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificarPermiso;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseOneCrudTest extends TestCase
{
    protected Usuario $administrador;

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
        $this->withoutMiddleware(VerificarPermiso::class);

        DB::table('roles')->insert(['id_rol' => 1, 'nombre' => 'Administrador', 'activo' => true]);
        $this->administrador = Usuario::create([
            'id_rol' => 1,
            'nombre' => 'Admin',
            'apellido' => 'Pruebas',
            'email' => 'admin@example.test',
            'contrasena_hash' => Hash::make('secret1'),
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
        $this->actingAs($this->administrador);
    }

    public function test_usuario_se_crea_con_contrasena_hasheada_y_rol_de_bd(): void
    {
        $this->post(route('usuarios.store'), [
            'nombre' => 'María',
            'apellido' => 'Pérez López',
            'email' => 'maria@example.test',
            'password' => 'secret2',
            'confirmar_password' => 'secret2',
            'id_rol' => 1,
            'activo' => 1,
        ])->assertRedirect(route('usuarios.index'));

        $usuario = Usuario::where('email', 'maria@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret2', $usuario->contrasena_hash));
        $this->assertSame(0, $usuario->intentos_fallidos);
    }

    public function test_login_rehashea_contrasena_en_la_columna_personalizada(): void
    {
        Auth::logout();
        $hashAnterior = password_hash('secret1', PASSWORD_BCRYPT, ['cost' => 4]);
        Hash::driver('bcrypt')->setRounds(5);
        $this->administrador->update(['contrasena_hash' => $hashAnterior]);

        $this->assertTrue(Auth::attempt([
            'email' => $this->administrador->email,
            'password' => 'secret1',
            'activo' => 1,
        ]));

        $hashActualizado = $this->administrador->fresh()->contrasena_hash;
        $this->assertNotSame($hashAnterior, $hashActualizado);
        $this->assertTrue(Hash::check('secret1', $hashActualizado));
        $this->assertSame('contrasena_hash', $this->administrador->getAuthPasswordName());
    }

    public function test_usuario_edicion_sin_nueva_contrasena_conserva_el_hash_actual(): void
    {
        $hashActual = $this->administrador->contrasena_hash;

        $this->put(route('usuarios.update', $this->administrador->id_usuario), [
            'nombre' => 'Admin',
            'apellido' => 'Actualizado',
            'email' => $this->administrador->email,
            'id_rol' => 1,
            'activo' => 1,
        ])->assertRedirect(route('usuarios.index'));

        $this->assertSame($hashActual, $this->administrador->fresh()->contrasena_hash);
    }

    public function test_donador_fisico_se_guarda_con_su_subtipo_en_una_transaccion(): void
    {
        $this->post(route('donadores.store'), [
            'tipo_persona' => 'fisica',
            'nombre' => 'Ana',
            'apellido' => 'López',
            'curp' => 'HEGJ830428HMNRRL05',
            'email' => 'ana@example.test',
            'puntos_acumulados' => 5,
            'activo' => 1,
        ])->assertRedirect(route('donadores.index'));

        $donador = DB::table('donadores')->where('email', 'ana@example.test')->first();
        $this->assertNotNull($donador);
        $this->assertSame('persona', $donador->tipo_donante);
        $this->assertSame('Ana', DB::table('donadores_fisicos')->where('id_donador', $donador->id_donador)->value('nombre'));
    }

    public function test_donador_anonimo_se_guarda_sin_subtipo_y_grupo_con_su_tabla(): void
    {
        $this->post(route('donadores.store'), [
            'tipo_persona' => 'anonimo',
            'email' => 'anonimo@example.test',
            'activo' => 1,
        ])->assertRedirect(route('donadores.index'));

        $this->post(route('donadores.store'), [
            'tipo_persona' => 'grupo',
            'nombre_grupo' => 'Red Comunitaria',
            'representante' => 'Luz García',
            'email' => 'grupo@example.test',
        ])->assertRedirect(route('donadores.index'));

        $anonimo = DB::table('donadores')->where('email', 'anonimo@example.test')->first();
        $this->assertSame('anonimo', $anonimo->tipo_donante);
        $this->assertSame(0, DB::table('donadores_grupos')->where('id_donador', $anonimo->id_donador)->count());

        $grupo = DB::table('donadores')->where('email', 'grupo@example.test')->first();
        $this->assertSame('Red Comunitaria', DB::table('donadores_grupos')->where('id_donador', $grupo->id_donador)->value('nombre_grupo'));
    }

    public function test_actualizar_donador_reemplaza_el_subtipo_en_la_misma_transaccion(): void
    {
        $this->post(route('donadores.store'), [
            'tipo_persona' => 'fisica',
            'nombre' => 'Ana',
            'apellido' => 'López',
            'email' => 'ana@example.test',
        ]);
        $idDonador = DB::table('donadores')->where('email', 'ana@example.test')->value('id_donador');

        $this->put(route('donadores.update', $idDonador), [
            'tipo_persona' => 'moral',
            'razon_social' => 'Fundación Iskali',
            'rfc' => 'ABC240101ABC',
            'email' => 'ana@example.test',
            'activo' => 1,
        ])->assertRedirect(route('donadores.index'));

        $this->assertSame('organizacion', DB::table('donadores')->where('id_donador', $idDonador)->value('tipo_donante'));
        $this->assertSame(0, DB::table('donadores_fisicos')->where('id_donador', $idDonador)->count());
        $this->assertSame('Fundación Iskali', DB::table('donadores_morales')->where('id_donador', $idDonador)->value('razon_social'));
    }

    public function test_beneficiario_moral_guarda_comunidad_y_subtipo(): void
    {
        DB::table('comunidades')->insert([
            'id_comunidad' => 1,
            'nombre' => 'San Martín',
            'municipio' => 'Texmelucan',
            'estado' => 'Puebla',
        ]);

        $this->post(route('beneficiarios.store'), [
            'tipo_persona' => 'moral',
            'razon_social' => 'Casa de Apoyo A.C.',
            'rfc' => 'ABC240101ABC',
            'id_comunidad' => 1,
            'estado' => 'activo',
            'direccion' => 'Calle Reforma 1',
            'telefono' => '2221234567',
        ])->assertRedirect(route('beneficiarios.index'));

        $beneficiario = DB::table('beneficiarios')->first();
        $this->assertSame(1, $beneficiario->id_comunidad);
        $this->assertSame('Casa de Apoyo A.C.', DB::table('beneficiarios_morales')->where('id_beneficiario', $beneficiario->id_beneficiario)->value('razon_social'));
    }

    public function test_actualizar_beneficiario_reemplaza_el_subtipo_y_baja_fisica(): void
    {
        DB::table('comunidades')->insert([
            'id_comunidad' => 1,
            'nombre' => 'San Martín',
            'municipio' => 'Texmelucan',
            'estado' => 'Puebla',
        ]);
        $this->post(route('beneficiarios.store'), [
            'tipo_persona' => 'fisica',
            'nombre' => 'Ana',
            'apellido' => 'López',
            'id_comunidad' => 1,
            'estado' => 'activo',
        ]);
        $idBeneficiario = DB::table('beneficiarios')->value('id_beneficiario');

        $this->put(route('beneficiarios.update', $idBeneficiario), [
            'tipo_persona' => 'moral',
            'razon_social' => 'Casa de Apoyo A.C.',
            'id_comunidad' => 1,
            'estado' => 'en_espera',
        ])->assertRedirect(route('beneficiarios.index'));

        $this->assertSame('moral', DB::table('beneficiarios')->where('id_beneficiario', $idBeneficiario)->value('tipo_persona'));
        $this->assertSame(0, DB::table('beneficiarios_fisicos')->where('id_beneficiario', $idBeneficiario)->count());
        $this->assertSame('Casa de Apoyo A.C.', DB::table('beneficiarios_morales')->where('id_beneficiario', $idBeneficiario)->value('razon_social'));
    }

    public function test_eliminar_beneficiario_elimina_sus_registros_dependientes(): void
    {
        DB::table('comunidades')->insert([
            'id_comunidad' => 1,
            'nombre' => 'San Martín',
            'municipio' => 'Texmelucan',
            'estado' => 'Puebla',
        ]);
        $this->post(route('beneficiarios.store'), [
            'tipo_persona' => 'fisica',
            'nombre' => 'Ana',
            'apellido' => 'López',
            'id_comunidad' => 1,
            'estado' => 'activo',
        ]);
        $idBeneficiario = DB::table('beneficiarios')->value('id_beneficiario');
        DB::table('beneficiario_tipos_apoyo')->insert(['id_beneficiario' => $idBeneficiario]);

        $this->delete(route('beneficiarios.destroy', $idBeneficiario))
            ->assertRedirect(route('beneficiarios.index'));

        $this->assertDatabaseMissing('beneficiarios', ['id_beneficiario' => $idBeneficiario]);
        $this->assertDatabaseMissing('beneficiarios_fisicos', ['id_beneficiario' => $idBeneficiario]);
        $this->assertDatabaseMissing('beneficiario_tipos_apoyo', ['id_beneficiario' => $idBeneficiario]);
    }

    public function test_usuario_con_datos_criticos_se_desactiva_en_lugar_de_borrarse(): void
    {
        $usuario = Usuario::create([
            'id_rol' => 1,
            'nombre' => 'Usuario',
            'apellido' => 'Relacionado',
            'email' => 'relacionado@example.test',
            'contrasena_hash' => Hash::make('secret3'),
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
        DB::table('campanas')->insert(['id_usuario_creador' => $usuario->id_usuario]);

        $this->delete(route('usuarios.destroy', $usuario->id_usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $usuario->id_usuario,
            'activo' => false,
        ]);
    }

    public function test_usuario_sin_datos_criticos_se_elimina_fisicamente(): void
    {
        $usuario = Usuario::create([
            'id_rol' => 1,
            'nombre' => 'Usuario',
            'apellido' => 'Temporal',
            'email' => 'temporal@example.test',
            'contrasena_hash' => Hash::make('secret3'),
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);

        $this->delete(route('usuarios.destroy', $usuario->id_usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('usuarios', ['id_usuario' => $usuario->id_usuario]);
    }

    public function test_desbloquear_reinicia_intentos_y_fecha_de_bloqueo(): void
    {
        $this->administrador->update([
            'intentos_fallidos' => 5,
            'fecha_bloqueo_temporal' => now(),
        ]);

        $this->post(route('usuarios.desbloquear', $this->administrador->id_usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertSame(0, $this->administrador->fresh()->intentos_fallidos);
        $this->assertNull($this->administrador->fresh()->fecha_bloqueo_temporal);
    }

    private function crearEsquemaAislado(): void
    {
        Schema::create('roles', function ($table): void {
            $table->increments('id_rol');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('intentos_fallidos')->default(0);
            $table->timestamp('fecha_bloqueo_temporal')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('donadores', function ($table): void {
            $table->increments('id_donador');
            $table->unsignedInteger('id_nivel')->nullable();
            $table->string('tipo_donante')->default('persona');
            $table->unsignedInteger('puntos_acumulados')->default(0);
            $table->string('email')->unique();
            $table->string('telefono')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('donadores_fisicos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('curp')->nullable()->unique();
            $table->date('fecha_nacimiento')->nullable();
        });
        Schema::create('donadores_morales', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('razon_social');
            $table->string('rfc')->nullable()->unique();
            $table->string('representante_legal')->nullable();
            $table->string('giro_comercial')->nullable();
        });
        Schema::create('donadores_grupos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre_grupo');
            $table->string('representante')->nullable();
        });
        Schema::create('beneficiarios', function ($table): void {
            $table->increments('id_beneficiario');
            $table->string('tipo_persona');
            $table->unsignedInteger('id_comunidad');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('estado');
            $table->text('notas')->nullable();
            $table->unsignedInteger('id_usuario_registrador')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('beneficiarios_fisicos', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('nombre');
            $table->string('apellido');
            $table->unsignedInteger('edad')->nullable();
            $table->string('curp')->nullable()->unique();
            $table->date('fecha_nacimiento')->nullable();
        });
        Schema::create('beneficiarios_morales', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('razon_social');
            $table->string('rfc')->nullable()->unique();
        });
        Schema::create('comunidades', function ($table): void {
            $table->increments('id_comunidad');
            $table->string('nombre');
            $table->string('municipio');
            $table->string('estado');
        });

        foreach ([
            'campanas' => ['id_usuario_creador'],
            'donaciones' => ['id_usuario_registrador'],
            'entregas' => ['id_usuario_responsable', 'id_entrega', 'id_beneficiario'],
            'avances_campana' => ['id_usuario'],
            'movimientos_inventario' => ['id_usuario'],
            'reconocimientos' => ['id_usuario_emisor'],
            'notificaciones' => ['id_usuario'],
            'historial_accesos' => ['id_usuario'],
            'recuperacion_contrasenas' => ['id_usuario'],
            'asignaciones_voluntario' => ['id_voluntario', 'id_entrega'],
            'asistencia_voluntarios' => ['id_voluntario', 'presente'],
            'voluntarios' => ['id_voluntario', 'id_usuario', 'activo'],
            'beneficiario_tipos_apoyo' => ['id_beneficiario'],
        ] as $tabla => $columnas) {
            Schema::create($tabla, function ($table) use ($columnas): void {
                $table->increments('id');
                foreach ($columnas as $columna) {
                    if ($columna === 'presente' || $columna === 'activo') {
                        $table->boolean($columna)->default(false);
                    } else {
                        $table->unsignedInteger($columna)->nullable();
                    }
                }
            });
        }
    }
}
