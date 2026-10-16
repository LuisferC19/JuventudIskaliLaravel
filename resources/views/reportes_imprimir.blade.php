<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $titulo }} | Juventud Iskali</title>
  <style>
    :root { --verde:#0aafa0; --text:#1a2e2c; --muted:#5a8a84; --border:#d0ecea; --fondo:#f4fffe; }
    * { box-sizing:border-box; }
    body { margin:0; color:var(--text); background:var(--fondo); font:14px Arial,sans-serif; }
    .report-header { padding:18px 32px; background:var(--verde); color:#fff; display:flex; justify-content:space-between; }
    .report-header .org { font-size:12px; opacity:.9; }
    .report-header .date { font-size:11px; text-align:right; }
    main { max-width:1200px; margin:24px auto; padding:0 24px; }
    h1 { margin:0 0 8px; font-size:20px; text-transform:uppercase; }
    .subtitle { color:var(--muted); margin-bottom:20px; }
    .summary { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
    .summary span { border:1px solid var(--border); background:#fff; border-radius:6px; padding:8px 12px; }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th,td { border:1px solid var(--border); padding:9px 10px; text-align:left; }
    th { background:#e8f9f7; font-size:12px; }
    tr:nth-child(even) { background:#f8fffe; }
    .toolbar { margin:16px 0; display:flex; gap:8px; }
    button,a { border:0; border-radius:5px; padding:9px 14px; color:#fff; background:var(--verde); text-decoration:none; cursor:pointer; }
    a { background:#5a8a84; }
    @media print {
      body { background:#fff; }
      main { max-width:none; margin:12px 0; padding:0; }
      .toolbar { display:none; }
      .report-header { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
      th { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
    }
  </style>
</head>
<body>
  <header class="report-header">
    <div><strong>Fundación Juventud Iskali</strong><div class="org">Reporte del sistema</div></div>
    <div class="date">Generado: {{ now()->format('d/m/Y H:i') }}</div>
  </header>
  <main>
    <div class="toolbar">
      <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
      <a href="{{ url('/reportes') }}" onclick="window.close(); return false;">Cerrar</a>
    </div>
    <h1>{{ $titulo }}</h1>
    @if ($filtros['desde'] || $filtros['hasta'])
      <div class="subtitle">
        Periodo: {{ $filtros['desde'] ?: '—' }} al {{ $filtros['hasta'] ?: '—' }}
      </div>
    @endif
    <div class="summary">
      @foreach ($summary as $label => $value)
        <span><strong>{{ $label }}:</strong> {{ $value }}</span>
      @endforeach
    </div>
    <table>
      <thead>
        @if ($tipo === 'donadores')
          <tr><th>#</th><th>Nombre / Razón social</th><th>Correo electrónico</th><th>Teléfono</th><th>Tipo</th><th>Estatus</th></tr>
        @elseif ($tipo === 'beneficiarios')
          <tr><th>#</th><th>Nombre</th><th>Apellidos</th><th>Edad</th><th>Municipio</th><th>Estatus</th><th>Tipo</th></tr>
        @else
          <tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Inicio</th><th>Cierre</th><th>Estatus</th><th>Meta económica</th></tr>
        @endif
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            <td>{{ $loop->iteration }}</td>
            @if ($tipo === 'donadores')
              <td>{{ $row['nombre'] }}</td><td>{{ $row['email'] }}</td><td>{{ $row['telefono'] }}</td><td>{{ $row['tipo'] }}</td><td>{{ $row['estatus'] }}</td>
            @elseif ($tipo === 'beneficiarios')
              <td>{{ $row['nombre'] }}</td><td>{{ $row['apellidos'] }}</td><td>{{ $row['edad'] !== null && $row['edad'] !== '' ? $row['edad'].' años' : '—' }}</td><td>{{ $row['municipio'] }}</td><td>{{ $row['estatus'] }}</td><td>{{ $row['tipo'] }}</td>
            @else
              <td>{{ $row['nombre'] }}</td><td>{{ $row['descripcion'] }}</td><td>{{ $row['fecha_inicio'] }}</td><td>{{ $row['fecha_cierre'] }}</td><td>{{ $row['estatus'] }}</td><td>${{ number_format($row['meta'], 2) }}</td>
            @endif
          </tr>
        @empty
          <tr><td colspan="{{ $tipo === 'beneficiarios' ? 7 : ($tipo === 'campanas' ? 7 : 6) }}">No hay registros para el periodo seleccionado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </main>
</body>
</html>
