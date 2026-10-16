<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response;
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

        $informacionContrasena = password_get_info((string) $usuario->contrasena_hash);
        $esHash = $informacionContrasena['algoName'] !== 'unknown';
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

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/\d/'],
            'password_confirm' => ['required', 'same:password'],
        ], [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula y un número.',
            'password_confirm.same' => 'Las contraseñas no coinciden.',
        ]);

        $rol = Rol::query()->whereRaw('LOWER(nombre) = ?', ['voluntario'])->first();
        if (! $rol) {
            return back()->withInput($request->only('nombre', 'apellido', 'email'))
                ->withErrors(['email' => 'No se pudo solicitar el acceso. El rol Voluntario no está configurado.']);
        }

        Usuario::create([
            'id_rol' => $rol->id_rol,
            'nombre' => trim($validated['nombre']),
            'apellido' => trim($validated['apellido']),
            'email' => mb_strtolower(trim($validated['email'])),
            'contrasena_hash' => Hash::make($validated['password']),
            'activo' => false,
            'intentos_fallidos' => 0,
        ]);

        return redirect('/registro')->with('success', 'Tu solicitud fue enviada correctamente. Un administrador revisará tu acceso.');
    }

    public function solicitarRecuperacion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $usuario = Usuario::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $response = redirect('/recuperar-password')->with(
            'success',
            'Si el correo está asociado a una cuenta, se generó un enlace de recuperación.'
        );

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            RecuperacionContrasena::query()
                ->where('id_usuario', $usuario->id_usuario)
                ->where('usado', false)
                ->update(['usado' => true]);
            RecuperacionContrasena::create([
                'id_usuario' => $usuario->id_usuario,
                'token' => hash('sha256', $token),
                'fecha_solicitud' => now(),
                'fecha_expiracion' => now()->addHour(),
                'usado' => false,
            ]);

            $response->with('reset_url', url('/recuperar-password/reset?token='.$token));
        }

        return $response;
    }

    public function mostrarRestablecimiento(Request $request): View
    {
        $token = (string) $request->query('token', '');
        $valid = strlen($token) === 64 && RecuperacionContrasena::query()
            ->where('token', hash('sha256', $token))
            ->where('usado', false)
            ->where('fecha_expiracion', '>', now())
            ->exists();

        return view('recuperar_password', compact('token', 'valid'));
    }

    public function restablecerContrasena(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/\d/'],
            'password_confirm' => ['required', 'same:password'],
        ], [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula y un número.',
            'password_confirm.same' => 'Las contraseñas no coinciden.',
        ]);

        $recuperacion = RecuperacionContrasena::query()
            ->where('token', hash('sha256', $validated['token']))
            ->where('usado', false)
            ->where('fecha_expiracion', '>', now())
            ->first();

        if (! $recuperacion) {
            return redirect('/recuperar-password')->withErrors([
                'token' => 'El enlace de recuperación no es válido o ya venció.',
            ]);
        }

        DB::transaction(function () use ($recuperacion, $validated, $request): void {
            $usuario = Usuario::query()->findOrFail($recuperacion->id_usuario);
            $usuario->contrasena_hash = Hash::make($validated['password']);
            $usuario->save();
            $recuperacion->usado = true;
            $recuperacion->save();

            HistorialAcceso::create([
                'id_usuario' => $usuario->id_usuario,
                'tipo_accion' => 'recuperacion',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'exitoso' => true,
            ]);
        });

        return redirect()->route('login')->with('success', 'La contraseña se restableció. Ya puedes iniciar sesión.');
    }
}
