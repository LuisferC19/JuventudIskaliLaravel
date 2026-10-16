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

    <div class="card">
      <div class="card-header">
        <h3>Voluntarios <small>({{ $voluntariosActivos }} activos de {{ $totalVoluntarios }})</small></h3>
        <a class="btn btn-primary" href="{{ route('voluntarios.create') }}">+ Nuevo voluntario</a>
      </div>
      <table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Zona</th><th>Disponibilidad</th><th>Ingreso</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($voluntarios as $voluntario)
            <tr>
              <td>{{ $voluntario->usuario?->nombre }} {{ $voluntario->usuario?->apellido }}</td>
              <td>{{ $voluntario->usuario?->email }}</td>
              <td>{{ $voluntario->telefono_contacto ?: '—' }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($voluntario->zona_asignada)) }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($voluntario->disponibilidad)) }}</td>
              <td>{{ $voluntario->fecha_ingreso?->format('d/m/Y') }}</td>
              <td>{{ $voluntario->activo ? 'Activo' : 'Inactivo' }}</td>
              <td style="white-space:nowrap">
                <a class="btn btn-secondary btn-sm" href="{{ route('voluntarios.edit', $voluntario->id_voluntario) }}">Editar</a>
                @if ($voluntario->activo)
                  <form method="POST" action="{{ route('voluntarios.destroy', $voluntario->id_voluntario) }}" style="display:inline" onsubmit="return confirm('¿Desactivar este perfil de voluntario?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-secondary btn-sm" type="submit">Desactivar</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center">No hay perfiles de voluntario registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-header"><h3>Asignaciones a entregas</h3></div>
      <div class="card-body" style="padding:20px">
        <form method="POST" action="{{ route('voluntarios.asignaciones.store') }}" class="form-grid" data-validate="true">
          @csrf
          <div class="form-group"><label for="id_voluntario">Voluntario *</label>
            <select id="id_voluntario" name="id_voluntario" required><option value="">Seleccionar...</option>
              @foreach ($voluntarios->where('activo', true) as $voluntario)<option value="{{ $voluntario->id_voluntario }}">{{ $voluntario->usuario?->nombre }} {{ $voluntario->usuario?->apellido }}</option>@endforeach
            </select>
          </div>
          <div class="form-group"><label for="id_entrega">Entrega *</label>
            <select id="id_entrega" name="id_entrega" required><option value="">Seleccionar entrega...</option>
              @foreach ($entregasDisponibles as $entrega)<option value="{{ $entrega->id_entrega }}">#{{ $entrega->id_entrega }} — {{ $entrega->fecha_entrega }} ({{ str_replace('_', ' ', $entrega->estado) }})</option>@endforeach
            </select>
          </div>
          <div class="form-group"><label for="fecha_compromiso">Fecha compromiso *</label><input id="fecha_compromiso" type="datetime-local" name="fecha_compromiso" required></div>
          <div class="form-group"><label for="observaciones_voluntario">Observaciones</label><input id="observaciones_voluntario" type="text" name="observaciones_voluntario"></div>
          <div class="form-group"><button class="btn btn-primary" type="submit">Asignar entrega</button></div>
        </form>
      </div>
      <table>
        <thead><tr><th>Voluntario</th><th>Entrega</th><th>Asignado por</th><th>Fecha compromiso</th><th>Estado</th><th>Observaciones</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($asignaciones as $asignacion)
            <tr>
              <td>{{ $asignacion->nombre_voluntario }} {{ $asignacion->apellido_voluntario }}</td>
              <td>#{{ $asignacion->id_entrega }}</td>
              <td>{{ $asignacion->nombre_asignador }} {{ $asignacion->apellido_asignador }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($asignacion->fecha_compromiso)->format('d/m/Y H:i') }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($asignacion->estado)) }}</td>
              <td>{{ $asignacion->observaciones_voluntario ?: '—' }}</td>
              <td style="white-space:nowrap">
                <form method="POST" action="{{ route('voluntarios.asignaciones.update', $asignacion->id_asignacion) }}" style="display:inline">
                  @csrf @method('PATCH')
                  <input type="hidden" name="fecha_compromiso" value="{{ \Illuminate\Support\Carbon::parse($asignacion->fecha_compromiso)->format('Y-m-d H:i:s') }}">
                  <input type="hidden" name="observaciones_voluntario" value="{{ $asignacion->observaciones_voluntario }}">
                  <select name="estado" aria-label="Estado de asignación">
                    @foreach (['asignada', 'aceptada', 'rechazada', 'completada', 'cancelada'] as $estado)<option value="{{ $estado }}" @selected($asignacion->estado === $estado)>{{ ucfirst($estado) }}</option>@endforeach
                  </select>
                  <button class="btn btn-secondary btn-sm" type="submit">Actualizar</button>
                </form>
                <form method="POST" action="{{ route('voluntarios.asignaciones.destroy', $asignacion->id_asignacion) }}" style="display:inline" onsubmit="return confirm('¿Eliminar esta asignación?')">
                  @csrf @method('DELETE')<button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center">No hay asignaciones registradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-header"><h3>Asistencia por QR</h3></div>
      <div class="card-body" style="padding:20px">
        <div class="toolbar">
          <label for="actividad-asistencia">Actividad</label>
          <select id="actividad-asistencia">
            <option value="">Seleccionar actividad...</option>
            @foreach ($actividades as $actividad)<option value="{{ $actividad->id_actividad }}">{{ $actividad->titulo }} — {{ $actividad->fecha_inicio?->format('d/m/Y H:i') }}</option>@endforeach
          </select>
          <button id="cargar-asistencia" class="btn btn-secondary" type="button">Ver asistencia</button>
          <button id="generar-tokens" class="btn btn-primary" type="button">Generar QR personales</button>
        </div>
        <div id="tokens-qr" class="form-grid" style="margin-top:16px"></div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:16px">
          <input id="token-qr" type="text" maxlength="64" placeholder="Token QR del voluntario" style="max-width:420px">
          <button id="validar-token" class="btn btn-primary" type="button">Validar QR</button>
          <span id="resultado-qr" role="status"></span>
        </div>
        <table id="tabla-asistencia" style="margin-top:16px">
          <thead><tr><th>Voluntario</th><th>Zona</th><th>Estado</th><th>Método</th><th>Acción</th></tr></thead>
          <tbody><tr><td colspan="5" style="text-align:center">Selecciona una actividad.</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const activity = document.getElementById('actividad-asistencia');
