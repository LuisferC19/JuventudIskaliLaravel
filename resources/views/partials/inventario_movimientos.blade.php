<table>
  <thead>
    <tr>
      <th>Movimiento</th>
      <th>Tipo de bien</th>
      <th>Cantidad</th>
      <th>Anterior</th>
      <th>Posterior</th>
      <th>Referencia</th>
      <th>Motivo</th>
      <th>Usuario</th>
      <th>Fecha</th>
      @if ($historial && usuarioPuede('inventario', 'editar'))
        <th>Acciones</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @forelse ($movimientos as $movimiento)
      <tr>
        <td>{{ ucfirst($movimiento->tipo_movimiento) }}</td>
        <td>{{ $movimiento->tipo_bien }}</td>
        <td>{{ number_format((float) $movimiento->cantidad, 2) }} {{ $movimiento->unidad_medida }}</td>
        <td>{{ number_format((float) $movimiento->cantidad_anterior, 2) }}</td>
        <td>{{ number_format((float) $movimiento->cantidad_posterior, 2) }}</td>
        <td>
          {{ ucfirst(str_replace('_', ' ', $movimiento->tipo_referencia)) }}
          {{ $movimiento->id_referencia !== null ? '#' . $movimiento->id_referencia : '' }}
        </td>
        <td>{{ $movimiento->motivo ?: '—' }}</td>
        <td>{{ $movimiento->usuario ?: '—' }}</td>
        <td>{{ $movimiento->fecha }}</td>
        @if ($historial && usuarioPuede('inventario', 'editar'))
          <td>
            @if ($movimiento->puede_revertirse_ajuste)
              <form method="POST" action="{{ route('inventario.revertirAjuste') }}" onsubmit="return confirm('Esto registrará un movimiento compensatorio para revertir el ajuste más reciente. ¿Deseas continuar?');">
                @csrf
                <input type="hidden" name="id_tipo_bien" value="{{ $movimiento->id_tipo_bien }}">
                <button type="submit" class="btn btn-secondary btn-sm">Revertir ajuste</button>
              </form>
            @endif
          </td>
        @endif
      </tr>
    @empty
      <tr><td colspan="{{ $historial && usuarioPuede('inventario', 'editar') ? 10 : 9 }}" style="text-align:center;color:var(--muted);">No hay movimientos registrados.</td></tr>
    @endforelse
  </tbody>
</table>
