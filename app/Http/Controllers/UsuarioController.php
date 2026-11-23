<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Voluntario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:usuarios,ver')->only('index');
        $this->middleware('permiso:usuarios,crear')->only('create', 'store');
        $this->middleware('permiso:usuarios,editar')->only('edit', 'update');
        $this->middleware('permiso:usuarios,eliminar')->only('destroy');
    }

    public function index()
    {
        $usuarios = DB::table('usuarios as u')
            ->leftJoin('roles as r', 'u.id_rol', '=', 'r.id_rol')
            ->select(
                'u.id_usuario',
                'u.id_rol',
                'u.nombre',
                'u.apellido',
                'u.email',
                'u.estatus',
                'u.sede',
                'u.intentos_fallidos',
                'u.fecha_bloqueo_temporal',
                'u.ultimo_acceso',
                'u.created_at',
                'r.nombre as rol'
            )
            ->orderBy('u.id_usuario')
            ->get();

        return view('usuarios', [
            'usuarios' => $usuarios,
            'roles' => Rol::orderBy('nombre')->get(),
            'totalUsuarios' => $usuarios->count(),
            'usuariosActivos' => $usuarios->where('estatus', Usuario::ESTATUS_ACTIVO)->count(),
            'usuariosPendientes' => $usuarios->where('estatus', Usuario::ESTATUS_PENDIENTE)->count(),
            'usuariosBloqueados' => $usuarios->where('estatus', Usuario::ESTATUS_BLOQUEADO)->count(),
            'usuariosInactivos' => $usuarios->where('estatus', Usuario::ESTATUS_INACTIVO)->count(),
        ]);
    }

    public function create()
    {
        return view('usuarios_nuevo', ['roles' => Rol::orderBy('nombre')->get()]);
    }

    public function store(UsuarioRequest $request)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos): void {
            $usuario = Usuario::create([
                'id_rol' => $datos['id_rol'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'contrasena_hash' => Hash::make($datos['password']),
                'estatus' => $datos['estatus'],
                'sede' => $datos['sede'] ?? null,
                'intentos_fallidos' => 0,
            ]);

            $this->provisionarPerfilVoluntario($usuario, (int) $datos['id_rol']);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(string $usuario)
    {
        return view('usuarios_editar', [
            'usuario' => Usuario::findOrFail($usuario),
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function update(UsuarioRequest $request, string $usuario)
    {
        $usuario = Usuario::findOrFail($usuario);
        $datos = $request->validated();
        $actualizacion = [
            'id_rol' => $datos['id_rol'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'email' => $datos['email'],
            'estatus' => $datos['estatus'],
            'sede' => $datos['sede'] ?? null,
        ];

        if (! empty($datos['password'])) {
            $actualizacion['contrasena_hash'] = Hash::make($datos['password']);
        }

        DB::transaction(function () use ($usuario, $actualizacion, $datos): void {
            $usuario->update($actualizacion);
            $this->provisionarPerfilVoluntario($usuario, (int) $datos['id_rol']);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(string $usuario)
    {
        $usuario = Usuario::findOrFail($usuario);

        if ((int) $usuario->getKey() === (int) auth()->id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        try {
            $logico = DB::transaction(function () use ($usuario): bool {
                $idUsuario = $usuario->getKey();

                if ($this->tieneDatosCriticos($idUsuario)) {
                    $usuario->update(['estatus' => Usuario::ESTATUS_INACTIVO]);
                    DB::table('voluntarios')->where('id_usuario', $idUsuario)->update(['activo' => false]);

                    return true;
                }

                $idsVoluntario = DB::table('voluntarios')
                    ->where('id_usuario', $idUsuario)
                    ->pluck('id_voluntario');

                if ($idsVoluntario->isNotEmpty()) {
                    DB::table('asignaciones_voluntario')->whereIn('id_voluntario', $idsVoluntario)->delete();
                    DB::table('asistencia_voluntarios')->whereIn('id_voluntario', $idsVoluntario)->delete();
                    DB::table('voluntarios')->whereIn('id_voluntario', $idsVoluntario)->delete();
                }

                DB::table('notificaciones')->where('id_usuario', $idUsuario)->delete();
                DB::table('historial_accesos')->where('id_usuario', $idUsuario)->delete();
                DB::table('recuperacion_contrasena')->where('id_usuario', $idUsuario)->delete();
                $usuario->delete();

                return false;
            });
        } catch (QueryException $exception) {
            Log::error('No se pudo eliminar el usuario.', [
                'id_usuario' => $usuario->getKey(),
                'exception' => $exception,
            ]);

            return redirect()->route('usuarios.index')
                ->with('error', 'No se pudo eliminar el usuario porque existen registros relacionados.');
        }

        return redirect()->route('usuarios.index')->with(
            'success',
            $logico
                ? 'El usuario tiene datos críticos y se desactivó en lugar de eliminarlo.'
                : 'Usuario eliminado correctamente.'
        );
    }

    public function desbloquear(string $usuario)
    {
        Usuario::whereKey($usuario)->where('estatus', Usuario::ESTATUS_BLOQUEADO)->update([
            'estatus' => Usuario::ESTATUS_ACTIVO,
            'intentos_fallidos' => 0,
            'fecha_bloqueo_temporal' => null,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desbloqueado correctamente.');
    }

    public function aprobar(string $usuario)
    {
        DB::transaction(function () use ($usuario): void {
            $registro = Usuario::findOrFail($usuario);
            if ($registro->estatus !== Usuario::ESTATUS_PENDIENTE) {
                return;
            }
            $registro->update(['estatus' => Usuario::ESTATUS_ACTIVO]);

            $perfil = Voluntario::query()->where('id_usuario', $registro->id_usuario)->first();
            if ($perfil) {
                $perfil->update([
                    'activo' => true,
                    'fecha_ingreso' => $perfil->fecha_ingreso ?? now()->toDateString(),
                ]);
            } elseif ($registro->esRol('voluntario')) {
                $this->provisionarPerfilVoluntario($registro, (int) $registro->id_rol);
            }
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario aprobado correctamente.');
    }

    private function tieneDatosCriticos(int|string $idUsuario): bool
    {
        $relaciones = [
            ['campanas', 'id_usuario_creador'],
            ['donaciones', 'id_usuario_registrador'],
            ['beneficiarios', 'id_usuario_registrador'],
            ['entregas', 'id_usuario_responsable'],
            ['avances_campana', 'id_usuario'],
            ['movimientos_inventario', 'id_usuario'],
            ['reconocimientos', 'id_usuario_emisor'],
        ];

        foreach ($relaciones as [$tabla, $columna]) {
            if (DB::table($tabla)->where($columna, $idUsuario)->exists()) {
                return true;
            }
        }

        return DB::table('asistencia_voluntarios as av')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'av.id_voluntario')
            ->where('v.id_usuario', $idUsuario)
            ->where('av.presente', true)
            ->exists();
    }

    private function provisionarPerfilVoluntario(Usuario $usuario, int $idRol): void
    {
        $esVoluntario = Rol::query()
            ->whereKey($idRol)
            ->where('slug', 'voluntario')
            ->exists();

        if (! $esVoluntario) {
            return;
        }

        Voluntario::query()->firstOrCreate(
            ['id_usuario' => $usuario->getKey()],
            [
                'telefono_contacto' => null,
                'zona_asignada' => 'ambas',
                'disponibilidad' => 'bajo_demanda',
                'activo' => true,
                'fecha_ingreso' => now()->toDateString(),
            ]
        );
    }
}
