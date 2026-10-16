<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Services\DashboardService;
use App\Services\DatabaseBackupService;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class PhaseThreeModulesTest extends TestCase
{
    private string $backupDirectory;
    private bool $createdBackupDirectory = false;
    private array $createdFiles = [];

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

        $this->backupDirectory = storage_path('app/respaldos');
        if (! is_dir($this->backupDirectory)) {
            mkdir($this->backupDirectory, 0700, true);
            $this->createdBackupDirectory = true;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if ($this->createdBackupDirectory && is_dir($this->backupDirectory) && count(scandir($this->backupDirectory)) === 2) {
            rmdir($this->backupDirectory);
        }

        parent::tearDown();
    }

    public function test_reports_apply_date_filters_and_return_printable_data(): void
    {
        DB::table('donadores')->insert([
            ['id_donador' => 1, 'tipo_donante' => 'persona', 'email' => 'ana@example.test', 'telefono' => '5550001', 'activo' => 1, 'created_at' => '2026-02-01 12:00:00'],
            ['id_donador' => 2, 'tipo_donante' => 'grupo', 'email' => 'grupo@example.test', 'telefono' => null, 'activo' => 0, 'created_at' => '2026-04-01 12:00:00'],
        ]);
        DB::table('donadores_fisicos')->insert(['id_donador' => 1, 'nombre' => 'Ana', 'apellido' => 'López']);
        DB::table('donadores_grupos')->insert(['id_donador' => 2, 'nombre_grupo' => 'Manos Unidas']);
        DB::table('beneficiarios')->insert([
            'id_beneficiario' => 1, 'tipo_persona' => 'fisica', 'estado' => 'activo', 'created_at' => '2026-02-02', 'id_comunidad' => 1,
        ]);
        DB::table('beneficiarios_fisicos')->insert(['id_beneficiario' => 1, 'nombre' => 'Luis', 'apellido' => 'Pérez', 'edad' => 12]);
        DB::table('comunidades')->insert(['id_comunidad' => 1, 'municipio' => 'Puebla']);
        DB::table('campanas')->insert([
            'id_campana' => 1, 'nombre' => 'Abrigo', 'descripcion' => 'Invierno', 'fecha_inicio' => '2026-02-01',
            'fecha_cierre' => '2026-03-01', 'estado' => 'activa', 'meta_economica' => 5000,
        ]);

        $reports = app(ReporteService::class);
        $donadores = $reports->donadores('2026-03-01', null);
        $beneficiarios = $reports->beneficiarios('2026-02-01', '2026-02-28');
        $campanas = $reports->campanas('2026-02-01', '2026-02-28');

        $this->assertCount(1, $donadores['rows']);
        $this->assertSame('Manos Unidas', $donadores['rows'][0]['nombre']);
        $this->assertSame(1, $donadores['summary']['Total']);
        $this->assertSame('Puebla', $beneficiarios['rows'][0]['municipio']);
        $this->assertSame('Abrigo', $campanas['rows'][0]['nombre']);
    }

    public function test_dashboard_uses_database_values_and_only_marks_empty_charts_demo(): void
    {
        DB::table('donaciones')->insert(['id_donacion' => 1, 'estado' => 'recibida', 'fecha_recepcion' => now()->toDateString(), 'id_campana' => null]);
        DB::table('donaciones_economicas')->insert(['id_donacion' => 1, 'monto' => 125.50]);
        DB::table('donadores')->insert([
            'id_donador' => 1, 'tipo_donante' => 'persona', 'email' => 'a@example.test', 'activo' => 1, 'created_at' => now(),
        ]);
        DB::table('beneficiarios')->insert([
            'id_beneficiario' => 1, 'tipo_persona' => 'fisica', 'estado' => 'activo', 'created_at' => now(),
        ]);

        $data = app(DashboardService::class)->datos();

        $this->assertSame('$125.50', $data['kpis']['donaciones_total']);
        $this->assertSame(1, $data['kpis']['donadores']);
        $this->assertSame(1, $data['kpis']['beneficiarios']);
        $this->assertFalse($data['charts']['estados_donaciones']['demo']);
        $this->assertSame(['Recibida'], array_values(array_filter(
            $data['charts']['estados_donaciones']['labels'],
            fn (string $label, int $index): bool => $data['charts']['estados_donaciones']['data'][$index] > 0,
            ARRAY_FILTER_USE_BOTH
        )));
        $this->assertTrue($data['charts']['roles']['demo']);
    }

    public function test_private_backup_round_trips_isolated_database_and_rejects_unexpected_zip_entries(): void
    {
        DB::table('phase3_items')->insert(['id' => 1, 'value' => 'original']);
        $service = new DatabaseBackupService($this->backupDirectory);
        $archive = $service->generar(null);
        $this->createdFiles[] = $archive;
        $this->assertStringStartsWith(storage_path('app').DIRECTORY_SEPARATOR, $archive);
        $this->assertSame(1, DB::table('respaldos')->where('tipo_operacion', 'EXPORTACION')->count());

        DB::table('phase3_items')->where('id', 1)->update(['value' => 'changed']);
        $uploaded = new UploadedFile($archive, basename($archive), 'application/zip', null, true);
        $imported = $service->importar($uploaded, null);
        $this->createdFiles[] = $imported;

        $this->assertSame('original', DB::table('phase3_items')->where('id', 1)->value('value'));
        $this->assertSame(1, DB::table('respaldos')->where('tipo_operacion', 'IMPORTACION')->count());

        $unsafeArchive = $this->backupDirectory.DIRECTORY_SEPARATOR.'invalid_'.bin2hex(random_bytes(3)).'.zip';
        $this->createdFiles[] = $unsafeArchive;
        $zip = new ZipArchive;
        $this->assertSame(true, $zip->open($unsafeArchive, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('../escape.json', '{}');
        $zip->close();

        $unsafeUpload = new UploadedFile($unsafeArchive, basename($unsafeArchive), 'application/zip', null, true);
        try {
            $service->importar($unsafeUpload, null);
            $this->fail('The import should reject archive paths that are not the fixed manifest name.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('únicamente', $exception->getMessage());
        }
    }

    public function test_registration_creates_inactive_volunteer_and_password_recovery_token_is_one_time(): void
    {
        DB::table('roles')->insert(['id_rol' => 1, 'nombre' => 'Voluntario', 'activo' => 1]);
        $controller = app(AuthController::class);
        $controller->register(Request::create('/registro', 'POST', [
            'nombre' => 'Sofía',
            'apellido' => 'Reyes',
            'email' => 'SOFIA@example.test',
            'password' => 'ClaveSegura9',
            'password_confirm' => 'ClaveSegura9',
        ]));

        $this->assertDatabaseHas('usuarios', [
            'email' => 'sofia@example.test',
            'id_rol' => 1,
            'activo' => 0,
        ]);
        $userId = (int) DB::table('usuarios')->where('email', 'sofia@example.test')->value('id_usuario');
        $recoveryRequest = Request::create('/recuperar-password', 'POST', ['email' => 'sofia@example.test']);
        $recoveryRequest->setLaravelSession(app('session.store'));
        $recoveryResponse = $controller->solicitarRecuperacion($recoveryRequest);
        $resetUrl = $recoveryResponse->getSession()->get('reset_url');
        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);
        $token = $query['token'] ?? '';

        $this->assertSame(64, strlen($token));
        $this->assertSame(hash('sha256', $token), DB::table('recuperacion_contrasena')->value('token'));
        $resetPage = $controller->mostrarRestablecimiento(Request::create('/recuperar-password/reset?token='.$token));
        $this->assertTrue($resetPage->getData()['valid']);

        $resetRequest = Request::create('/recuperar-password/reset', 'POST', [
            'token' => $token,
            'password' => 'NuevaClave8',
            'password_confirm' => 'NuevaClave8',
        ]);
        $controller->restablecerContrasena($resetRequest);

        $this->assertSame(1, DB::table('recuperacion_contrasena')->where('usado', 1)->count());
        $this->assertSame(1, DB::table('historial_accesos')->where('tipo_accion', 'recuperacion')->count());
        $this->assertTrue(password_verify('NuevaClave8', (string) DB::table('usuarios')->where('id_usuario', $userId)->value('contrasena_hash')));
    }

    private function crearEsquemaAislado(): void
    {
        Schema::create('roles', function ($table): void {
            $table->increments('id_rol');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol')->nullable();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->boolean('activo')->default(false);
            $table->integer('intentos_fallidos')->default(0);
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('recuperacion_contrasena', function ($table): void {
            $table->increments('id_recuperacion');
            $table->unsignedInteger('id_usuario');
            $table->string('token')->unique();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_expiracion');
            $table->boolean('usado')->default(false);
        });
        Schema::create('historial_accesos', function ($table): void {
            $table->increments('id_acceso');
            $table->unsignedInteger('id_usuario');
            $table->string('tipo_accion');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('exitoso')->default(true);
        });
        Schema::create('respaldos', function ($table): void {
            $table->bigIncrements('id');
            $table->string('tipo_operacion');
            $table->string('nombre_archivo');
            $table->string('formato')->nullable();
            $table->string('nombre_bd');
            $table->unsignedBigInteger('tamanio_bytes')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('fechayhora')->useCurrent();
            $table->text('observaciones')->nullable();
        });
        Schema::create('phase3_items', function ($table): void {
            $table->increments('id');
            $table->string('value');
        });
        Schema::create('donadores', function ($table): void {
            $table->increments('id_donador');
            $table->string('tipo_donante');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('activo');
            $table->dateTime('created_at');
        });
        Schema::create('donadores_fisicos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre');
            $table->string('apellido');
        });
        Schema::create('donadores_morales', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('razon_social');
        });
        Schema::create('donadores_grupos', function ($table): void {
            $table->unsignedInteger('id_donador')->primary();
            $table->string('nombre_grupo');
        });
        Schema::create('beneficiarios', function ($table): void {
            $table->increments('id_beneficiario');
            $table->string('tipo_persona');
            $table->string('estado');
            $table->dateTime('created_at');
            $table->unsignedInteger('id_comunidad')->nullable();
        });
        Schema::create('beneficiarios_fisicos', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('nombre');
            $table->string('apellido');
            $table->unsignedInteger('edad')->nullable();
        });
        Schema::create('beneficiarios_morales', function ($table): void {
            $table->unsignedInteger('id_beneficiario')->primary();
            $table->string('razon_social');
        });
        Schema::create('comunidades', function ($table): void {
            $table->increments('id_comunidad');
            $table->string('municipio');
        });
        Schema::create('campanas', function ($table): void {
            $table->increments('id_campana');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_cierre');
            $table->string('estado');
            $table->decimal('meta_economica', 12, 2);
        });
        Schema::create('donaciones', function ($table): void {
            $table->increments('id_donacion');
            $table->string('estado');
            $table->date('fecha_recepcion')->nullable();
            $table->unsignedInteger('id_campana')->nullable();
        });
        Schema::create('donaciones_economicas', function ($table): void {
            $table->unsignedInteger('id_donacion')->primary();
            $table->decimal('monto', 12, 2);
        });
        Schema::create('donaciones_especie', function ($table): void {
            $table->unsignedInteger('id_donacion')->primary();
            $table->decimal('cantidad', 12, 2);
        });
        Schema::create('inventario', function ($table): void {
            $table->increments('id_inventario');
        });
        Schema::create('entregas', function ($table): void {
            $table->increments('id_entrega');
            $table->string('estado');
        });
        Schema::create('voluntarios', function ($table): void {
            $table->increments('id_voluntario');
            $table->boolean('activo')->default(true);
        });
    }
}
