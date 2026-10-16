@extends('layouts.app')
@section('title', 'Planning | Juventud Iskali')
@section('page-title', 'Planning & Actividades')
@section('content')
@php
  $nombresMeses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
  $estados = ['planeada' => 'Planeada', 'en_curso' => 'En curso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];
  $coloresEstado = ['planeada' => ['var(--blue)', '#E5EEFF'], 'en_curso' => ['#9A6A00', '#FEF5DC'], 'completada' => ['#157A45', '#E4F5ED'], 'cancelada' => ['var(--danger)', '#FDECEA']];
  $zonas = ['san_martin' => 'San Martín', 'tlaxcala' => 'Tlaxcala', 'ambas' => 'Ambas'];
  $fechaAnterior = $fechaMes->subMonth();
  $fechaSiguiente = $fechaMes->addMonth();
@endphp
<div class="main">
  @include('partials.topbar')
  <div class="content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:12px;">
      <div>
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin:0 0 4px;">Planning & Actividades</h2>
        <p style="font-size:12.5px;color:var(--muted);margin:0;">Gestión de actividades por zona</p>
      </div>
      <a class="btn btn-primary" href="{{ route('actividades.create') }}">＋ Nueva actividad</a>
    </div>

    <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
      <div class="kpi"><div class="kpi-label">Total actividades</div><div class="kpi-value">{{ $kpis['total_actividades'] }}</div><div class="kpi-sub">registradas</div></div>
      <div class="kpi amber"><div class="kpi-label">Esta semana</div><div class="kpi-value">{{ $kpis['actividades_semana'] }}</div><div class="kpi-sub">programadas</div></div>
      <div class="kpi blue"><div class="kpi-label">San Martín</div><div class="kpi-value">{{ $kpis['san_martin'] }}</div><div class="kpi-sub">actividades</div></div>
      <div class="kpi danger"><div class="kpi-label">Tlaxcala</div><div class="kpi-value">{{ $kpis['tlaxcala'] }}</div><div class="kpi-sub">actividades</div></div>
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start;">
      <div class="card" style="overflow:hidden;">
        <div class="card-header">
          <div style="display:flex;align-items:center;gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0aafa0" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#1a2e2c;">{{ $nombresMeses[$mes] }} {{ $anio }}</span>
          </div>
          <div style="display:flex;gap:6px;">
            <a href="{{ route('planning.index', ['mes' => $fechaAnterior->month, 'anio' => $fechaAnterior->year, 'zona' => $zona]) }}" class="btn btn-secondary" aria-label="Mes anterior">‹</a>
            <a href="{{ route('planning.index', ['mes' => now()->month, 'anio' => now()->year, 'zona' => $zona]) }}" class="btn btn-secondary">Hoy</a>
            <a href="{{ route('planning.index', ['mes' => $fechaSiguiente->month, 'anio' => $fechaSiguiente->year, 'zona' => $zona]) }}" class="btn btn-secondary" aria-label="Mes siguiente">›</a>
          </div>
        </div>
        <div style="padding:16px 18px;">
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;">
            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $nombreDia)
              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">{{ $nombreDia }}</div>
            @endforeach
          </div>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
            @foreach ($diasCalendario as $dia)
              @if (! $dia || $dia->month !== $mes)
                <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
              @else
                @php($eventos = $actividadesPorDia->get($dia->day, collect()))
                <button type="button" class="dia-calendario" data-day="{{ $dia->day }}"
                        style="min-height:72px;border-radius:10px;padding:7px;text-align:left;cursor:pointer;border:1px solid {{ $eventos->isNotEmpty() ? '#c8efeb' : '#f0fdfa' }};background:{{ $eventos->isNotEmpty() ? '#f7fffe' : '#fafffe' }};">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:{{ $dia->isToday() ? '800' : '600' }};color:{{ $dia->isToday() ? '#0aafa0' : '#1a2e2c' }};">{{ $dia->day }}</span>
                    @if ($eventos->isNotEmpty())
                      <span style="background:#0aafa0;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;">{{ $eventos->count() }}</span>
                    @endif
                  </div>
                  @foreach ($eventos->take(2) as $evento)
                    <div style="background:{{ $coloresEstado[$evento->estado][1] ?? '#eef2ff' }};border-left:2px solid {{ $coloresEstado[$evento->estado][0] ?? '#6366f1' }};border-radius:4px;padding:2px 5px;margin-bottom:2px;overflow:hidden;">
                      <span style="font-size:9px;font-weight:600;color:{{ $coloresEstado[$evento->estado][0] ?? '#6366f1' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">{{ $evento->titulo }}</span>
                    </div>
                  @endforeach
                </button>
              @endif
            @endforeach
          </div>
        </div>
        <div style="padding:12px 18px 16px;border-top:1px solid #f0fdfa;display:flex;gap:16px;flex-wrap:wrap;">
          @foreach ($estados as $clave => $etiqueta)
            <div style="display:flex;align-items:center;gap:5px;">
              <div style="width:8px;height:8px;border-radius:50%;background:{{ $coloresEstado[$clave][0] }};"></div>
              <span style="font-size:11px;color:#5a8a84;">{{ $etiqueta }}</span>
            </div>
          @endforeach
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="card" style="padding:16px;">
          <div class="kpi-label">Filtrar por zona</div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @foreach (['todas' => 'Todas'] + $zonas as $clave => $etiqueta)
              <a href="{{ route('planning.index', ['mes' => $mes, 'anio' => $anio, 'zona' => $clave]) }}"
                 style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:99px;font-size:11.5px;font-weight:600;text-decoration:none;border:1.5px solid {{ $zona === $clave ? '#0aafa0' : '#e0f2f0' }};background:{{ $zona === $clave ? '#f0fdfa' : '#fff' }};color:{{ $zona === $clave ? '#0aafa0' : '#5a8a84' }};">
                {{ $etiqueta }}
              </a>
            @endforeach
          </div>
        </div>

        <div class="card" style="overflow:hidden;">
          <div class="card-header">
            <h3>Actividades del mes</h3>
            <span class="badge badge-green">{{ $actividades->count() }}</span>
          </div>
          <div style="max-height:600px;overflow-y:auto;">
            @forelse ($actividades as $actividad)
              <div style="padding:12px 18px;border-bottom:1px solid #f8fffe;">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                  <div style="width:3px;min-height:40px;border-radius:3px;background:{{ $coloresEstado[$actividad->estado][0] ?? '#6366f1' }};flex-shrink:0;margin-top:2px;"></div>
                  <div style="flex:1;min-width:0;">
                    <div style="font-size:12.5px;font-weight:600;color:#1a2e2c;margin-bottom:4px;">{{ $actividad->titulo }}</div>
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                      <span style="font-size:10px;font-weight:600;color:{{ $coloresEstado[$actividad->estado][0] }};background:{{ $coloresEstado[$actividad->estado][1] }};padding:2px 7px;border-radius:99px;">{{ $estados[$actividad->estado] ?? $actividad->estado }}</span>
                      <span style="font-size:10px;color:#5a8a84;background:#f0fdfa;padding:2px 7px;border-radius:99px;">{{ $zonas[$actividad->zona] ?? $actividad->zona }}</span>
                      <span style="font-size:10px;color:#5a8a84;">{{ $actividad->fecha_inicio->format('d/m') }}</span>
                    </div>
                    @if ($actividad->responsable)
                      <div style="font-size:10px;color:#5a8a84;margin-top:4px;">👤 {{ $actividad->responsable->nombre }} {{ $actividad->responsable->apellido }}</div>
                    @endif
                  </div>
                  <select class="cambiar-estado" data-url="{{ route('actividades.actualizarEstado', $actividad->id_actividad) }}"
                          aria-label="Cambiar estado de {{ $actividad->titulo }}"
                          style="font-size:10px;border:1px solid #e0f2f0;border-radius:6px;padding:3px 6px;color:#5a8a84;background:#fff;max-width:100px;">
                    @foreach ($estados as $clave => $etiqueta)
                      <option value="{{ $clave }}" @selected($actividad->estado === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                  </select>
                  <a href="{{ route('actividades.edit', $actividad->id_actividad) }}" class="btn btn-xs btn-secondary">Editar</a>
                </div>
              </div>
            @empty
              <div style="padding:24px 18px;text-align:center;color:var(--muted);font-size:12px;">No hay actividades para mostrar en este mes.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modal-dia" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <div class="modal-overlay" style="position:absolute;inset:0;background:rgba(10,30,28,.45);" onclick="cerrarDia()"></div>
  <div class="card" style="position:relative;z-index:1;width:90%;max-width:440px;max-height:80vh;overflow-y:auto;padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h3 id="modal-dia-titulo">Actividades del día</h3>
      <button type="button" class="btn btn-secondary" onclick="cerrarDia()">×</button>
    </div>
    <div id="modal-dia-contenido" style="padding-top:12px;"></div>
  </div>
</div>

<script>
const planningMes = @json($mes);
const planningAnio = @json($anio);
const planningEndpoint = @json(route('planning.actividadesMes'));
const planningCsrf = @json(csrf_token());
const planningMeses = @json(array_values($nombresMeses));

document.querySelectorAll('.cambiar-estado').forEach(select => {
  select.addEventListener('change', async () => {
    try {
      const respuesta = await fetch(select.dataset.url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': planningCsrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({estado: select.value})
      });
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.success) throw new Error(resultado.message || 'No se pudo actualizar el estado.');
      location.reload();
    } catch (error) {
      alert(error.message);
      location.reload();
    }
  });
});

