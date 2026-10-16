<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reporte de {{ ucfirst($tipo) }} | Juventud Iskali</title>
  <style>
    body{font-family:Arial,sans-serif;color:#1a2e2c;margin:32px}
    header{display:flex;justify-content:space-between;border-bottom:2px solid #0aafa0;padding-bottom:12px}
    h1{font-size:22px;margin:0 0 8px}
    p{color:#5a8a84;margin:4px 0}
    table{width:100%;border-collapse:collapse;margin-top:24px;font-size:13px}
    th,td{border:1px solid #d0ecea;padding:8px;text-align:left}
    th{background:#e8f9f7}
    @media print{body{margin:12mm}.no-print{display:none}}
  </style>
</head>
<body>
  <header>
    <div><h1>Reporte de {{ ucfirst($tipo) }}</h1><p>Juventud Iskali</p></div>
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
  </header>
  <p>
    Periodo:
    {{ $desde ? \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') : 'inicio' }}
    a
    {{ $hasta ? \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') : 'actualidad' }}
  </p>
  <button class="no-print" type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
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
        <tr><td colspan="8">No hay registros para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
