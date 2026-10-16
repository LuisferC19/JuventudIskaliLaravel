@extends('layouts.app')
@section('title', 'Dashboard | Juventud Iskali')
@section('page-title', 'Dashboard')
@section('content')

<div style="display:flex;width:100%;">

  

  <div class="main">
    @include('partials.topbar')
    


    <div class="content">

      <!-- ══ Bienvenida ══ -->
      <div style="margin-bottom:1.1rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;
                   color:var(--text);margin-bottom:4px;">
          ¡Bienvenido, {{ auth()->user()->nombre }}!
          <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Saludar.png') }}" alt=""
               style="width:20px;height:20px;vertical-align:middle;margin-left:4px;">
        </h2>
        <p style="font-size:12.5px;color:var(--muted);">
          {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }} · Aquí está el resumen del sistema.
        </p>
      </div>

      <!-- ══ KPIs ══ -->
      <div class="kpi-grid dashboard-kpis">

        <div class="kpi">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Total_dinero.png') }}" alt="" style="width:16px;height:16px;">
            Total donado
          </div>
          <div class="kpi-value">${{ number_format($totalDonado, 2) }}</div>
          <div class="kpi-sub">acumulado histórico</div>
        </div>

        <div class="kpi amber">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/calendario.png') }}" alt="" style="width:16px;height:16px;">
            Este mes
          </div>
          <div class="kpi-value">${{ number_format($donadoEsteMes, 2) }}</div>
          <div class="kpi-sub">MXN donado</div>
        </div>

        <div class="kpi blue">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Beneficiarios.png') }}" alt="" style="width:16px;height:16px;">
            Beneficiarios
          </div>
          <div class="kpi-value">{{ $beneficiariosActivos }}</div>
          <div class="kpi-sub">activos registrados</div>
        </div>

        <div class="kpi">
          <div class="kpi-label">🚀 Campañas</div>
          <div class="kpi-value">{{ $campanasActivasTotal }}</div>
          <div class="kpi-sub">activas ahora</div>
        </div>

        <div class="kpi danger">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Entregas.png') }}" alt="" style="width:16px;height:16px;">
            Entregas
          </div>
          <div class="kpi-value">{{ $entregasCompletadas }}</div>
          <div class="kpi-sub">completadas</div>
        </div>

        <div class="kpi">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/patrocinador.png') }}" alt="" style="width:16px;height:16px;">
            Donadores
          </div>
          <div class="kpi-value">{{ $totalDonadores }}</div>
          <div class="kpi-sub">registrados</div>
        </div>

        <div class="kpi amber">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Voluntarios.png') }}" alt="" style="width:16px;height:16px;">
            Voluntarios
          </div>
          <div class="kpi-value">{{ $voluntariosActivos }}</div>
          <div class="kpi-sub">activos</div>
        </div>

        <div class="kpi blue">
          <div class="kpi-label">
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/inventario.png') }}" alt="" style="width:16px;height:16px;">
            Inventario
          </div>
          <div class="kpi-value">{{ $tiposBienInventario }}</div>
          <div class="kpi-sub">tipos de bien</div>
        </div>

      </div>

      <!-- ══ Acciones rápidas ══ -->
      <div class="quick-actions" style="margin-bottom:1.1rem;">
        @can('permiso', ['donadores', 'crear'])
          <a href="{{ route('donadores.create') }}" class="qa-btn">
            <div class="qa-icon"><img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/New_donador.png') }}" alt="" style="width:18px;height:18px;"></div>
            <div class="qa-label">Nuevo donador</div>
          </a>
        @endcan
        @can('permiso', ['beneficiarios', 'crear'])
          <a href="{{ route('beneficiarios.create') }}" class="qa-btn">
            <div class="qa-icon"><img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/New_beneficiario.png') }}" alt="" style="width:18px;height:18px;"></div>
            <div class="qa-label">Nuevo beneficiario</div>
          </a>
        @endcan
        @can('permiso', ['usuarios', 'ver'])
          <a href="{{ route('usuarios.index') }}" class="qa-btn">
            <div class="qa-icon"><img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/New_Usuario.png') }}" alt="" style="width:18px;height:18px;"></div>
            <div class="qa-label">Usuarios</div>
          </a>
        @endcan
        @can('permiso', ['campanas', 'ver'])
          <a href="{{ route('campanas.index') }}" class="qa-btn">
            <div class="qa-icon">🚀</div>
            <div class="qa-label">Campañas</div>
          </a>
        @endcan
        @can('permiso', ['inventario', 'ver'])
          <a href="{{ route('inventario.index') }}" class="qa-btn">
            <div class="qa-icon"><img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/inventario.png') }}" alt="" style="width:18px;height:18px;"></div>
            <div class="qa-label">Inventario</div>
          </a>
        @endcan
      </div>

      <!-- ══ FILA 1: Barras donaciones + Estado donaciones ══ -->
      <div class="dashboard-chart-row dashboard-chart-row-main">

        <!-- Barras: donaciones por mes -->
        <div class="card" style="padding:1rem;">
          <div class="card-header" style="margin-bottom:12px;">
            <h3 style="font-size:15px;font-weight:600;margin:0;">
              Donaciones por mes (MXN vs Especie)
              @if ($demoMensual)<span class="badge badge-amber">DEMO</span>@endif
            </h3>
            <span style="font-size:12px;color:var(--muted);">Año {{ now()->year }}</span>
          </div>
          <div style="height:240px;position:relative;">
            <canvas id="chartBarMeses"></canvas>
          </div>
        </div>

        <!-- Pastel: estado de donaciones -->
        <div class="card" style="padding:1rem;">
          <div class="card-header" style="margin-bottom:12px;">
            <h3 style="font-size:15px;font-weight:600;margin:0;">
              Estado de donaciones
              @if ($demoEstados)<span class="badge badge-amber">DEMO</span>@endif
            </h3>
          </div>
          <div style="height:185px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chartPieEstados"></canvas>
          </div>
          <div id="leyenda-estados" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;
               justify-content:center;font-size:11px;color:var(--muted);"></div>
        </div>

      </div>

      <!-- ══ FILA 2: Tipo donación + Beneficiarios + Campañas + Actividad ══ -->
      <div class="dashboard-chart-row dashboard-chart-row-four">

        <!-- Donut: tipo de donación -->
        <div class="card" style="padding:1rem;">
          <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">
            Tipo de donación
            @if ($demoTiposDonacion)<span class="badge badge-amber">DEMO</span>@endif
          </h3>
          <div style="height:175px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chartPieTipo"></canvas>
          </div>
        </div>

        <!-- Donut: beneficiarios por estado -->
        <div class="card" style="padding:1rem;">
          <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">
            Beneficiarios
            @if ($demoBeneficiarios)<span class="badge badge-amber">DEMO</span>@endif
          </h3>
          <div style="height:175px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chartDonutBenef"></canvas>
          </div>
        </div>

        <!-- Campañas activas -->
        <div class="card" style="padding:1rem;">
          <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">Campañas activas</h3>
          <div class="progress-wrap">
          @foreach ($campanasActivas as $campana)
            <div class="prog-item">
                <div class="prog-label">
                  <span style="font-size:12px;">{{ $campana['nombre'] }}</span>
                  <span style="color:var(--accent,#0aafa0);font-size:12px;font-weight:600;">{{ $campana['pct'] }}%</span>
                </div>
                <div class="prog-bar">
                  <div class="prog-fill" style="width:{{ $campana['pct'] }}%;background:{{ $campana['color'] }}"></div>
                </div>
              </div>
          @endforeach
          @if ($demoCampanas)<span class="badge badge-amber">DEMO</span>@endif
        </div>

        <!-- Actividad reciente -->
        <div class="card" style="padding:1rem;">
          <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">Actividad reciente</h3>
          @foreach ($actividadReciente as $actividad)
            <div class="activity-item">
              <div class="activity-dot" style="background:{{ $actividad['color'] }}"></div>
              <div class="activity-text" style="font-size:12px;">{{ $actividad['texto'] }}</div>
              <div class="activity-time">{{ $actividad['tiempo'] }}</div>
            </div>
          @endforeach
          @if ($demoActividad)<span class="badge badge-amber">DEMO</span>@endif
                  </div>

      </div>

      <!-- ══ FILA 3: Tipos de donador + Usuarios por rol ══ -->
      <div class="dashboard-chart-row dashboard-chart-row-two">

        <!-- Barras horiz: tipos de donador -->
        <div class="card" style="padding:1rem;">
          <div class="card-header" style="margin-bottom:12px;">
            <h3 style="font-size:15px;font-weight:600;margin:0;">
              <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/patrocinador.png') }}" alt=""
                   style="width:16px;height:16px;vertical-align:middle;margin-right:6px;">
              Tipos de donador
              @if ($demoDonadores)<span class="badge badge-amber">DEMO</span>@endif
            </h3>
          </div>
          <div style="height:200px;position:relative;">
            <canvas id="chartDonadorTipo"></canvas>
          </div>
        </div>

        <!-- Barras horiz: usuarios por rol -->
        <div class="card" style="padding:1rem;">
          <div class="card-header" style="margin-bottom:12px;">
            <h3 style="font-size:15px;font-weight:600;margin:0;">
              <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/Usuarios.png') }}" alt=""
                   style="width:16px;height:16px;vertical-align:middle;margin-right:6px;">
              Usuarios por rol
              @if ($demoRoles)<span class="badge badge-amber">DEMO</span>@endif
            </h3>
          </div>
          <div style="height:200px;position:relative;">
            <canvas id="chartRolUsuarios"></canvas>
          </div>
        </div>

      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div>

