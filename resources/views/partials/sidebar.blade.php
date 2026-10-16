@php
  $secciones = [
    'Gestión' => [
      'campanas' => 'Campañas',
      'donadores' => 'Donadores',
      'donaciones' => 'Donaciones',
      'beneficiarios' => 'Beneficiarios',
      'entregas' => 'Entregas',
      'usuarios' => 'Usuarios',
    ],
    'Control' => [
      'inventario' => 'Inventario',
      'voluntarios' => 'Voluntarios',
      'respaldos' => 'Respaldos',
    ],
    'Extras' => [
      'gamificacion' => 'Gamificación',
      'planning' => 'Planning',
      'reportes' => 'Reportes',
      'notificaciones' => 'Notificaciones',
    ],
  ];
  $iconos = [
    'dashboard' => 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
    'campanas' => 'M4 21V4m0 1h12l-2 4 2 4H4',
    'donadores' => 'M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
    'donaciones' => 'M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11z',
    'beneficiarios' => 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm10 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    'entregas' => 'm3 7 9-4 9 4-9 4-9-4zm0 0v10l9 4 9-4V7m-9 4v10',
    'usuarios' => 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm10 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    'inventario' => 'm12 3 9 5-9 5-9-5 9-5zm-9 5v9l9 5 9-5V8m-9 5v9',
    'voluntarios' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-7 9a7 7 0 0 1 14 0m-7-7v6m-3-3h6',
    'respaldos' => 'M12 3 4 7v10l8 4 8-4V7l-8-4zm0 0v8m-8-4 8 4 8-4',
    'gamificacion' => 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3z',
    'planning' => 'M4 5h16v16H4zM8 3v4m8-4v4M4 10h16',
    'reportes' => 'M4 20V10m8 10V4m8 16v-7',
    'notificaciones' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4',
  ];
@endphp
<nav class="sidebar">
  <div class="sidebar-logo">
    <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/img/Mascota_Iski_1.jpeg') }}" alt="Iskali" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-bottom:8px">
    <span>Juventud Iskali</span>
    <small>Sistema Integral</small>
  </div>
  <div class="nav-section">
    <div class="nav-label">Principal</div>
    @can('permiso', ['dashboard', 'ver'])
      <a class="nav-item" href="{{ route('dashboard') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $iconos['dashboard'] }}"/></svg>
        Dashboard
      </a>
    @endcan
    @foreach ($secciones as $seccion => $modulos)
      <div class="nav-label">{{ $seccion }}</div>
      @foreach ($modulos as $modulo => $etiqueta)
        @can('permiso', [$modulo, 'ver'])
          <a class="nav-item" href="{{ route($modulo . '.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="{{ $iconos[$modulo] }}"/>
            </svg>
            {{ $etiqueta }}
          </a>
        @endcan
        @endforeach
    @endforeach
  </div>
  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="user-avatar">{{ mb_substr(Auth::user()->nombre, 0, 1) }}{{ mb_substr(Auth::user()->apellido, 0, 1) }}</div>
      <div class="user-info">
        <span>{{ Auth::user()->nombre }} {{ Auth::user()->apellido }}</span>
        <small>{{ Auth::user()->rol?->nombre ?? 'Sin rol' }}</small>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn-secondary btn-sm" type="submit" style="display:flex;width:100%;margin-top:.6rem;align-items:center;justify-content:center;gap:.45rem">
        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
        Cerrar sesión
      </button>
    </form>
  </div>
</nav>
