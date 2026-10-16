@extends('layouts.app')
@section('title', 'Reportes | Juventud Iskali')
@section('page-title', 'Reportes')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- ══ Encabezado ══ -->
      <div style="margin-bottom:1.1rem;">
        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px;">
          Reportes
        </h2>
        <p style="font-size:12.5px;color:var(--muted);max-width:620px;line-height:1.6;">
          Genera reportes en formato PDF de los módulos del sistema. Los archivos se abren directamente en tu navegador y puedes descargarlos o imprimirlos.
        </p>
      </div>

      <!-- ══ Tarjetas de reportes ══ -->
      <div class="report-grid">

        <!-- ── Donadores ── -->
        <div class="card report-card">
          <div class="report-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <div>
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text);margin:0 0 6px;">
              Donadores
            </h3>
            <p style="font-size:12px;color:var(--muted);line-height:1.6;margin:0;">
              Listado completo de todos los donadores registrados con nombre, correo, teléfono y tipo.
            </p>
          </div>
          <!-- RF005: filtro de fechas opcional (sobre fecha de registro). -->
          <form method="GET" action="{{ route('reportes.generar') }}" target="_blank">
            <input type="hidden" name="tipo"   value="donadores">
            <div style="display:flex;gap:6px;">
              <input type="date" name="desde" title="Registrado desde" class="form-control" style="font-size:11.5px;padding:6px 8px;">
              <input type="date" name="hasta" title="Registrado hasta" class="form-control" style="font-size:11.5px;padding:6px 8px;">
            </div>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                <path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/>
                <path d="M6 18v4h12v-4"/>
              </svg>
              Generar reporte
            </button>
          </form>
        </div>

        <!-- ── Beneficiarios ── -->
        <div class="card report-card">
          <div class="report-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;">
              <circle cx="9" cy="7" r="4"/>
              <path d="M3 21v-2a4 4 0 0 1 4-4h4"/>
              <circle cx="18" cy="11" r="3"/>
              <path d="M14 21v-1a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3v1"/>
            </svg>
          </div>
          <div>
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text);margin:0 0 6px;">
              Beneficiarios
            </h3>
            <p style="font-size:12px;color:var(--muted);line-height:1.6;margin:0;">
              Listado de beneficiarios con nombre, tipo, edad, municipio y estatus de atención.
            </p>
          </div>
          <!-- RF005: filtro de fechas opcional (sobre fecha de registro). -->
          <form method="GET" action="{{ route('reportes.generar') }}" target="_blank">
            <input type="hidden" name="tipo"   value="beneficiarios">
            <div style="display:flex;gap:6px;">
              <input type="date" name="desde" title="Registrado desde" class="form-control" style="font-size:11.5px;padding:6px 8px;">
              <input type="date" name="hasta" title="Registrado hasta" class="form-control" style="font-size:11.5px;padding:6px 8px;">
            </div>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                <path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/>
                <path d="M6 18v4h12v-4"/>
              </svg>
              Generar reporte
            </button>
          </form>
        </div>

        <!-- ── Campañas ── -->
        <div class="card report-card">
          <div class="report-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <div>
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text);margin:0 0 6px;">
              Campañas
            </h3>
            <p style="font-size:12px;color:var(--muted);line-height:1.6;margin:0;">
              Reporte de todas las campañas con nombre, fechas, estatus y meta económica acumulada.
            </p>
          </div>
          <!-- RF005: filtro de fechas opcional (sobre la fecha de inicio de la campaña). -->
          <form method="GET" action="{{ route('reportes.generar') }}" target="_blank">
            <input type="hidden" name="tipo"   value="campanas">
            <div style="display:flex;gap:6px;">
              <input type="date" name="desde" title="Inicio de campaña desde" class="form-control" style="font-size:11.5px;padding:6px 8px;">
              <input type="date" name="hasta" title="Inicio de campaña hasta" class="form-control" style="font-size:11.5px;padding:6px 8px;">
            </div>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                <path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/>
                <path d="M6 18v4h12v-4"/>
              </svg>
              Generar reporte
            </button>
          </form>
        </div>

      </div><!-- /grid -->

      <!-- ══ Nota informativa ══ -->
      <div class="alert alert-info" style="margin-top:1rem;line-height:1.7;">
        <strong>Nota:</strong>
        Los reportes se abren en una nueva pestaña del navegador. Desde ahí puedes imprimirlos o guardarlos como PDF usando la opción de impresión del navegador
        (<kbd>Ctrl+P</kbd>).
        Los datos corresponden a la información actual registrada en el sistema.
      </div>

      
    </div><!-- /content -->
  </div><!-- /main -->
</div>
@endsection
