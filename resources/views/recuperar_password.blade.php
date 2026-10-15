@extends('layouts.app')
@section('title', 'Recuperar contraseña | Juventud Iskali')
@section('content')
<div class="login-container">
  <div class="login-header">
    <div class="login-brand">Juventud Iskali<span>.</span></div>
    <div class="recover-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
      </svg>
    </div>
    <h1 class="login-title">Recuperar contraseña</h1>
    <p class="login-subtitle">Te enviaremos un enlace para restablecer tu contraseña.</p>
  </div>

  <div class="step-chips">
    <div class="step-chip"><span class="step-num">1</span> Ingresa tu correo</div>
    <div class="step-chip"><span class="step-num">2</span> Revisa tu bandeja</div>
    <div class="step-chip"><span class="step-num">3</span> Restablece</div>
  </div>

  
      <form id="recoverForm" method="POST" action="{{ route('recuperar_password') }}">
        @csrf
            <div class="form-group">
        <label class="form-label" for="email">Correo electrónico registrado</label>
        <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="tu@correo.com" required>
        @error('email')<div class="error-message">{{ $message }}</div>@enderror
      </div>
      <button type="submit" class="btn-login">Enviar enlace de recuperación</button>
    </form>
  
  <p class="login-link-note"><a href="{{ route('login') }}">← Regresar al inicio de sesión</a></p>

  <div class="login-footer">
    <a href="{{ route('landing') }}">← Volver al inicio</a>
  </div>
</div>
@endsection
