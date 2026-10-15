@extends('layouts.app')
@section('title', 'Iniciar sesión | Juventud Iskali')
@section('content')
<div class="login-container">
  <div class="login-header">
    <div class="login-brand">Juventud Iskali<span>.</span></div>
    <h1 class="login-title">Iniciar Sesión</h1>
  </div>

  <!-- ══ KOALA ISKI ══════════════════════════════════════════════════════════ -->
  <div style="text-align:center; position:relative; margin-bottom:-20px; z-index:10;">

    <div id="iskiBurbuja" style="
      display:none;
      position:absolute;
      bottom:158px;
      left:50%;
      transform:translateX(-50%);
      background:#ffffff;
      border:2px solid #1D9E75;
      border-radius:12px;
      padding:10px 16px;
      font-size:13px;
      color:#085041;
      max-width:230px;
      min-width:140px;
      text-align:center;
      box-shadow:0 4px 16px rgba(0,0,0,0.12);
      z-index:20;
      font-family:'DM Sans', sans-serif;
      line-height:1.5;
    ">
      <span id="iskiTexto">...</span>
      <div style="
        position:absolute;
        bottom:-10px;
        left:50%;
        transform:translateX(-50%);
        width:0; height:0;
        border-left:8px solid transparent;
        border-right:8px solid transparent;
        border-top:10px solid #1D9E75;
      "></div>
    </div>

    <img
      id="iski"
      src="./public/img/Mascota_Iski_1.jpeg"
      alt="Iski, mascota de Fundación Iskali"
      title="¡Haz clic para hablar con Iski!"
      style="
        width:150px;
        cursor:pointer;
        transition:transform 0.3s ease;
        filter:drop-shadow(0 4px 8px rgba(0,0,0,0.15));
      "
      onclick="iskiHabla('El usuario hizo clic en ti, salúdalo con entusiasmo y dile que puede iniciarle sesión')"
    >
  </div>
  <!-- ══ FIN KOALA ══ -->

  <form method="POST" action="{{ route('login.authenticate') }}" id="loginForm">

    @csrf

    @if ($errors->any())
      <div class="error-message" role="alert">
        {{ $errors->first() }}
      </div>
    @endif

    <div class="form-group">
      <label class="form-label" for="email">Correo Electrónico</label>
      <input
        type="email"
        id="email"
        name="email"
        class="form-input"
        placeholder="admin@iskali.com"
        value="{{ old('email') }}"
        required
      >
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Contraseña</label>
      <input
        type="password"
        id="password"
        name="password"
        class="form-input"
        placeholder="••••••••"
        required
      >
      <!-- Link recuperar contraseña -->
      <div class="forgot-link-wrap">
        <a href="{{ route('recuperar_password') }}" class="forgot-link">
          ¿Olvidaste tu contraseña?
        </a>
      </div>
    </div>

    <button type="submit" class="btn-login">
      Iniciar Sesión
    </button>

    <!-- Separador -->
    <div class="login-divider">
      <span>¿Eres nuevo?</span>
    </div>

    <!-- Botón Crear cuenta -->
    <a href="{{ route('registro') }}" class="btn-create-account">
      Crear nueva cuenta
    </a>

  </form>

  <div class="login-footer">
    <a href="{{ route('landing') }}">← Volver al inicio</a>
  </div>
</div>

<!-- ══ LÓGICA DE ISKI ═════════════════════════════════════════════════════ -->
<script>
let burbujaTimer;

function mostrarBurbuja(texto) {
  clearTimeout(burbujaTimer);
  document.getElementById('iskiTexto').textContent = texto;
  document.getElementById('iskiBurbuja').style.display = 'block';
  burbujaTimer = setTimeout(function () {
    document.getElementById('iskiBurbuja').style.display = 'none';
  }, 4500);
}

async function iskiHabla(contexto) {
  const img = document.getElementById('iski');
  img.style.transform = 'scale(1.1) rotate(-6deg)';
  setTimeout(function () { img.style.transform = 'scale(1)'; }, 350);
  mostrarBurbuja('...');
  try {
    const res = await fetch('./iski_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ mensaje: contexto })
    });
    const data = await res.json();
    mostrarBurbuja(data.respuesta || '¡Hola! Soy Iski 👋');
  } catch (e) {
    mostrarBurbuja('¡Hola! Soy Iski 👋');
  }
}

document.addEventListener('DOMContentLoaded', function () {

  setTimeout(function () {
    iskiHabla('Saluda al usuario que acaba de abrir el login de Fundación Iskali. Sé breve y alegre.');
  }, 1000);

  document.getElementById('email')?.addEventListener('focus', function () {
    iskiHabla('El usuario está escribiendo su correo, anímalo brevemente a continuar.');
  });

  document.getElementById('password')?.addEventListener('focus', function () {
    mostrarBurbuja('¡No miro la contraseña!');
  });

  document.getElementById('loginForm')?.addEventListener('submit', function () {
    iskiHabla('El usuario está intentando iniciar sesión, deséale mucha suerte de manera simpática.');
  });

});
</script>
<!-- ══ FIN LÓGICA ISKI ══ -->
@endsection
