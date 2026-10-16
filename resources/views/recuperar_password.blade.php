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
    <p class="login-subtitle">Solicita un enlace y restablece tu contraseña sin correo electrónico.</p>
  </div>

  @if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif
  @if (session('reset_url'))
    <div class="register-note" role="status">
      <span>El sistema no envía correo. Usa este enlace privado para continuar:</span>
      <a href="{{ session('reset_url') }}">{{ session('reset_url') }}</a>
    </div>
  @endif

  
      @if (session('success'))
        <div class="success-box" role="status">{{ session('success') }}</div>
      @else
      <form id="recoverForm" method="POST" action="{{ route('recuperar_password.store') }}">
        @csrf
            <div class="form-group">
        <label class="form-label" for="email">Correo electrónico registrado</label>
        <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="tu@correo.com" required>
        @error('email')<div class="error-message">{{ $message }}</div>@enderror
      </div>
      <button type="submit" class="btn-login">Generar enlace de recuperación</button>
    </form>
      @endif
  
  <p class="login-link-note"><a href="{{ route('login') }}">← Regresar al inicio de sesión</a></p>

  <div class="login-footer">
    <a href="{{ route('landing') }}">← Volver al inicio</a>
  </div>
</div>
@endsection