<!-- ══ Chart.js ══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {

  /* ── Colores globales ── */
  const gridColor = 'rgba(28,28,26,0.08)';
  const textColor = '#6B6B67';
  const colVerde  = '#1A7A5E';
  const colAmber  = '#C68B0A';
  const colBlue   = '#3B6FD4';
  const colRed    = '#C0392B';
  const colPurple = '#993556';
  const colGray   = '#6B6B67';

  Chart.defaults.color       = textColor;
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.font.size   = 12;

  /* ══════════════════════════════════════════════════
     1. BARRAS — Donaciones por mes
  ══════════════════════════════════════════════════ */
  new Chart(document.getElementById('chartBarMeses'), {
    type: 'bar',
    data: {
      labels:   @json($meses),
      datasets: [
        {
          label: 'MXN',
          data:  @json($montosEconomicos),
          backgroundColor: colVerde + 'cc',
          borderRadius: 5,
          borderSkipped: false,
        },
        {
          label: 'Especie (unidades)',
          data:  @json($cantidadesEspecie),
          backgroundColor: colAmber + 'aa',
          borderRadius: 5,
          borderSkipped: false,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { boxWidth: 12, padding: 14 } },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor } },
        y: { grid: { color: gridColor }, ticks: { color: textColor }, beginAtZero: true }
      }
    }
  });

  /* ══════════════════════════════════════════════════
     2. PASTEL — Estado de donaciones
  ══════════════════════════════════════════════════ */
  const labEstados = ["Verificada","Recibida","Pendiente","Rechazada"];
  const datEstados = @json($estadosDonacion);
  const colsEstados = [colVerde, colAmber, colBlue, colRed];

  new Chart(document.getElementById('chartPieEstados'), {
    type: 'pie',
    data: {
      labels: labEstados,
      datasets: [{
        data: datEstados,
        backgroundColor: colsEstados,
        borderWidth: 2,
        borderColor: '#FFFFFF'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
      }
    }
  });

  // Leyenda manual
  const legEl = document.getElementById('leyenda-estados');
  labEstados.forEach((l, i) => {
    legEl.innerHTML += `<span style="display:flex;align-items:center;gap:4px;">
      <span style="width:9px;height:9px;background:${colsEstados[i]};border-radius:50%;display:inline-block;"></span>
      ${l} (${datEstados[i]})
    </span>`;
  });

  /* ══════════════════════════════════════════════════
     3. DONUT — Tipo de donación
  ══════════════════════════════════════════════════ */
  new Chart(document.getElementById('chartPieTipo'), {
    type: 'doughnut',
    data: {
      labels: ["Econ\u00f3mica","En especie"],
      datasets: [{
        data: @json($tiposDonacion),
        backgroundColor: [colBlue, colVerde],
        borderWidth: 2,
        borderColor: '#FFFFFF'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8 } }
      }
    }
  });

  /* ══════════════════════════════════════════════════
     4. DONUT — Beneficiarios por estado
  ══════════════════════════════════════════════════ */
  new Chart(document.getElementById('chartDonutBenef'), {
    type: 'doughnut',
    data: {
      labels: ["Activos","En espera","Inactivos"],
      datasets: [{
        data: @json($estadosBeneficiario),
        backgroundColor: [colVerde, colAmber, colGray],
        borderWidth: 2,
        borderColor: '#FFFFFF'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8 } }
      }
    }
  });

  /* ══════════════════════════════════════════════════
     5. BARRAS HORIZONTALES — Tipos de donador
  ══════════════════════════════════════════════════ */
  new Chart(document.getElementById('chartDonadorTipo'), {
    type: 'bar',
    data: {
      labels: ["An\u00f3nimo","Persona","Grupo","Organizaci\u00f3n"],
      datasets: [{
        label: 'Donadores',
        data:  @json($tiposDonador),
        backgroundColor: [colGray, colVerde, colBlue, colPurple],
        borderRadius: 5,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} donador(es)` } }
      },
      scales: {
        x: { grid: { color: gridColor }, ticks: { color: textColor }, beginAtZero: true,
             ticks: { precision: 0, color: textColor } },
        y: { grid: { display: false }, ticks: { color: textColor } }
      }
    }
  });

  /* ══════════════════════════════════════════════════
     6. BARRAS HORIZONTALES — Usuarios por rol
  ══════════════════════════════════════════════════ */
  const rolLabels = @json($rolesLabels);
  const rolData   = @json($rolesData);

  // Paleta de colores según posición
  const rolColors = [colVerde, colBlue, colAmber, colPurple, colRed, colGray,
                     '#26c6da','#ef6c00','#8d6e63','#546e7a'];

  new Chart(document.getElementById('chartRolUsuarios'), {
    type: 'bar',
    data: {
      labels: rolLabels,
      datasets: [{
        label: 'Usuarios',
        data:  rolData,
        backgroundColor: rolColors.slice(0, rolLabels.length),
        borderRadius: 5,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} usuario(s)` } }
      },
      scales: {
        x: { grid: { color: gridColor },
             ticks: { precision: 0, color: textColor }, beginAtZero: true },
        y: { grid: { display: false }, ticks: { color: textColor } }
      }
    }
  });

})();
</script>

@endsection
