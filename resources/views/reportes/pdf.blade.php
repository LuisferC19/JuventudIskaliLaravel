<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 28px 34px 42px 34px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #173b37; }
    .watermark { position: fixed; width: 360px; height: 360px; left: 50%; top: 50%; margin-left: -180px; margin-top: -180px; opacity: 0.08; z-index: -1; }
    .header { border-bottom: 3px solid #0aafa0; padding-bottom: 8px; margin-bottom: 10px; }
    .logo { float: left; width: 58px; height: 58px; margin-right: 12px; }
    h1 { font-size: 18px; margin: 4px 0 2px; }
    .meta { float: right; width: 230px; text-align: right; font-size: 9px; }
    .clear { clear: both; }
    .filters { background: #edf8f6; border: 1px solid #b8dcd7; padding: 7px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
    th { background: #0aafa0; color: #fff; padding: 7px 5px; border: 1px solid #087f74; text-align: left; }
    td { border: 1px solid #cbdedb; padding: 6px 5px; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #edf8f6; }
  </style>
</head>
<body>
  @if (imagen_base64('/img/Logo.jpeg'))
    <img class="watermark" src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="">
  @endif
  <div class="header">
    @if (imagen_base64('/img/Logo.jpeg'))
      <img class="logo" src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="Logo">
    @endif
    <div class="meta">
      Generado por: {{ auth()->user()?->nombre }} {{ auth()->user()?->apellido }}<br>
      {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i') }}
    </div>
    <h1>{{ config('iskali.institucion.nombre') }}</h1>
    <strong>Reporte de {{ ucfirst(str_replace('_', ' ', $tipo)) }}</strong>
  </div>
  <div class="clear"></div>
  <div class="filters">
    <strong>Filtros aplicados:</strong>
    @if ($desde) Desde {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }} @endif
    @if ($hasta) @if ($desde) - @endif Hasta {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }} @endif
    @if ($region) | Región: {{ $region === 'san_martin' ? 'San Martín Texmelucan' : 'Tlaxcala' }} @endif
    @if ($producto) | Producto: {{ $producto }} @endif
    @if (! $desde && ! $hasta && ! $region && ! $producto) Todos los registros @endif
  </div>
  <table>
    <thead>
      <tr>
        @foreach (array_keys($filas->first() ?? []) as $columna)
          <th>{{ ucfirst(str_replace('_', ' ', $columna)) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($filas as $fila)
        <tr>
          @foreach ($fila as $valor)
            <td>{{ $valor ?? '—' }}</td>
          @endforeach
        </tr>
      @empty
        <tr><td colspan="{{ max(1, $columnas) }}">No hay registros para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>
  <script type="text/php">
    if (isset($pdf)) {
      $font = $fontMetrics->getFont("DejaVu Sans");
      $size = 9;
      $y = $pdf->get_height() - 25;
      $x = $pdf->get_width() - 150;
      $pdf->page_text($x, $y, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size, array(0.08, 0.40, 0.36));
    }
  </script>
</body>
</html>
