@extends('layouts.app')
@section('title', 'Inventario | Juventud Iskali')
@section('page-title', $historial ? 'Movimientos de inventario' : 'Inventario')
@section('content')
<div class="main">
  @include('partials.topbar')
  <div class="content">
    @if (session('success'))
      <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger" role="alert">
        <ul style="margin:0;padding-left:20px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if ($historial)
      <div class="card">
        <div class="card-header">
          <h3>Historial de movimientos</h3>
          <div class="toolbar">
            <a class="btn btn-secondary" href="{{ route('inventario.index') }}">Volver al inventario</a>
          </div>
        </div>
        <form method="GET" action="{{ route('inventario.movimientos') }}" class="toolbar" style="padding:16px;">
          <label for="filtro-tipo-bien">Tipo de bien</label>
          <select id="filtro-tipo-bien" name="tipo_bien">
            <option value="">Todos</option>
            @foreach ($inventario as $bien)
              <option value="{{ $bien->id_tipo_bien }}" @selected((string) ($tipoBienSeleccionado ?? '') === (string) $bien->id_tipo_bien)>
                {{ $bien->tipo_bien }}
              </option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
        @include('partials.inventario_movimientos')
      </div>
    @else
      <div class="card">
        <div class="card-header">
          <h3>Inventario por tipo de bien</h3>
          <div class="toolbar">
            <a class="btn btn-secondary btn-sm" href="{{ route('inventario.movimientos') }}">Ver historial</a>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Tipo de bien</th>
              <th>Unidad de medida</th>
              <th>Recibido</th>
              <th>Entregado</th>
              <th>Disponible</th>
              <th>Última actualización</th>
              <th>Actualizado por</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($inventario as $bien)
              <tr>
                <td>{{ $bien->tipo_bien }}</td>
                <td>{{ $bien->unidad_medida }}</td>
                <td>{{ number_format((float) $bien->cantidad_total_recibida, 2) }}</td>
                <td>{{ number_format((float) $bien->cantidad_total_entregada, 2) }}</td>
                <td style="{{ (float) $bien->cantidad_disponible === 0.0 ? 'color:var(--danger);font-weight:700;' : 'font-weight:600;' }}">
                  {{ number_format((float) $bien->cantidad_disponible, 2) }}
                </td>
                <td>{{ $bien->ultima_actualizacion ?? '—' }}</td>
                <td>{{ $bien->actualizado_por ?: '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="7" style="text-align:center;color:var(--muted);">No hay tipos de bien registrados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Movimientos recientes</h3>
          <a class="btn btn-secondary btn-sm" href="{{ route('inventario.movimientos') }}">Ver todos</a>
        </div>
        @include('partials.inventario_movimientos')
      </div>

      @if (usuarioPuede('inventario', 'editar'))
        <div class="card">
          <div class="card-header"><h3>Ajuste manual</h3></div>
          <form method="POST" action="{{ route('inventario.ajustar') }}" style="padding:16px;">
            @csrf
            <div class="form-grid">
              <div class="form-group">
                <label for="ajuste-tipo">Tipo de bien *</label>
                <select id="ajuste-tipo" name="id_tipo_bien" required>
                  @foreach ($inventario as $bien)
                    <option value="{{ $bien->id_tipo_bien }}" @selected((string) old('id_tipo_bien') === (string) $bien->id_tipo_bien)>
                      {{ $bien->tipo_bien }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="ajuste-recibida">Cantidad recibida nueva *</label>
                <input id="ajuste-recibida" type="number" name="cantidad_total_recibida" min="0" step="0.01" value="{{ old('cantidad_total_recibida') }}" required>
              </div>
              <div class="form-group">
                <label for="ajuste-entregada">Cantidad entregada nueva *</label>
                <input id="ajuste-entregada" type="number" name="cantidad_total_entregada" min="0" step="0.01" value="{{ old('cantidad_total_entregada') }}" required>
              </div>
              <div class="form-group" style="grid-column:1/-1;">
                <label for="ajuste-motivo">Motivo *</label>
                <textarea id="ajuste-motivo" name="motivo" rows="3" required>{{ old('motivo') }}</textarea>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Registrar ajuste</button>
          </form>
        </div>
      @endif
    @endif
  </div>
</div>
@endsection
