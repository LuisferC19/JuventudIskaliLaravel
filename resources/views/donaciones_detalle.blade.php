@extends('layouts.app')
@section('title', 'Detalle de donación | Juventud Iskali')
@section('page-title', 'Detalle de donación')
@section('content')
<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content" style="padding:24px;">
    <div class="card" style="max-width:900px;">
      <div class="card-header"><h3>Donación #{{ $donacion->id_donacion }}</h3><a class="btn btn-secondary" href="{{ route('donaciones.index') }}">Volver</a></div>
      <div class="card-body" style="padding:24px;">
        <div class="form-grid">
          <div><strong>Estado</strong><div>{{ ucfirst($donacion->estado) }}</div></div>
          <div><strong>Tipo</strong><div>{{ $donacion->tipo === 'especie' ? 'En especie' : 'Económica' }}</div></div>
          <div><strong>Donador</strong><div>{{ trim($donacion->donador) }}</div></div>
          <div><strong>Campaña</strong><div>{{ $donacion->campana }}</div></div>
          <div><strong>Fecha de recepción</strong><div>{{ $donacion->fecha_recepcion }}</div></div>
          @if ($donacion->tipo === 'especie')
            <div><strong>Tipo de bien</strong><div>{{ $donacion->tipo_bien }}</div></div>
            <div><strong>Cantidad</strong><div>{{ $donacion->cantidad }} {{ $donacion->unidad_medida }}</div></div>
            <div class="form-group full"><strong>Descripción</strong><div>{{ $donacion->descripcion ?: '—' }}</div></div>
          @else
            <div><strong>Monto</strong><div>{{ $donacion->monto }} {{ $donacion->moneda }}</div></div>
            <div><strong>Método de pago</strong><div>{{ ucfirst($donacion->metodo_pago) }}</div></div>
            <div><strong>Referencia de pago</strong><div>{{ $donacion->referencia_pago ?: '—' }}</div></div>
          @endif
          <div class="form-group full"><strong>Evidencia</strong><div>@if ($donacion->evidencia_url)<a href="{{ $donacion->evidencia_url }}" target="_blank" rel="noopener noreferrer">{{ $donacion->evidencia_url }}</a>@else — @endif</div></div>
        </div>
      </div>
    </div>
  </div>
</div></div>
@endsection
