@php
  $notificacionesNoLeidas = \Illuminate\Support\Facades\DB::table('notificaciones')
      ->where(function ($consulta) {
          $consulta->where('id_usuario', Auth::id());
      })
      ->where('leida', false)
      ->count();
@endphp
<header class="topbar">
  <div class="topbar-title">@yield('page-title', 'Juventud Iskali')</div>
  <div class="topbar-actions">
    <a class="topbar-button" href="{{ route('dashboard') }}" aria-label="Inicio">Inicio</a>
    <a class="topbar-button" href="{{ route('notificaciones.index') }}" aria-label="Notificaciones">
      Notificaciones <span>{{ $notificacionesNoLeidas }}</span>
    </a>
    <span>{{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
      @csrf
      <button class="topbar-button topbar-logout" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 17l5-5-5-5M15 12H3"></path>
          <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path>
        </svg>
        <span>Salir</span>
      </button>
    </form>
  </div>
</header>
@if (session('success'))
  <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
