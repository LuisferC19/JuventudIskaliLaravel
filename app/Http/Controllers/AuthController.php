<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

        if (! $usuario->activo) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.'])
                ->onlyInput('email');
        }

        if ($usuario->intentos_fallidos >= 5) {
            return back()
                ->withErrors(['email' => 'Cuenta bloqueada por demasiados intentos fallidos. Contacta al administrador.'])
                ->onlyInput('email');
        }

        $hashContrasena = (string) $usuario->contrasena_hash;
        $informacionContrasena = password_get_info($hashContrasena);
        $esHash = $informacionContrasena['algoName'] !== 'unknown'
            || str_starts_with($hashContrasena, '$2b$');
        $autenticado = $esHash
            ? Auth::attempt([
                'email' => $email,
                'password' => $credenciales['password'],
                'activo' => 1,
            ])
            : hash_equals((string) $usuario->contrasena_hash, $credenciales['password']);

        if (! $autenticado) {
            $usuario->increment('intentos_fallidos');
            if ($usuario->intentos_fallidos >= 5) {
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

        if (! $esHash) {
            $usuario->contrasena_hash = Hash::make($credenciales['password']);
            Auth::login($usuario);
        }

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
        ]);

        $rolVoluntario = DB::table('roles')->whereRaw('LOWER(nombre) = ?', ['voluntario'])->value('id_rol');
        if ($rolVoluntario === null) {
            return back()->withInput($request->only('nombre', 'apellido', 'email'))
                ->withErrors(['email' => 'No se pudo solicitar el acceso. El rol Voluntario no está configurado.']);
        }

        Usuario::create([
            'id_rol' => $rolVoluntario,
            'nombre' => trim($datos['nombre']),
            'apellido' => trim($datos['apellido']),
            'email' => $datos['email'],
            'contrasena_hash' => Hash::make($datos['password']),
            'activo' => false,
            'intentos_fallidos' => 0,
        ]);

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

        if ($usuario) {
            DB::table('recuperacion_contrasena')->insert([
                'id_usuario' => $usuario->id_usuario,
                'token' => bin2hex(random_bytes(32)),
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

        return redirect()->route('recuperar_password')
            ->with('success', 'Si el correo está asociado a una cuenta, recibirás instrucciones para restablecer la contraseña.');
    }

}
