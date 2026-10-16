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
        $this->middleware('permiso:usuarios,editar')->only('edit', 'update', 'desbloquear');
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
                'u.activo',
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
            'usuariosActivos' => $usuarios->where('activo', 1)->count(),
            'usuariosInactivos' => $usuarios->where('activo', 0)->count(),
        ]);
    }

    public function create()
    {
        return view('usuarios_nuevo', ['roles' => Rol::orderBy('nombre')->get()]);
    }

    public function store(UsuarioRequest $request)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $request): void {
            $usuario = Usuario::create([
                'id_rol' => $datos['id_rol'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'contrasena_hash' => Hash::make($datos['password']),
                'activo' => $request->boolean('activo'),
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
            'activo' => $request->boolean('activo'),
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
                    $usuario->update(['activo' => false]);
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
                DB::table('recuperacion_contrasenas')->where('id_usuario', $idUsuario)->delete();
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
        Usuario::whereKey($usuario)->update([
            'intentos_fallidos' => 0,
            'fecha_bloqueo_temporal' => null,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desbloqueado correctamente.');
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
            ->where('nombre', 'Voluntario')
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
