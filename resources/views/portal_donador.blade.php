@extends('layouts.app')
@section('title', 'Portal del donador | Juventud Iskali')
@section('page-title', 'Portal del donador')
@section('content')
<div class="main">
  <div class="topbar">
    <div class="topbar-title">Portal del donador</div>
    <div class="topbar-actions">
      @if (imagen_base64('/img/Logo.jpeg'))
        <img src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="Juventud Iskali" style="width:38px;height:38px;">
      @endif
      <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-secondary">Cerrar sesión</button></form>
    </div>
  </div>
  <div class="content">
    @if (! $donador)
      <div class="alert alert-warning">Tu cuenta aún no tiene un perfil de donador asociado.</div>
    @endif
    <h2>{{ $nombre }}</h2>
    <div class="kpi-grid">
      <div class="kpi"><div class="kpi-label">Nivel actual</div><div class="kpi-value">{{ $nivel ?: 'Sin nivel' }}</div></div>
      <div class="kpi blue"><div class="kpi-label">Puntos acumulados</div><div class="kpi-value">{{ $puntos }}</div></div>
    </div>
    <div class="card">
      <div class="card-header"><h3>Mis insignias</h3></div>
      <div style="padding:14px;">
        @forelse ($insignias as $insignia)<span class="badge badge-blue" style="margin:4px;">{{ $insignia->nombre }}</span>@empty <span style="color:var(--muted);">Aún no tienes insignias.</span>@endforelse
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3>Historial de aportaciones</h3></div>
      <div style="overflow-x:auto;"><table>
        <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Campaña</th><th>Estado</th></tr></thead>
        <tbody>
          @forelse ($aportaciones as $aporte)
            <tr><td>{{ \Illuminate\Support\Carbon::parse($aporte->fecha_recepcion)->format('d/m/Y') }}</td><td>{{ $aporte->producto }}</td><td>{{ $aporte->cantidad }} {{ $aporte->unidad_medida }}</td><td>{{ $aporte->campana ?: '—' }}</td><td>{{ ucfirst($aporte->estado) }}</td></tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--muted);">No hay aportaciones registradas.</td></tr>
          @endforelse
        </tbody>
      </table></div>
    </div>
    <div class="card">
      <div class="card-header"><h3>Mis diplomas</h3></div>
      <div style="overflow-x:auto;"><table>
        <thead><tr><th>Folio</th><th>Descripción</th><th>Fecha</th><th>PDF</th></tr></thead>
        <tbody>
          @forelse ($reconocimientos as $reconocimiento)
            <tr><td><strong>{{ $reconocimiento->folio }}</strong></td><td>{{ $reconocimiento->descripcion }}</td><td>{{ \Illuminate\Support\Carbon::parse($reconocimiento->fecha_emision)->format('d/m/Y') }}</td><td><a class="btn btn-secondary btn-sm" href="{{ route('reconocimientos.pdf', $reconocimiento->id_reconocimiento) }}">Descargar</a></td></tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:var(--muted);">No tienes diplomas emitidos.</td></tr>
          @endforelse
        </tbody>
      </table></div>
    </div>
  </div>
</div>
@endsection