document.querySelectorAll('.dia-calendario').forEach(dia => {
  dia.addEventListener('click', async () => {
    try {
      const url = new URL(planningEndpoint, window.location.origin);
      url.searchParams.set('mes', planningMes);
      url.searchParams.set('anio', planningAnio);
      const respuesta = await fetch(url, {headers: {'Accept': 'application/json'}});
      const resultado = await respuesta.json();
      if (!respuesta.ok || !resultado.success) throw new Error('No se pudieron cargar las actividades.');
      const numeroDia = dia.dataset.day;
      const actividades = resultado.actividades[numeroDia] || [];
      document.getElementById('modal-dia-titulo').textContent = `${numeroDia} de ${planningMeses[planningMes - 1]} ${planningAnio}`;
      const contenido = document.getElementById('modal-dia-contenido');
      contenido.replaceChildren();
      if (!actividades.length) {
        contenido.textContent = 'No hay actividades programadas para este día.';
      } else {
        actividades.forEach(actividad => {
          const tarjeta = document.createElement('div');
          tarjeta.style.cssText = 'border:1px solid #e0f2f0;border-radius:12px;padding:14px 16px;margin-bottom:10px;';
          const titulo = document.createElement('strong');
          titulo.textContent = actividad.titulo;
          tarjeta.append(titulo);
          if (actividad.descripcion) {
            const descripcion = document.createElement('p');
            descripcion.textContent = actividad.descripcion;
            tarjeta.append(descripcion);
          }
          const responsable = [actividad.nombre, actividad.apellido].filter(Boolean).join(' ');
          if (responsable) {
            const encargado = document.createElement('div');
            encargado.textContent = `Responsable: ${responsable}`;
            tarjeta.append(encargado);
          }
          contenido.append(tarjeta);
        });
      }
      document.getElementById('modal-dia').style.display = 'flex';
    } catch (error) {
      alert(error.message);
    }
  });
});

function cerrarDia() {
  document.getElementById('modal-dia').style.display = 'none';
}
</script>
@endsection
