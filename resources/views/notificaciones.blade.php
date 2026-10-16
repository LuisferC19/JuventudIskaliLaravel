@extends('layouts.app')
@section('title', 'Notificaciones | Juventud Iskali')
@section('page-title', 'Notificaciones')
@section('content')
<div class="main">
  @include('partials.topbar')
  <div class="content">
    <div class="kpi-grid">
      <div class="kpi">
        <div class="kpi-label">Total notificaciones</div>
        <div class="kpi-value">{{ $totalNotificaciones }}</div>
      </div>
      <div class="kpi amber">
        <div class="kpi-label">No leídas</div>
        <div class="kpi-value">{{ $noLeidas }}</div>
      </div>
      <div class="kpi blue">
        <div class="kpi-label">Leídas</div>
        <div class="kpi-value">{{ $totalNotificaciones - $noLeidas }}</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Mis Notificaciones</h3>
        @if ($noLeidas > 0)
          <button class="btn btn-secondary" type="button" id="marcar-todas"
                  data-url="{{ route('notificaciones.marcarTodas') }}">
            Marcar todas como leídas
          </button>
        @endif
      </div>
      <table>
        <thead>
          <tr>
            <th style="width:70px;">Leída</th>
            <th>Tipo</th>
            <th>Asunto</th>
            <th>Mensaje</th>
            <th>Canal</th>
            <th>Fecha</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($notificaciones as $notificacion)
            <tr data-id="{{ $notificacion->id_notificacion }}"
                style="background:{{ $notificacion->leida ? '#fff' : 'rgba(10,175,160,.05)' }};">
              <td>
                @if ($notificacion->leida)
                  <span style="font-size:11px;color:var(--muted);">Leída</span>
                @else
                  <span class="badge badge-teal">Nueva</span>
                @endif
              </td>
              <td><span class="badge badge-gray" style="text-transform:capitalize;">{{ $notificacion->tipo }}</span></td>
              <td><strong>{{ $notificacion->asunto }}</strong></td>
              <td style="max-width:300px;color:var(--muted);font-size:13px;">{{ $notificacion->mensaje }}</td>
              <td>
                <span style="font-size:11px;background:var(--border);padding:2px 6px;border-radius:4px;">
                  {{ $notificacion->canal }}
                </span>
              </td>
              <td style="font-size:12px;color:var(--muted);">
                {{ $notificacion->fecha_envio?->format('d/m/Y H:i') }}
              </td>
              <td>
                @if (! $notificacion->leida)
                  <button class="btn btn-xs btn-secondary marcar-leida" type="button"
                          data-url="{{ route('notificaciones.marcarLeida', $notificacion->id_notificacion) }}">
                    Marcar leída
                  </button>
                @else
                  <span style="font-size:11px;color:var(--muted);">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px;">No tienes notificaciones.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const notificacionesCsrf = @json(csrf_token());

async function enviarAccionNotificaciones(url) {
  const respuesta = await fetch(url, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': notificacionesCsrf,
      'X-Requested-With': 'XMLHttpRequest'
    }
  });
  const datos = await respuesta.json();
  if (!respuesta.ok || !datos.success) throw new Error(datos.error || 'No se pudo actualizar la notificación.');
}

function actualizarResumenNotificaciones() {
  const filas = [...document.querySelectorAll('tbody tr[data-id]')];
  const noLeidas = filas.filter(fila => fila.querySelector('.badge-teal')).length;
  const valores = document.querySelectorAll('.kpi-grid .kpi-value');
  if (valores.length === 3) {
    valores[0].textContent = filas.length;
    valores[1].textContent = noLeidas;
    valores[2].textContent = filas.length - noLeidas;
  }
}

document.querySelectorAll('.marcar-leida').forEach(boton => {
  boton.addEventListener('click', async () => {
    try {
      await enviarAccionNotificaciones(boton.dataset.url);
      const fila = boton.closest('tr');
      fila.style.background = '#fff';
      fila.cells[0].innerHTML = '<span style="font-size:11px;color:var(--muted);">Leída</span>';
      boton.replaceWith(Object.assign(document.createElement('span'), {
        textContent: '—',
        style: 'font-size:11px;color:var(--muted);'
      }));
      actualizarResumenNotificaciones();
    } catch (error) {
      alert(error.message);
    }
  });
});

document.getElementById('marcar-todas')?.addEventListener('click', async event => {
  const boton = event.currentTarget;
  try {
    await enviarAccionNotificaciones(boton.dataset.url);
    document.querySelectorAll('tbody tr[data-id]').forEach(fila => {
      fila.style.background = '#fff';
      fila.cells[0].innerHTML = '<span style="font-size:11px;color:var(--muted);">Leída</span>';
      fila.cells[6].textContent = '—';
    });
    boton.remove();
    actualizarResumenNotificaciones();
  } catch (error) {
    alert(error.message);
  }
});
</script>
@endsection
