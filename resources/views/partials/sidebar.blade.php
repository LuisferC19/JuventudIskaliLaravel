@php
  $secciones = [
    'Gestión' => [
      'campanas' => ['Campañas', 'Campanas.png'],
      'donadores' => ['Donadores', 'patrocinador.png'],
      'donaciones' => ['Donaciones', 'donaciones.png'],
      'beneficiarios' => ['Beneficiarios', 'Beneficiarios.png'],
      'entregas' => ['Entregas', 'Entregas.png'],
      'usuarios' => ['Usuarios', 'Usuarios.png'],
    ],
    'Control' => [
      'inventario' => ['Inventario', 'inventario.png'],
      'voluntarios' => ['Voluntarios', 'Voluntarios.png'],
      'respaldos' => ['Respaldos', 'respaldo_base_datos.png'],
    ],
    'Extras' => [
      'gamificacion' => ['Gamificación', 'gamificacion_trofeo.png'],
      'planning' => ['Planning', 'planificacion.png'],
      'reportes' => ['Reportes', 'Reportes.png'],
      'notificaciones' => ['Notificaciones', 'Modulo_Notificaciones.png'],
    ],
  ];
@endphp
<nav class="sidebar">
  <div class="sidebar-logo">
    <img src="{{ asset('img/Mascota_Iski_1.jpeg') }}" alt="Iskali" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-bottom:8px">
    <span>Juventud Iskali</span>
    <small>Sistema Integral</small>
  </div>
  <div class="nav-section">
    @can('permiso', ['dashboard', 'ver'])
      <div class="nav-label">Principal</div>
      <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <img src="{{ asset('iconos/Dashboard.png') }}" alt="Dashboard" style="width:20px;height:20px;">
        Dashboard
      </a>
    @endcan
    @foreach ($secciones as $seccion => $modulos)
      @php
        $modulosVisibles = array_filter(
          $modulos,
          fn ($datos, $modulo) => auth()->user()->can('permiso', [$modulo, 'ver']),
          ARRAY_FILTER_USE_BOTH
        );
        $mostrarHistorial = $seccion === 'Extras' && auth()->user()->can('permiso', ['usuarios', 'ver']);
      @endphp
      @if (count($modulosVisibles) || $mostrarHistorial)
        <div class="nav-label">{{ $seccion }}</div>
        @foreach ($modulosVisibles as $modulo => [$etiqueta, $icono])
          @can('permiso', [$modulo, 'ver'])
            <a class="nav-item {{ request()->routeIs($modulo.'.*') ? 'active' : '' }}" href="{{ route($modulo.'.index') }}">
              <img src="{{ asset('iconos/'.$icono) }}" alt="{{ $etiqueta }}" style="width:20px;height:20px;">
              {{ $etiqueta }}
            </a>
          @endcan
        @endforeach
      @endif
    @endforeach
    @can('permiso', ['usuarios', 'ver'])
      <a class="nav-item" href="#" onclick="showToast('Módulo Historial en construcción'); return false;">
        <img src="{{ asset('iconos/Historial_De_Acesso.png') }}" alt="Historial accesos" style="width:20px;height:20px;">
        Historial accesos
      </a>
    @endcan
  </div>
  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="user-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->nombre, 0, 1).mb_substr(Auth::user()->apellido, 0, 1)) }}</div>
      <div class="user-info">
        <span>{{ Auth::user()->nombre }} {{ Auth::user()->apellido }}</span>
        <small>{{ Auth::user()->rol?->nombre ?? 'Sin rol' }}</small>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn-secondary btn-sm" type="submit" style="display:flex;width:100%;margin-top:.6rem;align-items:center;justify-content:center;gap:.45rem">
        Cerrar sesión
      </button>
    </form>
  </div>
</nav>
