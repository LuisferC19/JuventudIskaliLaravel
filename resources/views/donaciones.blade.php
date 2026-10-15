@extends('layouts.app')
@section('title', 'Donaciones | Juventud Iskali')
@section('page-title', 'Donaciones')
@section('content')
<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="kpi-grid">
      <div class="kpi"><div class="kpi-label">Total donaciones</div><div class="kpi-value">{{ $totalDonaciones }}</div></div>
      <div class="kpi amber"><div class="kpi-label">Pendientes</div><div class="kpi-value">{{ $donacionesPendientes }}</div></div>
      <div class="kpi blue"><div class="kpi-label">Recibidas</div><div class="kpi-value">{{ $donacionesRecibidas }}</div></div>
      <div class="kpi"><div class="kpi-label">Verificadas</div><div class="kpi-value">{{ $donacionesVerificadas }}</div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>Donaciones</h3>
        <div class="toolbar">
          <input type="text" class="search-input" placeholder="Buscar..." oninput="filtrarTabla(this,'tabla-donaciones')">
          <form method="GET" action="{{ route('donaciones.index') }}"><select name="estado" onchange="this.form.submit()" style="width:150px;">
            <option value="">Todos los estados</option>
            @foreach (['pendiente', 'recibida', 'verificada', 'rechazada'] as $estado)
              <option value="{{ $estado }}" @selected($filtroEstado === $estado)>{{ ucfirst($estado) }}</option>
            @endforeach
          </select></form>
          @if (usuarioPuede('donaciones', 'crear'))<a class="btn btn-primary" href="{{ route('donaciones.create') }}">+ Nueva donación</a>@endif
        </div>
      </div>
      <table id="tabla-donaciones">
        <thead><tr><th>ID</th><th>Donador</th><th>Tipo</th><th>Cantidad / monto</th><th>Fecha recepción</th><th>Campaña</th><th>Registrado por</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($donaciones as $donacion)
            <tr>
              <td>#{{ $donacion->id_donacion }}</td>
              <td>{{ trim($donacion->donador) }}</td>
              <td>{{ $donacion->tipo === 'especie' ? ($donacion->detalle ?? 'En especie') : 'Económica' }}</td>
              <td>{{ $donacion->tipo === 'especie' ? $donacion->cantidad_monto.' '.$donacion->unidad_medida : '$'.$donacion->cantidad_monto.' '.$donacion->unidad_medida }}</td>
              <td>{{ $donacion->fecha_recepcion }}</td>
              <td>{{ $donacion->campana }}</td>
              <td>{{ trim($donacion->registrado_por) }}</td>
              <td><span class="badge {{ $donacion->estado === 'verificada' ? 'badge-green' : ($donacion->estado === 'rechazada' ? 'badge-red' : ($donacion->estado === 'recibida' ? 'badge-blue' : 'badge-amber')) }}">{{ ucfirst($donacion->estado) }}</span></td>
              <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="{{ url('/donaciones/'.$donacion->id_donacion) }}">Ver</a>
                @if ($donacion->estado === 'pendiente' && usuarioPuede('donaciones', 'editar'))
                  <a class="btn btn-secondary btn-sm" href="{{ route('donaciones.edit', $donacion->id_donacion) }}">Editar</a>
                @endif
                @if (usuarioPuede('donaciones', 'editar'))
                  @php
                    $transiciones = $donacion->estado === 'pendiente' ? ['recibida'] : ($donacion->estado === 'recibida' ? ['verificada', 'rechazada'] : []);
                  @endphp
                  @foreach ($transiciones as $estadoSiguiente)
                    <form method="POST" action="{{ route('donaciones.cambiarEstado', $donacion->id_donacion) }}" style="display:inline;">
                      @csrf
                      <input type="hidden" name="estado" value="{{ $estadoSiguiente }}">
                      <button class="btn btn-primary btn-sm" type="submit">{{ ucfirst($estadoSiguiente) }}</button>
                    </form>
                  @endforeach
                @endif
                @if ($donacion->estado === 'pendiente' && usuarioPuede('donaciones', 'eliminar'))
                  <form method="POST" action="{{ route('donaciones.destroy', $donacion->id_donacion) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar esta donación pendiente?')">
                    @csrf @method('DELETE')<button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9">No hay donaciones para mostrar.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div></div>
@endsection
