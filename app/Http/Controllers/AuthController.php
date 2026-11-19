<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use App\Http\Requests\RestablecerContrasenaRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\HistorialAcceso;
use App\Models\RecuperacionContrasena;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\NotificacionService;

class AuthController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($credenciales['email']));
        $usuario = Usuario::where('email', $email)->first();

        if (! $usuario) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->onlyInput('email');
        }

        if ($usuario->estaBloqueado()) {
            return back()
                ->withErrors(['email' => 'No fue posible iniciar sesión. Si tu cuenta está bloqueada, contacta al administrador.'])
                ->onlyInput('email');
        }

        $hashContrasena = (string) $usuario->contrasena_hash;
        $informacionContrasena = password_get_info($hashContrasena);
        $esHash = $informacionContrasena['algoName'] !== 'unknown'
            || str_starts_with($hashContrasena, '$2b$');
        $autenticado = $esHash
            ? Auth::validate([
                'email' => $email,
                'password' => $credenciales['password'],
            ])
            : hash_equals((string) $usuario->contrasena_hash, $credenciales['password']);

        if (! $autenticado) {
            $usuario->increment('intentos_fallidos');
            if ($usuario->intentos_fallidos >= 5) {
                $usuario->update([
                    'estatus' => Usuario::ESTATUS_BLOQUEADO,
                    'fecha_bloqueo_temporal' => now(),
                ]);
                app(NotificacionService::class)->cuentaBloqueada(
                    (int) $usuario->id_usuario,
                    trim($usuario->nombre.' '.$usuario->apellido),
                    (string) $usuario->email
                );
            }
            HistorialAcceso::create([
                'id_usuario' => $usuario->id_usuario,
                'tipo_accion' => 'fallo_login',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'exitoso' => false,
            ]);

            $intentosRestantes = max(0, 5 - $usuario->intentos_fallidos);
            $mensaje = $intentosRestantes === 0
                ? 'Cuenta bloqueada por demasiados intentos fallidos. Contacta al administrador.'
                : "Credenciales incorrectas. Te quedan {$intentosRestantes} intento(s).";

            return back()->withErrors(['email' => $mensaje])->onlyInput('email');
        }

        if ($usuario->estaPendiente()) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por la Directiva.'])
                ->onlyInput('email');
        }

        if ($usuario->estaInactivo()) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.'])
                ->onlyInput('email');
        }

        if (! $esHash) {
            $usuario->contrasena_hash = Hash::make($credenciales['password']);
        }
        Auth::login($usuario);
        $usuario->intentos_fallidos = 0;
        $usuario->ultimo_acceso = now();
        $usuario->save();

        HistorialAcceso::create([
            'id_usuario' => $usuario->id_usuario,
            'tipo_accion' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'exitoso' => true,
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()) {
            HistorialAcceso::create([
                'id_usuario' => $request->user()->id_usuario,
                'tipo_accion' => 'logout',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'exitoso' => true,
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function registro(): View
    {
        return view('registro');
    }

    public function register(Request $request): RedirectResponse
    {
        return $this->crearCuenta($request);
    }

    public function crearCuenta(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/\d/'],
            'password_confirm' => ['required', 'same:password'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before:today'],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'zona_asignada' => ['required', Rule::in(['san_martin', 'tlaxcala'])],
        ]);

        $rolVoluntario = DB::table('roles')->where('slug', 'voluntario')->value('id_rol');
        if ($rolVoluntario === null) {
            return back()->withInput($request->only(
                'nombre',
                'apellido',
                'email',
                'fecha_nacimiento',
                'telefono_contacto',
                'zona_asignada'
            ))
                ->withErrors(['email' => 'No se pudo solicitar el acceso. El rol Voluntario no está configurado.']);
        }

        $usuario = DB::transaction(function () use ($datos, $rolVoluntario): Usuario {
            $usuario = Usuario::create([
                'id_rol' => $rolVoluntario,
                'nombre' => trim($datos['nombre']),
                'apellido' => trim($datos['apellido']),
                'email' => $datos['email'],
                'contrasena_hash' => Hash::make($datos['password']),
                'estatus' => Usuario::ESTATUS_PENDIENTE,
                'intentos_fallidos' => 0,
            ]);

            DB::table('voluntarios')->insert([
                'id_usuario' => $usuario->id_usuario,
                'telefono_contacto' => $datos['telefono_contacto'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'zona_asignada' => $datos['zona_asignada'],
                'disponibilidad' => 'bajo_demanda',
                'activo' => false,
                'fecha_ingreso' => null,
            ]);

            return $usuario;
        });

        $directivos = DB::table('usuarios')
            ->whereIn('id_rol', function ($query): void {
                $query->select('id_rol')
                    ->from('roles')
                    ->where('slug', 'directiva');
            })
            ->pluck('id_usuario');
        app(NotificacionService::class)->notificarUsuarios(
            $directivos,
            'sistema',
            'Nueva solicitud de voluntariado',
            'La solicitud de '.trim($usuario->nombre.' '.$usuario->apellido).' está pendiente de aprobación.'
        );

        return redirect()->route('registro')->with('success', 'Tu solicitud fue enviada correctamente. Un administrador revisará tu acceso.');
    }

    public function recuperarPassword(): View
    {
        return view('recuperar_password');
    }

    public function solicitarRecuperacion(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);
        $usuario = Usuario::where('email', $datos['email'])->first();

        $redirect = redirect()->route('recuperar_password')
            ->with('success', 'Si el correo está asociado a una cuenta, la solicitud quedó registrada. El envío del enlace de recuperación aún no está configurado.');

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            DB::table('recuperacion_contrasena')->insert([
                'id_usuario' => $usuario->id_usuario,
                'token' => $token,
                'fecha_solicitud' => now(),
                'fecha_expiracion' => now()->addHour(),
                'usado' => false,
            ]);
            HistorialAcceso::create([
                'id_usuario' => $usuario->id_usuario,
                'tipo_accion' => 'recuperacion',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'exitoso' => true,
            ]);

        }

        return $redirect;
    }

    public function formularioRestablecer(string $token): View
    {
        abort_unless($this->tokenRecuperacionValido($token), 404, 'El enlace de recuperación no es válido o ya expiró.');

        return view('restablecer_password', compact('token'));
    }

    public function restablecerContrasena(RestablecerContrasenaRequest $request, string $token): RedirectResponse
    {
        $datos = $request->validated();
        $actualizada = DB::transaction(function () use ($token, $datos): bool {
            $recuperacion = DB::table('recuperacion_contrasena')
                ->where('token', $token)
                ->where('usado', false)
                ->where('fecha_expiracion', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $recuperacion) {
                return false;
            }

            Usuario::where('id_usuario', $recuperacion->id_usuario)
                ->update(['contrasena_hash' => Hash::make($datos['password'])]);

            DB::table('recuperacion_contrasena')
                ->where('id_usuario', $recuperacion->id_usuario)
                ->where('usado', false)
                ->update(['usado' => true]);

            return true;
        });

        if (! $actualizada) {
            return redirect()->route('recuperar_password')
                ->withErrors(['token' => 'El enlace de recuperación no es válido o ya expiró.']);
        }

        return redirect()->route('login')->with('success', 'La contraseña se actualizó correctamente. Ya puedes iniciar sesión.');
    }

    private function tokenRecuperacionValido(string $token): bool
    {
        return DB::table('recuperacion_contrasena')
            ->where('token', $token)
            ->where('usado', false)
            ->where('fecha_expiracion', '>', now())
            ->exists();
    }

}
