@extends('layouts.app')
@section('title', 'Restablecer contraseña | Juventud Iskali')
@section('content')
<div class="login-container">
  <div class="login-header">
    <div class="login-brand">Juventud Iskali<span>.</span></div>
    <h1 class="login-title">Restablecer contraseña</h1>
    <p class="login-subtitle">Elige una nueva contraseña para tu cuenta.</p>
  </div>

  @if ($errors->any())
    <div class="error-message" role="alert">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('recuperar_password.update', ['token' => $token]) }}">
    @csrf
    <div class="form-group">
      <label class="form-label" for="password">Nueva contraseña</label>
      <input class="form-input" type="password" id="password" name="password" required autocomplete="new-password">
      @error('password')<div class="error-message">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirmar contraseña</label>
      <input class="form-input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
      @error('password_confirm')<div class="error-message">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn-login">Guardar contraseña</button>
  </form>

  <div class="login-footer">
    <a href="{{ route('login') }}">← Volver al inicio de sesión</a>
  </div>
</div>
@endsection
