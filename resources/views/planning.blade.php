@extends('layouts.app')
@section('title', 'Planning | Juventud Iskali')
@section('page-title', 'Planning & Actividades')
@section('content')

@php
  $actividadesPorDia = $actividades->groupBy(fn ($actividad) => \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->day);
  $diasVacios = $fechaCalendario->dayOfWeekIso - 1;
  $diasMes = $fechaCalendario->daysInMonth;
  $estadoColor = ['planeada' => 'badge-blue', 'en_curso' => 'badge-amber', 'completada' => 'badge-green', 'cancelada' => 'badge-gray'];
@endphp

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin:0 0 4px;">Planning &amp; Actividades</h2>
          <p style="font-size:12.5px;color:var(--muted);margin:0;">{{ $fechaCalendario->locale('es')->isoFormat('MMMM YYYY') }} — gestión de actividades por zona</p>
        </div>
        @can('permiso', ['planning', 'crear'])
          <button class="btn btn-primary" type="button" onclick="abrirModal()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva actividad
          </button>
        @endcan
      </div>

      <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
        <div class="kpi"><div class="kpi-label">Total actividades</div><div class="kpi-value">{{ $totalActividades }}</div><div class="kpi-sub">registradas</div></div>
        <div class="kpi amber"><div class="kpi-label">Esta semana</div><div class="kpi-value">{{ $actividadesSemana }}</div><div class="kpi-sub">programadas</div></div>
        <div class="kpi blue"><div class="kpi-label">San Martín</div><div class="kpi-value">{{ $actividadesSanMartin }}</div><div class="kpi-sub">actividades</div></div>
        <div class="kpi danger"><div class="kpi-label">Tlaxcala</div><div class="kpi-value">{{ $actividadesTlaxcala }}</div><div class="kpi-sub">actividades</div></div>
      </div>

      <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:18px;align-items:start;">
        <div class="card" style="overflow:hidden;">
          <div class="card-header">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0aafa0" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#1a2e2c;">{{ $fechaCalendario->locale('es')->isoFormat('MMMM YYYY') }}</span>
            </div>
            <div style="display:flex;gap:6px;">
              <a class="btn btn-secondary" href="{{ route('planning.index', ['mes' => $fechaCalendario->copy()->subMonth()->month, 'anio' => $fechaCalendario->copy()->subMonth()->year]) }}" aria-label="Mes anterior">‹</a>
              <a class="btn btn-secondary" href="{{ route('planning.index') }}">Hoy</a>
              <a class="btn btn-secondary" href="{{ route('planning.index', ['mes' => $fechaCalendario->copy()->addMonth()->month, 'anio' => $fechaCalendario->copy()->addMonth()->year]) }}" aria-label="Mes siguiente">›</a>
            </div>
          </div>

          <div style="padding:16px 18px;">
            <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px;margin-bottom:6px;">
              @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaNombre)
                <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">{{ $diaNombre }}</div>
              @endforeach
            </div>
            <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px;">
              @for ($i = 0; $i < $diasVacios; $i++)
                <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
              @endfor
              @for ($dia = 1; $dia <= $diasMes; $dia++)
                @php($eventosDia = $actividadesPorDia->get($dia, collect()))
                <div style="min-height:72px;border-radius:10px;padding:7px;border:1px solid {{ $fechaCalendario->isSameDay(now()) && $dia === now()->day ? '#0aafa0' : '#f0fdfa' }};background:#fafffe;">
                  <div style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;color:#1a2e2c;margin-bottom:4px;">{{ $dia }}</div>
                  @foreach ($eventosDia->take(2) as $evento)
                    <div title="{{ $evento->titulo }}" style="font-size:10px;color:#087a70;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $evento->titulo }}</div>
                  @endforeach
                  @if ($eventosDia->count() > 2)
                    <div style="font-size:9px;color:#5a8a84;">+{{ $eventosDia->count() - 2 }} más</div>
                  @endif
                </div>
              @endfor
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3>Actividades del mes</h3></div>
          <div style="padding:12px;max-height:540px;overflow:auto;">
            @forelse ($actividades as $actividad)
              <article style="padding:12px 0;border-bottom:1px solid var(--border,#d0ecea);">
                <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                  <strong style="font-size:13px;">{{ $actividad->titulo }}</strong>
                  <span class="badge {{ $estadoColor[$actividad->estado] ?? 'badge-gray' }}">{{ str_replace('_', ' ', ucfirst($actividad->estado)) }}</span>
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:5px;">
                  {{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y H:i') }} · {{ str_replace('_', ' ', ucfirst($actividad->zona)) }}
                </div>
                @if ($actividad->descripcion)
                  <p style="font-size:12px;margin:7px 0;color:var(--muted);">{{ $actividad->descripcion }}</p>
                @endif
                <div style="font-size:11px;color:var(--muted);">{{ trim(($actividad->responsable_nombre ?? '').' '.($actividad->responsable_apellido ?? '')) }}</div>
                <div style="display:flex;gap:6px;margin-top:8px;">
                  @can('permiso', ['planning', 'editar'])
                    <button class="btn btn-secondary btn-sm" type="button"
                      data-actividad="{{ json_encode([
                        'id' => $actividad->id_actividad,
                        'titulo' => $actividad->titulo,
                        'descripcion' => $actividad->descripcion,
                        'fecha_inicio' => \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('Y-m-d\TH:i'),
                        'fecha_fin' => \Illuminate\Support\Carbon::parse($actividad->fecha_fin)->format('Y-m-d\TH:i'),
                        'zona' => $actividad->zona,
                        'estado' => $actividad->estado,
                      ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                      onclick="editarActividad(this)">Editar</button>
                  @endcan
                  @can('permiso', ['planning', 'eliminar'])
                    <form method="POST" action="{{ route('planning.destroy', $actividad->id_actividad) }}" onsubmit="return confirm('¿Eliminar esta actividad?')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                    </form>
                  @endcan
                </div>
              </article>
            @empty
              <p style="color:var(--muted);font-size:13px;">No hay actividades programadas para este mes.</p>
            @endforelse
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:18px;">
        <div class="card-header"><h3>Calendario mensual JSON</h3></div>
        <div style="padding:12px 16px;font-size:12px;color:var(--muted);">
          Endpoint disponible:
          <code>{{ route('planning.calendario', ['mes' => $fechaCalendario->month, 'anio' => $fechaCalendario->year]) }}</code>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modal-actividad" class="modal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <div onclick="cerrarModal()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>
  <div class="modal-content" style="position:relative;width:min(620px,calc(100% - 28px));max-height:90vh;overflow:auto;">
    <div class="modal-header">
      <h3 id="titulo-modal-actividad">Nueva actividad</h3>
      <button type="button" class="modal-close" onclick="cerrarModal()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
      <form id="form-actividad" method="POST" action="{{ route('planning.store') }}">
        @csrf
        <input id="metodo-actividad" type="hidden" name="_method" value="POST">
        <div class="form-group">
          <label for="actividad-titulo">Título *</label>
          <input id="actividad-titulo" name="titulo" maxlength="150" required>
          @error('titulo')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="actividad-descripcion">Descripción</label>
          <textarea id="actividad-descripcion" name="descripcion" rows="3"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group"><label for="actividad-inicio">Fecha y hora de inicio *</label><input id="actividad-inicio" type="datetime-local" name="fecha_inicio" required></div>
          <div class="form-group"><label for="actividad-fin">Fecha y hora de fin *</label><input id="actividad-fin" type="datetime-local" name="fecha_fin" required></div>
          <div class="form-group">
            <label for="actividad-zona">Zona *</label>
            <select id="actividad-zona" name="zona" required>
              <option value="san_martin">San Martín</option><option value="tlaxcala">Tlaxcala</option><option value="ambas">Ambas</option>
            </select>
          </div>
          <div class="form-group">
            <label for="actividad-estado">Estado *</label>
            <select id="actividad-estado" name="estado" required>
              <option value="planeada">Planeada</option><option value="en_curso">En curso</option><option value="completada">Completada</option><option value="cancelada">Cancelada</option>
            </select>
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar actividad</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const formularioActividad = document.getElementById('form-actividad');
const rutaActualizarActividad = @json(route('planning.update', ['actividad' => '__ID__']));

function abrirModal() {
  formularioActividad.reset();
  formularioActividad.action = @json(route('planning.store'));
  document.getElementById('metodo-actividad').value = 'POST';
  document.getElementById('titulo-modal-actividad').textContent = 'Nueva actividad';
  document.getElementById('modal-actividad').style.display = 'flex';
}

function editarActividad(boton) {
  const actividad = JSON.parse(boton.dataset.actividad);
  formularioActividad.action = rutaActualizarActividad.replace('__ID__', actividad.id);
  document.getElementById('metodo-actividad').value = 'PUT';
  document.getElementById('titulo-modal-actividad').textContent = 'Editar actividad';
  document.getElementById('actividad-titulo').value = actividad.titulo;
  document.getElementById('actividad-descripcion').value = actividad.descripcion || '';
  document.getElementById('actividad-inicio').value = actividad.fecha_inicio;
  document.getElementById('actividad-fin').value = actividad.fecha_fin;
  document.getElementById('actividad-zona').value = actividad.zona;
  document.getElementById('actividad-estado').value = actividad.estado;
  document.getElementById('modal-actividad').style.display = 'flex';
}

function cerrarModal() {
  document.getElementById('modal-actividad').style.display = 'none';
}
</script>
@endsection
