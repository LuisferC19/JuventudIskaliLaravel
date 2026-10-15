@extends('layouts.app')
@section('title', 'Entregas | Juventud Iskali')
@section('page-title', 'Entregas')
@section('content')
<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">
      <div class="kpi"><div class="kpi-label">Total entregas</div><div class="kpi-value">{{ $totalEntregas }}</div></div>
      <div class="kpi amber"><div class="kpi-label">Programadas</div><div class="kpi-value">{{ $entregasProgramadas }}</div></div>
      <div class="kpi blue"><div class="kpi-label">En proceso</div><div class="kpi-value">{{ $entregasEnProceso }}</div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>Entregas</h3>
        <div class="toolbar">
          <input type="text" class="search-input" placeholder="Buscar entrega..." oninput="filtrarTabla(this,'tabla-entregas')">
          @if (usuarioPuede('entregas', 'crear'))<a class="btn btn-primary" href="{{ route('entregas.create') }}">+ Nueva entrega</a>@endif
        </div>
      </div>
      <table id="tabla-entregas">
        <thead><tr><th>ID</th><th>Donación</th><th>Beneficiario</th><th>Cantidad</th><th>Fecha entrega</th><th>Responsable</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($entregas as $entrega)
            <tr>
              <td>#{{ $entrega->id_entrega }}</td>
              <td>{{ $entrega->tipo_donacion }} {{ $entrega->tipo_bien ? '· '.$entrega->tipo_bien : '' }} · {{ $entrega->donador }} · {{ $entrega->campana }}</td>
              <td>{{ trim($entrega->beneficiario) }}</td>
              <td>{{ $entrega->cantidad_entregada }} {{ $entrega->unidad_medida }}</td>
              <td>{{ $entrega->fecha_entrega }}</td>
              <td>{{ trim($entrega->responsable) }}</td>
              <td><span class="badge {{ $entrega->estado === 'completada' ? 'badge-green' : ($entrega->estado === 'cancelada' ? 'badge-red' : ($entrega->estado === 'en_proceso' ? 'badge-blue' : 'badge-amber')) }}">{{ ucfirst(str_replace('_', ' ', $entrega->estado)) }}</span></td>
              <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="{{ url('/entregas/'.$entrega->id_entrega) }}">Ver</a>
                @if (in_array($entrega->estado, ['programada', 'en_proceso'], true) && usuarioPuede('entregas', 'editar'))
                  <a class="btn btn-secondary btn-sm" href="{{ route('entregas.edit', $entrega->id_entrega) }}">Editar</a>
                @endif
                @if (usuarioPuede('entregas', 'editar'))
                  @php
                    $transiciones = $entrega->estado === 'programada' ? ['en_proceso', 'completada', 'cancelada'] : ($entrega->estado === 'en_proceso' ? ['completada', 'cancelada'] : ($entrega->estado === 'completada' ? ['cancelada'] : []));
                  @endphp
                  @foreach ($transiciones as $estadoSiguiente)
                    <form method="POST" action="{{ route('entregas.cambiarEstado', $entrega->id_entrega) }}" style="display:inline;">
                      @csrf
                      <input type="hidden" name="estado" value="{{ $estadoSiguiente }}">
                      <button class="btn btn-primary btn-sm" type="submit">{{ ucfirst(str_replace('_', ' ', $estadoSiguiente)) }}</button>
                    </form>
                  @endforeach
                @endif
                @if (in_array($entrega->estado, ['programada', 'en_proceso'], true) && usuarioPuede('entregas', 'eliminar'))
                  <form method="POST" action="{{ route('entregas.destroy', $entrega->id_entrega) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar esta entrega?')">
                    @csrf @method('DELETE')<button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="8">No hay entregas para mostrar.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div></div>
@endsection
