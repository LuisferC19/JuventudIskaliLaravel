@extends('layouts.app')
@section('title', 'Notificaciones | Juventud Iskali')
@section('page-title', 'Notificaciones')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- KPIs -->
      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">Total notificaciones</div>
          <div class="kpi-value">3</div>
        </div>
        <div class="kpi amber">
          <div class="kpi-label">No leídas</div>
          <div class="kpi-value">2</div>
        </div>
        <div class="kpi blue">
          <div class="kpi-label">Leídas</div>
          <div class="kpi-value">1</div>
        </div>
      </div>

      <!-- Tabla de notificaciones -->
      <div class="card">
        <div class="card-header">
          <h3>Mis Notificaciones</h3>
                      <button class="btn btn-secondary" onclick="marcarTodasLeidas()">
              Marcar todas como leídas
            </button>
                  </div>

                  <table>
            <thead>
              <tr>
                <th style="width:50px;">Leída</th>
                <th>Tipo</th>
                <th>Asunto</th>
                <th>Mensaje</th>
                <th>Canal</th>
                <th>Fecha</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
                              <tr style="background:rgba(10,175,160,.05);">
                  <td>
                                          <span class="badge badge-teal">Nuevo</span>
                                      </td>
                  <td>
                                        <span class="badge badge-gray" style="text-transform:capitalize;">
                      donacion                    </span>
                  </td>
                  <td>
                    <strong>Nueva donación registrada</strong>
                  </td>
                  <td style="max-width:300px;color:var(--muted);font-size:13px;">
                    Empresa Alfa S.A. registró una donación económica de $5,000....
                  </td>
                  <td>
                    <span style="font-size:11px;background:var(--border);padding:2px 6px;border-radius:4px;">
                      web                    </span>
                  </td>
                  <td style="font-size:12px;color:var(--muted);">
                    29/09/2026 10:15                  </td>
                  <td>
                                          <button class="btn btn-xs btn-secondary" 
                              onclick="marcarLeidaIndividual(1)">
                        Marcar leída
                      </button>
                                      </td>
                </tr>
                              <tr style="background:rgba(10,175,160,.05);">
                  <td>
                                          <span class="badge badge-teal">Nuevo</span>
                                      </td>
                  <td>
                                        <span class="badge badge-gray" style="text-transform:capitalize;">
                      actividad                    </span>
                  </td>
                  <td>
                    <strong>Actividad próxima a iniciar</strong>
                  </td>
                  <td style="max-width:300px;color:var(--muted);font-size:13px;">
                    La jornada de salud en Tlaxcala inicia en 2 días....
                  </td>
                  <td>
                    <span style="font-size:11px;background:var(--border);padding:2px 6px;border-radius:4px;">
                      web                    </span>
                  </td>
                  <td style="font-size:12px;color:var(--muted);">
                    28/09/2026 08:00                  </td>
                  <td>
                                          <button class="btn btn-xs btn-secondary" 
                              onclick="marcarLeidaIndividual(2)">
                        Marcar leída
                      </button>
                                      </td>
                </tr>
                              <tr style="background:#fff;">
                  <td>
                                          <span style="font-size:11px;color:var(--muted);">—</span>
                                      </td>
                  <td>
                                        <span class="badge badge-gray" style="text-transform:capitalize;">
                      sistema                    </span>
                  </td>
                  <td>
                    <strong>Respaldo completado</strong>
                  </td>
                  <td style="max-width:300px;color:var(--muted);font-size:13px;">
                    El respaldo automático de la base de datos se completó correctamente....
                  </td>
                  <td>
                    <span style="font-size:11px;background:var(--border);padding:2px 6px;border-radius:4px;">
                      web                    </span>
                  </td>
                  <td style="font-size:12px;color:var(--muted);">
                    27/09/2026 02:00                  </td>
                  <td>
                                          <span style="font-size:11px;color:var(--muted);">—</span>
                                      </td>
                </tr>
                          </tbody>
          </table>
              </div>

    </div>
  </div>
</div>

<script>
function marcarLeidaIndividual(id) {
  const button = document.querySelector('button[onclick="marcarLeidaIndividual(' + id + ')"]');
  const row = button && button.closest('tr');
  if (!row) return;

  row.style.background = '#fff';
  row.cells[0].textContent = '—';
  row.cells[row.cells.length - 1].textContent = '—';
  actualizarResumenNotificaciones();
  showToast('Notificación marcada como leída (demo).');
}

function marcarTodasLeidas() {
  document.querySelectorAll('table tbody tr').forEach(row => {
    if (!row.querySelector('.badge-teal')) return;
    row.style.background = '#fff';
    row.cells[0].textContent = '—';
    row.cells[row.cells.length - 1].textContent = '—';
  });
  actualizarResumenNotificaciones();
  showToast('Todas las notificaciones están leídas (demo).');
}

function actualizarResumenNotificaciones() {
  const values = document.querySelectorAll('.kpi-grid .kpi-value');
  const total = document.querySelectorAll('table tbody tr').length;
  const unread = document.querySelectorAll('table tbody .badge-teal').length;
  if (values.length < 3) return;
  values[0].textContent = total;
  values[1].textContent = unread;
  values[2].textContent = total - unread;
}
</script>

@endsection
