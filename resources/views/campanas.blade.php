@extends('layouts.app')
@section('title', 'Campañas | Juventud Iskali')
@section('page-title', 'Campañas')
@section('content')
<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">
        <div class="kpi">
          <div class="kpi-label">Total</div>
          <div class="kpi-value">{{ $totalCampanas }}</div>
        </div>
        <div class="kpi amber">
          <div class="kpi-label">Activas</div>
          <div class="kpi-value">{{ $campanasActivas }}</div>
        </div>
        <div class="kpi blue">
          <div class="kpi-label">Cerradas</div>
          <div class="kpi-value">{{ $campanasCerradas }}</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Campañas</h3>
          <div class="toolbar">
            <input type="text" class="search-input" placeholder="Buscar campaña..." oninput="filtrarTabla(this,'tabla-campanas')">
            <select style="width:130px;" onchange="filtrarTabla(this,'tabla-campanas')">
              <option value="">Todos los estados</option>
              @foreach (['borrador' => 'Borrador', 'activa' => 'Activa', 'pausada' => 'Pausada', 'cerrada' => 'Cerrada', 'cancelada' => 'Cancelada'] as $valor => $etiqueta)
                <option value="{{ $etiqueta }}">{{ $etiqueta }}</option>
              @endforeach
            </select>
            @if (usuarioPuede('campanas', 'crear'))
              <a class="btn btn-primary" href="{{ route('campanas.create') }}">+ Nueva campaña</a>
            @endif
          </div>
        </div>

        <table id="tabla-campanas">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Tipo meta</th>
              <th>Meta económica</th>
              <th>Inicio</th>
              <th>Cierre</th>
              <th>Estado</th>
              <th>Creada por</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($campanas as $campana)
              @php
                $badgeEstado = match ($campana->estado) {
                    'activa' => 'badge-green',
                    'borrador', 'pausada' => 'badge-amber',
                    'cerrada' => 'badge-gray',
                    default => 'badge-red',
                };
                $transiciones = [
                    'borrador' => ['activa'],
                    'activa' => ['pausada', 'cerrada'],
                    'pausada' => ['activa', 'cancelada'],
                    'cerrada' => [],
                    'cancelada' => [],
                ][$campana->estado] ?? [];
              @endphp
              <tr>
                <td>#C{{ str_pad((string) $campana->id_campana, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $campana->nombre }}</td>
                <td>{{ ucfirst($campana->tipo_meta) }}</td>
                <td>{{ number_format((float) $campana->meta_economica, 2) }}</td>
                <td>{{ $campana->fecha_inicio->format('Y-m-d') }}</td>
                <td>{{ $campana->fecha_cierre->format('Y-m-d') }}</td>
                <td><span class="badge {{ $badgeEstado }}">{{ ucfirst($campana->estado) }}</span></td>
                <td>{{ trim(($campana->creador->nombre ?? '') . ' ' . ($campana->creador->apellido ?? '')) ?: '—' }}</td>
                <td>
                  @if (usuarioPuede('campanas', 'editar'))
                    <a class="btn btn-secondary btn-sm" href="{{ route('campanas.edit', $campana->id_campana) }}">Editar</a>
                    @if ($transiciones)
                      <form method="POST" action="{{ route('campanas.cambiarEstado', $campana->id_campana) }}" style="display:inline-flex;gap:4px;align-items:center;">
                        @csrf
                        <select name="estado" aria-label="Nuevo estado para {{ $campana->nombre }}" required>
                          @foreach ($transiciones as $transicion)
                            <option value="{{ $transicion }}">{{ ucfirst($transicion) }}</option>
                          @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Cambiar estado</button>
                      </form>
                    @endif
                  @endif
                  @if ($campana->estado === 'borrador' && usuarioPuede('campanas', 'eliminar'))
                    <form method="POST" action="{{ route('campanas.destroy', $campana->id_campana) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar esta campaña en borrador?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-secondary btn-sm">Eliminar</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="9">No hay campañas registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="card-header"><h3>Avances recientes</h3></div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Campaña</th>
              <th>Avance %</th>
              <th>Monto recaudado</th>
              <th>Descripción</th>
              <th>Reportado por</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($avances as $avance)
              <tr>
                <td>#AV{{ str_pad((string) $avance->id_avance, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $avance->nombre_campana ?? '—' }}</td>
                <td><b style="color:var(--accent);">{{ number_format((float) $avance->porcentaje_avance, 2) }}%</b></td>
                <td>{{ number_format((float) $avance->monto_recaudado, 2) }}</td>
                <td>{{ $avance->descripcion ?? '—' }}</td>
                <td>{{ $avance->reportado_por ?: '—' }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($avance->fecha_registro)->format('Y-m-d') }}</td>
              </tr>
            @empty
              <tr><td colspan="7">Aún no hay avances registrados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
