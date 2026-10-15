@extends('layouts.app')
@section('title', 'Respaldos | Juventud Iskali')
@section('page-title', 'Respaldos')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- ══ Encabezado ══ -->
      <div style="margin-bottom:1.1rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px;">
          Respaldos de Base de Datos
        </h2>
        <p style="font-size:12.5px;color:var(--muted);max-width:620px;line-height:1.6;">
          Genera respaldos comprimidos (.zip) o restaura la base de datos desde un respaldo previo.
          El historial de todas las operaciones queda registrado automáticamente.
        </p>
      </div>

      <!-- ══ Tarjetas de información ══ -->
      <div class="kpi-grid">

        <div class="kpi">
          <div class="kpi-label">Operaciones registradas</div>
          <div class="kpi-value">
            3          </div>
        </div>

        <div class="kpi amber">
          <div class="kpi-label">Base de datos</div>
          <div class="kpi-value" style="font-size:16px;">iskali</div>
        </div>

        <div class="kpi blue">
          <div class="kpi-label">Formato</div>
          <div class="kpi-value" style="font-size:16px;">ZIP comprimido</div>
        </div>

        <div class="kpi danger">
          <div class="kpi-label">Acceso</div>
          <div class="kpi-value" style="font-size:16px;">Solo administradores</div>
        </div>

      </div>

      <!-- ══ Panel de acciones ══ -->
      <div class="card">
        <div class="card-header">
          <h3>
            Acciones
          </h3>
        </div>
        <div class="card-body toolbar" style="padding:16px;">

          <!-- Botón exportar -->
          <form method="POST" action="#">
            @csrf
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="width:18px;height:18px;">
                <path d="M8 1V11M4 7L8 11L12 7"/><path d="M2 14H14"/>
              </svg>
              Generar Respaldo (.zip)
            </button>
          </form>

          <!-- Botón importar -->
          <form method="POST" action="#" enctype="multipart/form-data">
            @csrf
            <input type="file"
                   name="backup_file"
                   id="backup_file"
                   accept=".zip"
                   style="display:none;"
                   onchange="confirmarImportacion()">
            <label for="backup_file" class="btn btn-secondary">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="width:18px;height:18px;">
                <path d="M8 11V1M4 5L8 1L12 5"/><path d="M2 14H14"/>
              </svg>
              Importar Respaldo (.zip)
            </label>
          </form>

          <span style="font-size:12px;color:var(--muted);max-width:280px;line-height:1.6;">
            La importación reemplazará los datos actuales de la base de datos con el contenido del respaldo.
          </span>

        </div>
      </div>

      <!-- ══ Historial de operaciones ══ -->
      <div class="card">
        <div class="card-header">
          <h3>
            Historial de Operaciones
          </h3>
        </div>
        <div class="card-body" style="padding:0;overflow-x:auto;">

                      <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Operación</th>
                  <th>Formato</th>
                  <th>Archivo</th>
                  <th>Tamaño</th>
                  <th>Usuario</th>
                  <th>Fecha y Hora</th>
                  <th>Notas</th>
                </tr>
              </thead>
              <tbody>
                                  <tr style="border-bottom:1px solid var(--border,#c8efeb);">
                    <td style="padding:11px 16px;color:var(--muted,#5a8a84);">1</td>

                    <td style="padding:11px 16px;">
                                              <span class="badge badge-amber">
                          IMPORTACIÓN
                        </span>
                                          </td>

                    <td style="padding:11px 16px;">
                      <code>
                        SQL                      </code>
                    </td>

                    <td style="padding:11px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      respaldo_20260928_020000.sql                    </td>

                    <td style="padding:11px 16px;" class="bytes-cell" data-bytes="76022">
                      74.2 KB
                    </td>

                    <td style="padding:11px 16px;">
                      Sistema (automático)                    </td>

                    <td style="padding:11px 16px;white-space:nowrap;color:var(--muted,#5a8a84);">
                      2026-09-28 02:00:00                    </td>

                    <td style="padding:11px 16px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="Respaldo automático diario">
                      Respaldo automático diario                    </td>
                  </tr>
                                  <tr style="border-bottom:1px solid var(--border,#c8efeb);background:rgba(10,175,160,0.03);">
                    <td style="padding:11px 16px;color:var(--muted,#5a8a84);">2</td>

                    <td style="padding:11px 16px;">
                                              <span class="badge badge-amber">
                          IMPORTACIÓN
                        </span>
                                          </td>

                    <td style="padding:11px 16px;">
                      <code>
                        SQL                      </code>
                    </td>

                    <td style="padding:11px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      respaldo_20260921_020000.sql                    </td>

                    <td style="padding:11px 16px;" class="bytes-cell" data-bytes="74890">
                      73.1 KB
                    </td>

                    <td style="padding:11px 16px;">
                      Sistema (automático)                    </td>

                    <td style="padding:11px 16px;white-space:nowrap;color:var(--muted,#5a8a84);">
                      2026-09-21 02:00:00                    </td>

                    <td style="padding:11px 16px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="Respaldo automático diario">
                      Respaldo automático diario                    </td>
                  </tr>
                                  <tr style="border-bottom:1px solid var(--border,#c8efeb);">
                    <td style="padding:11px 16px;color:var(--muted,#5a8a84);">3</td>

                    <td style="padding:11px 16px;">
                                              <span class="badge badge-amber">
                          IMPORTACIÓN
                        </span>
                                          </td>

                    <td style="padding:11px 16px;">
                      <code>
                        SQL                      </code>
                    </td>

                    <td style="padding:11px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      respaldo_20260519_133016.sql                    </td>

                    <td style="padding:11px 16px;" class="bytes-cell" data-bytes="76022">
                      74.2 KB
                    </td>

                    <td style="padding:11px 16px;">
                      Eva Sánchez                    </td>

                    <td style="padding:11px 16px;white-space:nowrap;color:var(--muted,#5a8a84);">
                      2026-05-19 13:30:16                    </td>

                    <td style="padding:11px 16px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="Importación manual de prueba">
                      Importación manual de prueba                    </td>
                  </tr>
                              </tbody>
            </table>
          
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Formatear bytes en la tabla ──────────────────
function formatBytes(bytes, dec = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024, s = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dec)) + ' ' + s[i];
}
document.querySelectorAll('.bytes-cell').forEach(c => {
    const b = parseInt(c.dataset.bytes, 10);
    if (!isNaN(b)) c.textContent = formatBytes(b);
});

