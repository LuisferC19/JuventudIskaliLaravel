@extends('layouts.app')
@section('title', 'Registro | Juventud Iskali')
@section('content')
<div class="login-container" style="max-width:480px;">
  <div class="login-header">
    <div class="login-brand">Juventud Iskali<span>.</span></div>
    <h1 class="login-title">Crear cuenta</h1>
    <p class="login-subtitle">Únete al equipo de Fundación Iskali</p>
  </div>

  <div class="register-note">
    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
      <circle cx="8" cy="8" r="6"/>
      <path d="M8 6V8M8 10V10.5"/>
    </svg>
    <span>El acceso al sistema es aprobado por un administrador. Al registrarte, tu cuenta quedará pendiente de activación.</span>
  </div>

  
  
  <form method="POST" action="{{ route('registro') }}" id="registerForm">
    @csrf
    
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="nombre">Nombre(s)</label>
        <input type="text" id="nombre" name="nombre" class="form-input" value="{{ old('nombre') }}" placeholder="Ana" required>
        @error('nombre')<div class="error-message">{{ $message }}</div>@enderror
      </div>
      <div class="form-group">
        <label class="form-label" for="apellido">Apellido(s)</label>
        <input type="text" id="apellido" name="apellido" class="form-input" value="{{ old('apellido') }}" placeholder="García" required>
        @error('apellido')<div class="error-message">{{ $message }}</div>@enderror
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Correo Electrónico</label>
      <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="tu@correo.com" required>
      @error('email')<div class="error-message">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Contraseña</label>
      <input type="password" id="password" name="password" class="form-input" placeholder="Mínimo 8 caracteres" required>
      @error('password')<div class="error-message">{{ $message }}</div>@enderror
      <p class="password-hint">Usa al menos 8 caracteres, una mayúscula y un número.</p>
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirmar contraseña</label>
      <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Repite tu contraseña" required>
    </div>

    <button type="submit" class="btn-login">Solicitar acceso</button>

    <p class="login-link-note">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
  </form>

  <div class="login-footer">
    <a href="{{ route('landing') }}">← Volver al inicio</a>
  </div>
</div>

<script>
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
  const pwd = document.getElementById('password').value;
  const confirm = document.getElementById('password_confirm').value;
  if (pwd !== confirm) {
    e.preventDefault();
    alert('Las contraseñas no coinciden. Por favor verifica.');
  }
});
</script>
@endsection
