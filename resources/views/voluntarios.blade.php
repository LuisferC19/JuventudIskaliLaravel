@extends('layouts.app')
@section('title', 'Voluntarios | Juventud Iskali')
@section('page-title', 'Voluntarios')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="main">
  @include('partials.topbar')
  <div class="content" style="padding:24px;">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div class="card">
        <div class="card-header">
          <h3>Voluntarios</h3>
          <div class="toolbar">
            <input type="text" class="search-input" placeholder="Buscar voluntario..." oninput="filtrarTabla(this,'tabla-voluntarios')">
            @can('permiso', ['voluntarios', 'crear'])
              <a class="btn btn-primary" href="{{ route('voluntarios.create') }}">+ Nuevo voluntario</a>
            @endcan
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table id="tabla-voluntarios">
            <thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Zona asignada</th><th>Disponibilidad</th><th>Fecha ingreso</th><th>Activo</th><th></th></tr></thead>
            <tbody>
              @forelse ($voluntarios as $voluntario)
                <tr>
                  <td>#V{{ $voluntario->id_voluntario }}</td>
                  <td>{{ $voluntario->nombre }} {{ $voluntario->apellido }}</td>
                  <td>{{ $voluntario->telefono_contacto ?: '—' }}</td>
                  <td>{{ str_replace('_', ' ', ucfirst($voluntario->zona_asignada)) }}</td>
                  <td><span class="badge badge-blue">{{ str_replace('_', ' ', ucfirst($voluntario->disponibilidad)) }}</span></td>
                  <td>{{ \Illuminate\Support\Carbon::parse($voluntario->fecha_ingreso)->format('Y-m-d') }}</td>
                  <td><span class="badge {{ $voluntario->activo ? 'badge-green' : 'badge-gray' }}">{{ $voluntario->activo ? 'Sí' : 'No' }}</span></td>
                  <td>
                    @can('permiso', ['voluntarios', 'editar'])
                      <a class="btn btn-secondary btn-sm" href="{{ route('voluntarios.edit', $voluntario->id_voluntario) }}">Editar</a>
                    @endcan
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No hay voluntarios registrados.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3>Asignaciones de voluntarios</h3></div>
        <div style="overflow-x:auto;">
          <table>
            <thead><tr><th>ID</th><th>Voluntario</th><th>Entrega</th><th>Asignado por</th><th>Fecha asignación</th><th>Fecha compromiso</th><th>Estado</th><th>Observaciones</th></tr></thead>
            <tbody>
              @forelse ($asignaciones as $asignacion)
                <tr>
                  <td>#A{{ $asignacion->id_asignacion }}</td>
                  <td>{{ $asignacion->voluntario_nombre }} {{ $asignacion->voluntario_apellido }}</td>
                  <td>#E{{ $asignacion->id_entrega }}</td>
                  <td>{{ trim(($asignacion->asignador_nombre ?? '').' '.($asignacion->asignador_apellido ?? '')) ?: '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($asignacion->fecha_asignacion)->format('Y-m-d H:i') }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($asignacion->fecha_compromiso)->format('Y-m-d H:i') }}</td>
                  <td><span class="badge badge-amber">{{ str_replace('_', ' ', ucfirst($asignacion->estado)) }}</span></td>
                  <td>{{ $asignacion->observaciones_voluntario ?: '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No hay asignaciones registradas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Registro de Asistencia a Actividades</h3>
          <div class="toolbar">
            <select id="selector-actividad" onchange="cargarVoluntariosActividad(this.value)">
              <option value="">Seleccionar actividad...</option>
              @foreach ($actividades as $actividad)
                <option value="{{ $actividad->id_actividad }}">{{ $actividad->titulo }} ({{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y') }})</option>
              @endforeach
            </select>
            <button class="btn btn-primary" type="button" onclick="abrirModalQr()">Generar QR de asistencia</button>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table id="tabla-asistencia">
            <thead><tr><th>ID</th><th>Nombre</th><th>Zona</th><th>Estado</th><th>Método</th><th>Acción</th></tr></thead>
            <tbody><tr><td colspan="6" style="text-align:center;color:#999;">Selecciona una actividad para ver los voluntarios</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modal-qr-asistencia" class="modal" style="display:none;">
  <div class="modal-content" style="text-align:center;max-width:440px;">
    <div class="modal-header">
      <h3>Código QR de Asistencia</h3>
      <button type="button" class="modal-close" onclick="closeModal('modal-qr-asistencia')" aria-label="Cerrar">✕</button>
    </div>
    <div class="modal-body">
      <label for="selector-voluntario-qr">Voluntario</label>
      <select id="selector-voluntario-qr" class="form-control" style="width:100%;margin:8px 0 16px;">
        <option value="">Seleccionar voluntario...</option>
        @foreach ($voluntarios->where('activo', true) as $voluntario)
          <option value="{{ $voluntario->id_voluntario }}">{{ $voluntario->nombre }} {{ $voluntario->apellido }}</option>
        @endforeach
      </select>
      <div id="qr-container" style="margin:20px auto;"></div>
      <p id="qr-estado" style="margin-top:15px;font-size:12px;color:#666;"></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-qr-asistencia')">Cerrar</button>
      <button type="button" class="btn btn-primary" onclick="generarQr()">Generar código</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const rutaAsistencia = @json(route('voluntarios.asistencia', ['actividad' => '__ID__']));
const rutaRegistrarAsistencia = @json(route('voluntarios.asistencia.registrar'));
const rutaGenerarQr = @json(route('voluntarios.qr.generar'));

function textoSeguro(value) {
  const elemento = document.createElement('span');
  elemento.textContent = value ?? '';
  return elemento.innerHTML;
}

async function peticionJson(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      ...(options.headers || {})
    }
  });
  const data = await response.json();
  if (!response.ok) {
    const message = data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo completar la solicitud.';
    throw new Error(message);
  }
  return data;
}

async function cargarVoluntariosActividad(idActividad) {
  const tbody = document.querySelector('#tabla-asistencia tbody');
  if (!idActividad) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">Selecciona una actividad para ver los voluntarios</td></tr>';
    return;
  }
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">Cargando asistencia...</td></tr>';
  try {
    const data = await peticionJson(rutaAsistencia.replace('__ID__', encodeURIComponent(idActividad)));
    tbody.innerHTML = '';
    if (data.asistencia.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">No hay voluntarios activos</td></tr>';
      return;
    }
    data.asistencia.forEach(asistencia => {
      const fila = document.createElement('tr');
      const estado = asistencia.presente ? '<span class="badge badge-green">Presente</span>' : '<span class="badge badge-gray">Ausente</span>';
      const metodo = asistencia.metodo === 'qr' ? '<span class="badge badge-blue">QR</span>' : asistencia.metodo === 'manual' ? '<span class="badge badge-amber">Manual</span>' : '—';
      const accion = asistencia.presente ? '—' : `<button class="btn btn-secondary btn-sm" type="button" onclick="registrarAsistenciaManual(${asistencia.id_voluntario}, ${idActividad})">Registrar</button>`;
      fila.innerHTML = `<td>${asistencia.id_asistencia ?? '—'}</td><td>${textoSeguro(asistencia.nombre_voluntario)}</td><td>${textoSeguro(asistencia.zona_asignada)}</td><td>${estado}</td><td>${metodo}</td><td>${accion}</td>`;
      tbody.appendChild(fila);
    });
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#b42318;">${textoSeguro(error.message)}</td></tr>`;
  }
}

async function registrarAsistenciaManual(idVoluntario, idActividad) {
  const body = new URLSearchParams({id_voluntario: idVoluntario, id_actividad: idActividad});
  try {
    await peticionJson(rutaRegistrarAsistencia, {method: 'POST', body});
    await cargarVoluntariosActividad(idActividad);
  } catch (error) {
    alert(error.message);
  }
}

function abrirModalQr() {
  if (!document.getElementById('selector-actividad').value) {
    alert('Selecciona una actividad antes de generar el QR.');
    return;
  }
  document.getElementById('qr-container').replaceChildren();
  document.getElementById('qr-estado').textContent = '';
  document.getElementById('modal-qr-asistencia').style.display = 'flex';
}

async function generarQr() {
  const idActividad = document.getElementById('selector-actividad').value;
  const idVoluntario = document.getElementById('selector-voluntario-qr').value;
  if (!idVoluntario) {
    alert('Selecciona un voluntario para generar su código.');
    return;
  }
  const body = new URLSearchParams({id_voluntario: idVoluntario, id_actividad: idActividad});
  try {
    const data = await peticionJson(rutaGenerarQr, {method: 'POST', body});
    const container = document.getElementById('qr-container');
    container.replaceChildren();
    new QRCode(container, {text: data.token, width: 220, height: 220, colorDark: '#0aafa0', colorLight: '#fff'});
    document.getElementById('qr-estado').textContent = 'Código QR generado. El registro se confirmará al validar este token.';
    cargarVoluntariosActividad(idActividad);
  } catch (error) {
    alert(error.message);
  }
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}
</script>
@endsection