const attendanceBody = document.querySelector('#tabla-asistencia tbody');
const tokenCards = document.getElementById('tokens-qr');

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', ...(options.headers || {}) }
  });
  const data = await response.json();
  if (!response.ok || data.success === false) throw new Error(data.error || 'No fue posible completar la operación.');
  return data;
}

async function cargarAsistencia() {
  if (!activity.value) return;
  const data = await requestJson(@json(route('voluntarios.asistencia', ['actividad' => '__ACTIVIDAD__'])).replace('__ACTIVIDAD__', activity.value));
  attendanceBody.replaceChildren();
  if (!data.asistencia.length) {
    attendanceBody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay registros de asistencia para esta actividad.</td></tr>';
    return;
  }
  data.asistencia.forEach(registro => {
    const row = document.createElement('tr');
    const name = document.createElement('td');
    name.textContent = registro.nombre_voluntario;
    const zone = document.createElement('td');
    zone.textContent = registro.zona_asignada.replaceAll('_', ' ');
    const state = document.createElement('td');
    state.textContent = Number(registro.presente) ? 'Presente' : 'Pendiente';
    const method = document.createElement('td');
    method.textContent = registro.metodo === 'qr' ? 'QR' : 'Manual';
    const action = document.createElement('td');
    if (!Number(registro.presente)) {
      const button = document.createElement('button');
      button.className = 'btn btn-secondary btn-sm';
      button.type = 'button';
      button.textContent = 'Registrar manualmente';
      button.addEventListener('click', async () => {
        try {
          await requestJson(@json(route('voluntarios.asistencias.store')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_voluntario: registro.id_voluntario, id_actividad: activity.value })
          });
          await cargarAsistencia();
        } catch (error) { alert(error.message); }
      });
      action.append(button);
    } else action.textContent = '—';
    row.append(name, zone, state, method, action);
    attendanceBody.append(row);
  });
}

document.getElementById('cargar-asistencia').addEventListener('click', () => cargarAsistencia().catch(error => alert(error.message)));
document.getElementById('generar-tokens').addEventListener('click', async () => {
  if (!activity.value) return alert('Selecciona una actividad.');
  try {
    const url = @json(route('voluntarios.qr.generar', ['actividad' => '__ACTIVIDAD__'])).replace('__ACTIVIDAD__', activity.value);
    const data = await requestJson(url, { method: 'POST' });
    tokenCards.replaceChildren();
    data.tokens.forEach(item => {
      const card = document.createElement('div');
      card.className = 'card';
      card.style.padding = '12px';
      const name = document.createElement('strong');
      name.textContent = item.nombre || `Voluntario #${item.id_voluntario}`;
      const qr = document.createElement('div');
      qr.style.margin = '12px auto';
      const token = document.createElement('code');
      token.textContent = item.token;
      card.append(name, qr, token);
      tokenCards.append(card);
      if (window.QRCode) new QRCode(qr, { text: item.token, width: 160, height: 160 });
    });
    await cargarAsistencia();
  } catch (error) { alert(error.message); }
});

document.getElementById('validar-token').addEventListener('click', async () => {
  const output = document.getElementById('resultado-qr');
  try {
    const data = await requestJson(@json(route('voluntarios.validar_qr')), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token_qr: document.getElementById('token-qr').value.trim() })
    });
    output.textContent = data.message;
    output.style.color = 'green';
    await cargarAsistencia();
  } catch (error) {
    output.textContent = error.message;
    output.style.color = 'red';
  }
});
</script>
@endsection
