@extends('layouts.app')
@section('title', 'Reportes | Juventud Iskali')
@section('page-title', 'Reportes')
@section('content')
<div class="main">
  @include('partials.topbar')
  <div class="content">
    <div class="card" style="max-width:760px;">
      <div class="card-header"><h3>Generar reporte ejecutivo</h3></div>
      <form method="GET" action="{{ route('reportes.generar') }}" target="_blank" style="padding:18px;">
        @csrf
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="form-grid">
          <div class="form-group">
            <label for="tipo-reporte">Tipo</label>
            <select id="tipo-reporte" name="tipo" class="form-control" required>
              <option value="donadores">Donadores</option>
              <option value="beneficiarios">Beneficiarios</option>
              <option value="campanas">Campañas</option>
              <option value="donaciones">Donaciones</option>
              <option value="inventario">Inventario</option>
              <option value="voluntarios">Voluntarios</option>
            </select>
          </div>
          <div class="form-group" id="campo-desde">
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" value="{{ old('desde') }}" class="form-control">
          </div>
          <div class="form-group" id="campo-hasta">
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="{{ old('hasta') }}" class="form-control">
          </div>
          <div class="form-group" id="campo-region">
            <label for="region">Región</label>
            <select id="region" name="region" class="form-control">
              <option value="">Todas</option>
              <option value="san_martin">San Martín Texmelucan</option>
              <option value="tlaxcala">Tlaxcala</option>
            </select>
          </div>
          <div class="form-group" id="campo-producto">
            <label for="producto">Producto</label>
            <input id="producto" type="text" name="producto" maxlength="100" value="{{ old('producto') }}" class="form-control" placeholder="Ej. arroz">
          </div>
        </div>
        <input type="hidden" id="formato-reporte" name="formato" value="pdf">
        <div style="display:flex;gap:10px;margin-top:16px;">
          <button type="submit" class="btn btn-primary" onclick="document.getElementById('formato-reporte').value='pdf'">Descargar PDF</button>
          <button type="submit" class="btn btn-secondary" onclick="document.getElementById('formato-reporte').value='html'">Ver en pantalla</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function actualizarFiltrosReporte() {
  const tipo = document.getElementById('tipo-reporte').value;
  document.getElementById('campo-desde').style.display = tipo === 'inventario' ? 'none' : '';
  document.getElementById('campo-hasta').style.display = tipo === 'inventario' ? 'none' : '';
  document.getElementById('campo-region').style.display = (tipo === 'beneficiarios' || tipo === 'voluntarios') ? '' : 'none';
  document.getElementById('campo-producto').style.display = tipo === 'donaciones' ? '' : 'none';
}
document.getElementById('tipo-reporte').addEventListener('change', actualizarFiltrosReporte);
actualizarFiltrosReporte();
</script>
@endsection
