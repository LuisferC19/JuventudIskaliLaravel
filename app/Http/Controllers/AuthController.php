<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\HistorialAcceso;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
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
}
