@extends('layouts.app')
@section('title', 'Detalle de entrega | Juventud Iskali')
@section('page-title', 'Detalle de entrega')
@section('content')
<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content" style="padding:24px;">
    <div class="card" style="max-width:900px;">
      <div class="card-header"><h3>Entrega #{{ $entrega->id_entrega }}</h3><a class="btn btn-secondary" href="{{ route('entregas.index') }}">Volver</a></div>
      <div class="card-body" style="padding:24px;">
        <div class="form-grid">
          <div><strong>Estado</strong><div>{{ ucfirst(str_replace('_', ' ', $entrega->estado)) }}</div></div>
          <div><strong>Donación</strong><div>#{{ $entrega->id_donacion }} · {{ $entrega->tipo_donacion }} {{ $entrega->tipo_bien ? '· '.$entrega->tipo_bien : '' }}</div></div>
          <div><strong>Donador</strong><div>{{ trim($entrega->donador) }}</div></div>
          <div><strong>Campaña</strong><div>{{ $entrega->campana }}</div></div>
          <div><strong>Beneficiario</strong><div>{{ trim($entrega->beneficiario) }}</div></div>
          <div><strong>Cantidad entregada</strong><div>{{ $entrega->cantidad_entregada }} {{ $entrega->unidad_medida }}</div></div>
          <div><strong>Fecha de entrega</strong><div>{{ $entrega->fecha_entrega }}</div></div>
          <div><strong>Responsable</strong><div>{{ trim($entrega->responsable) }}</div></div>
          <div class="form-group full"><strong>Observaciones</strong><div>{{ $entrega->observaciones ?: '—' }}</div></div>
          <div class="form-group full"><strong>Evidencia</strong><div>@if ($entrega->evidencia_url)<a href="{{ $entrega->evidencia_url }}" target="_blank" rel="noopener noreferrer">{{ $entrega->evidencia_url }}</a>@else — @endif</div></div>
        </div>
      </div>
    </div>
  </div>
</div></div>
@endsection