// ── Confirmación antes de importar ──────────────
function confirmarImportacion() {
    const input = document.getElementById('backup_file');
    if (!input.files.length) return;

    Swal.fire({
        title: '¿Restaurar base de datos?',
        html: '<p style="color:#555;font-size:14px;">Esta acción <strong>reemplazará todos los datos actuales</strong> con el contenido del archivo ZIP seleccionado.<br><br>Asegúrate de tener un respaldo reciente antes de continuar.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e67e22',
        cancelButtonColor:  '#aaa',
        confirmButtonText:  'Sí, restaurar',
        cancelButtonText:   'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form-importar').submit();
        } else {
            input.value = '';
        }
    });
}

// ── Alertas desde URL params ─────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const p = new URLSearchParams(window.location.search);

    if (p.get('exito') === 'exportacion' || p.get('exito') === 'generado') {
        Swal.fire({
            title: '¡Respaldo generado!',
            text:  'El archivo ZIP se ha descargado correctamente.',
            icon:  'success',
            confirmButtonColor: '#0aafa0',
            confirmButtonText:  'Aceptar'
        }).then(() => limpiarUrl());
    }

    if (p.get('exito') === 'importacion') {
        Swal.fire({
            title: '¡Base de datos restaurada!',
            text:  'La importación del respaldo se completó con éxito.',
            icon:  'success',
            confirmButtonColor: '#0aafa0',
            confirmButtonText:  'Aceptar'
        }).then(() => limpiarUrl());
    }

    if (p.get('error')) {
        Swal.fire({
            title: 'Error',
            text:  decodeURIComponent(p.get('error')),
            icon:  'error',
            confirmButtonColor: '#0aafa0'
        }).then(() => limpiarUrl());
    }

    function limpiarUrl() {
        const url = window.location.protocol + '//' + window.location.host + window.location.pathname;
        window.history.replaceState({}, '', url);
    }
});
</script>

@endsection
