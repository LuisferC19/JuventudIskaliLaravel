<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\PermisosUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermisosUsuarioTest extends TestCase
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

        Schema::create('permisos', function ($table): void {
            $table->increments('id_permiso');
            $table->string('slug')->unique();
            $table->string('modulo');
            $table->string('descripcion')->nullable();
        });
        Schema::create('rol_permiso', function ($table): void {
            $table->unsignedInteger('id_rol');
            $table->unsignedInteger('id_permiso');
            $table->primary(['id_rol', 'id_permiso']);
        });

        DB::table('permisos')->insert([
            ['id_permiso' => 1, 'slug' => 'users.view', 'modulo' => 'Usuarios'],
            ['id_permiso' => 2, 'slug' => 'users.view_zone', 'modulo' => 'Usuarios'],
            ['id_permiso' => 3, 'slug' => 'inventory.adjust', 'modulo' => 'Inventario'],
            ['id_permiso' => 4, 'slug' => 'dashboard.view', 'modulo' => 'Dashboard'],
            ['id_permiso' => 5, 'slug' => 'rewards.manage', 'modulo' => 'Gamificacion'],
        ]);
        DB::table('rol_permiso')->insert([
            ['id_rol' => 1, 'id_permiso' => 1],
            ['id_rol' => 1, 'id_permiso' => 3],
            ['id_rol' => 1, 'id_permiso' => 4],
            ['id_rol' => 1, 'id_permiso' => 5],
            ['id_rol' => 2, 'id_permiso' => 2],
        ]);
    }

    public function test_permissions_are_loaded_from_the_normalized_role_permission_tables(): void
    {
        $service = new PermisosUsuario;
        $superadmin = (new Usuario)->forceFill([
            'id_usuario' => 1,
            'id_rol' => 1,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);
        $coordinator = (new Usuario)->forceFill([
            'id_usuario' => 2,
            'id_rol' => 2,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);

        $this->assertTrue($service->usuarioPuede($superadmin, 'usuarios', 'ver'));
        $this->assertTrue($service->usuarioPuede($superadmin, 'inventario', 'editar'));
        $this->assertTrue($service->usuarioPuede($superadmin, 'inventory.adjust'));
        $this->assertFalse($service->usuarioPuede($superadmin, 'usuarios', 'crear'));
        $this->assertFalse($service->usuarioPuede($coordinator, 'usuarios', 'ver'));
        $this->assertTrue($service->usuarioPuede($coordinator, 'voluntarios', 'ver'));
        $this->assertFalse($service->usuarioPuede($superadmin, 'modulo_inexistente', 'ver'));
    }

    public function test_dashboard_requires_an_explicit_permission_and_module_wildcards_require_active_status(): void
    {
        $service = new PermisosUsuario;
        $usuario = (new Usuario)->forceFill([
            'id_usuario' => 3,
            'id_rol' => 3,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);
        $usuarioConDashboard = (new Usuario)->forceFill([
            'id_usuario' => 1,
            'id_rol' => 1,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);

        $this->assertFalse($service->usuarioPuede($usuario, 'dashboard', 'ver'));
        $this->assertTrue($service->usuarioPuede($usuarioConDashboard, 'dashboard', 'ver'));
        $this->assertTrue($service->usuarioPuede($usuario, 'notificaciones', 'ver'));

        $usuario->estatus = Usuario::ESTATUS_INACTIVO;
        $this->assertFalse($service->usuarioPuede($usuario, 'notificaciones', 'ver'));
    }

    public function test_visible_zones_follow_user_permissions_and_sede(): void
    {
        $coordinator = (new Usuario)->forceFill([
            'id_rol' => 2,
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'sede' => 'san_martin',
        ]);
        $allZonesCoordinator = (new Usuario)->forceFill([
            'id_rol' => 2,
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'sede' => 'ambas',
        ]);
        $withoutZonePermission = (new Usuario)->forceFill([
            'id_rol' => 3,
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'sede' => 'san_martin',
        ]);
        $userViewer = (new Usuario)->forceFill([
            'id_rol' => 1,
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'sede' => 'san_martin',
        ]);

        $this->assertSame(['san_martin', 'ambas'], $coordinator->zonasVisibles());
        $this->assertNull($allZonesCoordinator->zonasVisibles());
        $this->assertSame([], $withoutZonePermission->zonasVisibles());
        $this->assertNull($userViewer->zonasVisibles());
    }

    public function test_gamification_management_requires_the_rewards_manage_permission(): void
    {
        $service = new PermisosUsuario;
        $directiva = (new Usuario)->forceFill([
            'id_rol' => 1,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);
        $tesoreria = (new Usuario)->forceFill([
            'id_rol' => 4,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);
        $coordinador = (new Usuario)->forceFill([
            'id_rol' => 2,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);

        $this->assertTrue($service->usuarioPuede($directiva, 'gamificacion', 'crear'));
        $this->assertFalse($service->usuarioPuede($tesoreria, 'gamificacion', 'crear'));
        $this->assertFalse($service->usuarioPuede($coordinador, 'gamificacion', 'crear'));
    }

    public function test_web_only_user_gets_portal_403_and_backup_url_is_denied_without_permission(): void
    {
        $webOnlyUser = (new Usuario)->forceFill([
            'id_usuario' => 3,
            'id_rol' => 3,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);
        $dashboardUser = (new Usuario)->forceFill([
            'id_usuario' => 1,
            'id_rol' => 1,
            'estatus' => Usuario::ESTATUS_ACTIVO,
        ]);

        $this->actingAs($webOnlyUser)
            ->get('/dashboard')
            ->assertForbidden()
            ->assertSeeText('Tu perfil no tiene acceso al panel web. Usa la aplicación móvil o el portal correspondiente.');
        $this->actingAs($webOnlyUser)
            ->getJson('/respaldos')
            ->assertForbidden()
            ->assertJson(['message' => 'No tienes permiso para ver este módulo.']);
        $this->actingAs($dashboardUser)
            ->get('/respaldos')
            ->assertRedirect(route('dashboard'));

        $dashboardUser->estatus = Usuario::ESTATUS_INACTIVO;
        $this->actingAs($dashboardUser)
            ->get('/respaldos')
            ->assertForbidden()
            ->assertSeeText('Tu perfil no tiene acceso al panel web. Usa la aplicación móvil o el portal correspondiente.');
    }
}
