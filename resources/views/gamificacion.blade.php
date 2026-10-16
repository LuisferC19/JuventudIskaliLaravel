@extends('layouts.app')
@section('title', 'Gamificación | Juventud Iskali')
@section('page-title', 'Gamificación')
@section('content')

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div class="kpi-grid">
        <div class="kpi"><div class="kpi-label">Niveles definidos</div><div class="kpi-value">{{ $totalNiveles }}</div></div>
        <div class="kpi amber"><div class="kpi-label">Insignias activas</div><div class="kpi-value">{{ $insigniasActivas }}</div></div>
        <div class="kpi blue"><div class="kpi-label">Reconocimientos emitidos</div><div class="kpi-value">{{ $reconocimientosEmitidos }}</div></div>
        <div class="kpi pink"><div class="kpi-label">Donadores con nivel</div><div class="kpi-value">{{ $donadoresConNivel }}</div></div>
      </div>

      <div class="two-col">
        <div class="card">
          <div class="card-header"><h3>Niveles de gamificación</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>ID</th><th>Nombre</th><th>Pts mínimos</th><th>Pts máximos</th><th>Descripción</th></tr></thead>
              <tbody>
                @forelse ($niveles as $nivel)
                  <tr>
                    <td>#N{{ $nivel->id_nivel }}</td>
                    <td><span class="badge badge-gray">{{ $nivel->nombre }}</span></td>
                    <td>{{ number_format($nivel->puntos_minimos) }}</td>
                    <td>{{ number_format($nivel->puntos_maximos) }}</td>
                    <td>{{ $nivel->descripcion ?: '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" style="text-align:center;color:var(--muted);">No hay niveles configurados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3>Insignias activas</h3></div>
          <div class="insignia-grid">
            @forelse ($insignias as $insignia)
              <div class="ins-card">
                @if ($insignia->imagen_url && filter_var($insignia->imagen_url, FILTER_VALIDATE_URL) && str_starts_with($insignia->imagen_url, 'https://'))
                  <img class="ins-icon" src="{{ $insignia->imagen_url }}" alt="{{ $insignia->nombre }}" loading="lazy">
                @else
                  <div class="ins-icon" aria-hidden="true">✦</div>
                @endif
                <div class="ins-name">{{ $insignia->nombre }}</div>
                <div class="ins-pts">+{{ number_format($insignia->puntos_otorgados) }} pts</div>
                @if ($insignia->descripcion)<div style="font-size:11px;color:var(--muted);">{{ $insignia->descripcion }}</div>@endif
                <div class="ins-status"><span class="badge badge-green">Activa</span></div>
              </div>
            @empty
              <p style="color:var(--muted);font-size:13px;">No hay insignias activas.</p>
            @endforelse
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Reconocimientos emitidos</h3>
          <select id="filtro-tipo-reconocimiento" aria-label="Filtrar reconocimientos" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
            <option value="">Todos los tipos</option>
            @foreach (['diploma', 'carta', 'certificado', 'voluntario_mes', 'mayor_asistencia', 'mayor_entregas', 'donador_destacado', 'otro'] as $tipo)
              <option value="{{ $tipo }}">{{ str_replace('_', ' ', ucfirst($tipo)) }}</option>
            @endforeach
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table id="tabla-reconocimientos">
            <thead><tr><th>ID</th><th>Tipo</th><th>Receptor</th><th>Descripción</th><th>Campaña</th><th>Emitido por</th><th>Fecha</th><th>PDF</th></tr></thead>
            <tbody>
              @forelse ($reconocimientos as $reconocimiento)
                <tr data-tipo="{{ $reconocimiento->tipo }}">
                  <td>#RC{{ $reconocimiento->id_reconocimiento }}</td>
                  <td><span class="badge badge-blue">{{ str_replace('_', ' ', ucfirst($reconocimiento->tipo)) }}</span></td>
                  <td>{{ $reconocimiento->receptor }}</td>
                  <td>{{ $reconocimiento->descripcion ?: '—' }}</td>
                  <td>{{ $reconocimiento->campana ?: '—' }}</td>
                  <td>{{ $reconocimiento->emisor ?: '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($reconocimiento->fecha_emision)->format('Y-m-d') }}</td>
                  <td>
                    @if ($reconocimiento->archivo_pdf_url && (str_starts_with($reconocimiento->archivo_pdf_url, '/storage/') || (filter_var($reconocimiento->archivo_pdf_url, FILTER_VALIDATE_URL) && str_starts_with($reconocimiento->archivo_pdf_url, 'https://'))))
                      <a href="{{ str_starts_with($reconocimiento->archivo_pdf_url, '/storage/') ? asset(ltrim($reconocimiento->archivo_pdf_url, '/')) : $reconocimiento->archivo_pdf_url }}" target="_blank" rel="noopener">Descargar</a>
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No hay reconocimientos emitidos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('filtro-tipo-reconocimiento').addEventListener('change', function () {
  const tipo = this.value;
  document.querySelectorAll('#tabla-reconocimientos tbody tr[data-tipo]').forEach((fila) => {
    fila.hidden = tipo !== '' && fila.dataset.tipo !== tipo;
  });
});
</script>
@endsection
