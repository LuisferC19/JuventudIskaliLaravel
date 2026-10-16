@extends('layouts.app')
@section('title', 'Respaldos | Juventud Iskali')
@section('page-title', 'Respaldos')
@section('content')

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div style="margin-bottom:1.1rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px;">Respaldos de Base de Datos</h2>
        <p style="font-size:12.5px;color:var(--muted);max-width:720px;line-height:1.6;">
          Los respaldos ZIP generados aquí incluyen únicamente datos, omiten columnas calculadas y están firmados para impedir la importación de archivos alterados.
        </p>
      </div>

      <div class="kpi-grid">
        <div class="kpi"><div class="kpi-label">Operaciones registradas</div><div class="kpi-value">{{ $historial->count() }}</div></div>
        <div class="kpi amber"><div class="kpi-label">Base de datos</div><div class="kpi-value" style="font-size:16px;">{{ config('database.connections.mysql.database') }}</div></div>
        <div class="kpi blue"><div class="kpi-label">Formato</div><div class="kpi-value" style="font-size:16px;">ZIP firmado</div></div>
        <div class="kpi danger"><div class="kpi-label">Acceso</div><div class="kpi-value" style="font-size:16px;">Administración / auditoría</div></div>
      </div>

      <div class="card">
        <div class="card-header"><h3>Acciones</h3></div>
        <div class="card-body toolbar" style="padding:16px;">
          @can('permiso', ['respaldos', 'crear'])
            <form method="POST" action="{{ route('respaldos.generar') }}">
              @csrf
              <button type="submit" class="btn btn-primary">Generar respaldo (.zip)</button>
            </form>
          @endcan
          @can('permiso', ['respaldos', 'editar'])
            <form id="form-importar" method="POST" action="{{ route('respaldos.importar') }}" enctype="multipart/form-data">
              @csrf
              <input type="file" name="backup_file" id="backup_file" accept=".zip,application/zip" required style="display:none;" onchange="document.getElementById('nombre-archivo').textContent=this.files[0]?.name || ''">
              <label for="backup_file" class="btn btn-secondary">Seleccionar respaldo (.zip)</label>
              <span id="nombre-archivo" style="font-size:12px;color:var(--muted);"></span>
              <button type="submit" class="btn btn-danger" onclick="return confirmarImportacion()">Importar respaldo</button>
              @error('backup_file')<div class="field-error">{{ $message }}</div>@enderror
            </form>
          @endcan
          <span style="font-size:12px;color:var(--muted);max-width:320px;line-height:1.6;">
            Importar reemplaza los datos de la base de datos. Solo se aceptan ZIP creados por esta aplicación y firmados con la clave actual.
          </span>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3>Historial de operaciones</h3></div>
        <div class="card-body" style="padding:0;overflow-x:auto;">
          <table>
            <thead><tr><th>#</th><th>Operación</th><th>Formato</th><th>Archivo</th><th>Tamaño</th><th>Usuario</th><th>Fecha y hora</th><th>Notas</th></tr></thead>
            <tbody>
              @forelse ($historial as $respaldo)
                <tr>
                  <td>{{ $respaldo->id }}</td>
                  <td><span class="badge {{ $respaldo->tipo_operacion === 'EXPORTACION' ? 'badge-green' : 'badge-amber' }}">{{ $respaldo->tipo_operacion }}</span></td>
                  <td><code>{{ $respaldo->formato }}</code></td>
                  <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $respaldo->nombre_archivo }}">{{ $respaldo->nombre_archivo }}</td>
                  <td>{{ $respaldo->tamanio_bytes ? number_format($respaldo->tamanio_bytes / 1024, 1).' KB' : '—' }}</td>
                  <td>{{ $respaldo->nombre_usuario ?? 'Sistema' }}</td>
                  <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($respaldo->fechayhora)->format('Y-m-d H:i:s') }}</td>
                  <td>{{ $respaldo->observaciones ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No hay operaciones registradas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function confirmarImportacion() {
  return confirm('Esta operación reemplazará los datos existentes. ¿Deseas continuar?');
}
</script>
@endsection
