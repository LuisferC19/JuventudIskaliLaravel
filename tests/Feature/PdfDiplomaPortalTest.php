<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\DiplomaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PdfDiplomaPortalTest extends TestCase
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

        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->nullable();
            $table->string('contrasena_hash')->nullable();
            $table->string('estatus')->default('ACTIVO');
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
        });
        Schema::create('permisos', function ($table): void {
            $table->increments('id_permiso');
            $table->string('slug')->unique();
        });
        Schema::create('rol_permiso', function ($table): void {
            $table->unsignedInteger('id_rol');
            $table->unsignedInteger('id_permiso');
        });
        Schema::create('donadores', function ($table): void {
            $table->increments('id_donador');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('id_nivel')->nullable();
            $table->string('tipo_donante')->default('persona');
            $table->integer('puntos_acumulados')->default(0);
            $table->boolean('activo')->default(true);
        });
        Schema::create('donadores_fisicos', function ($table): void {
            $table->unsignedInteger('id_donador');
            $table->string('nombre');
            $table->string('apellido');
        });
        Schema::create('donadores_morales', function ($table): void {
            $table->unsignedInteger('id_donador');
            $table->string('razon_social');
        });
        Schema::create('donadores_grupos', function ($table): void {
            $table->unsignedInteger('id_donador');
            $table->string('nombre_grupo');
        });
        Schema::create('reconocimientos', function ($table): void {
            $table->increments('id_reconocimiento');
            $table->unsignedInteger('id_donador')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('id_campana')->nullable();
            $table->string('tipo');
            $table->string('descripcion', 255)->nullable();
            $table->string('folio', 30)->nullable();
            $table->string('archivo_pdf_url')->nullable();
            $table->timestamp('fecha_emision')->nullable();
            $table->unsignedInteger('id_usuario_emisor')->nullable();
            $table->string('otorgado_por', 150)->nullable();
        });
        Schema::create('campanas', function ($table): void {
            $table->increments('id_campana');
            $table->string('nombre');
        });
        Schema::create('insignias', function ($table): void {
            $table->increments('id_insignia');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('imagen_url')->nullable();
        });
        Schema::create('donador_insignias', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('id_donador');
            $table->unsignedInteger('id_insignia');
            $table->timestamp('fecha_obtencion')->nullable();
        });
        Schema::create('donaciones', function ($table): void {
            $table->increments('id_donacion');
            $table->unsignedInteger('id_donador')->nullable();
            $table->unsignedInteger('id_campana')->nullable();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->string('estado')->default('recibida');
        });
        Schema::create('donaciones_especie', function ($table): void {
            $table->unsignedInteger('id_donacion');
            $table->unsignedInteger('id_tipo_bien');
            $table->decimal('cantidad', 12, 2);
            $table->string('unidad_medida');
        });
        Schema::create('tipos_bien', function ($table): void {
            $table->increments('id_tipo_bien');
            $table->string('nombre');
        });
        Schema::create('niveles_gamificacion', function ($table): void {
            $table->increments('id_nivel');
            $table->string('nombre');
        });
    }

    public function test_diploma_folio_increments_and_does_not_repeat(): void
    {
        DB::table('reconocimientos')->insert([
            'id_donador' => 1,
            'tipo' => 'diploma',
            'folio' => 'ISK-'.now()->year.'-0003',
            'fecha_emision' => now(),
        ]);

        $service = app(DiplomaService::class);
        $folio = $service->siguienteFolio();

        $this->assertMatchesRegularExpression('/^ISK-\d{4}-\d{4}$/', $folio);
        $this->assertSame('ISK-'.now()->year.'-0004', $folio);
    }

    public function test_donor_cannot_download_another_donors_diploma(): void
    {
        $usuario = $this->crearUsuario(2, ['donations.view_own', 'certificates.download']);
        DB::table('donadores')->insert(['id_donador' => 1, 'id_usuario' => 99, 'tipo_donante' => 'persona']);
        DB::table('donadores')->insert(['id_donador' => 2, 'id_usuario' => $usuario->id_usuario, 'tipo_donante' => 'persona']);
        DB::table('reconocimientos')->insert([
            'id_reconocimiento' => 10,
            'id_donador' => 1,
            'tipo' => 'diploma',
            'folio' => 'ISK-2026-0004',
            'fecha_emision' => now(),
        ]);

        $this->actingAs($usuario)
            ->get(route('reconocimientos.pdf', 10))
            ->assertForbidden();
    }

    public function test_donor_portal_route_is_available_but_dashboard_requires_permission(): void
    {
        $usuario = $this->crearUsuario(3, ['donations.view_own']);
        DB::table('donadores')->insert(['id_donador' => 3, 'id_usuario' => $usuario->id_usuario, 'tipo_donante' => 'persona']);

        $this->actingAs($usuario)
            ->get(route('portal.donador'))
            ->assertOk();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    private function crearUsuario(int $idRol, array $permisos): Usuario
    {
        $usuario = Usuario::create([
            'id_rol' => $idRol,
            'nombre' => 'Prueba',
            'apellido' => 'Usuario',
            'email' => 'usuario'.$idRol.'@example.test',
            'contrasena_hash' => 'hash',
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'intentos_fallidos' => 0,
        ]);

        foreach ($permisos as $slug) {
            $idPermiso = DB::table('permisos')->insertGetId(['slug' => $slug], 'id_permiso');
            DB::table('rol_permiso')->insert(['id_rol' => $idRol, 'id_permiso' => $idPermiso]);
        }

        return $usuario;
    }
}
