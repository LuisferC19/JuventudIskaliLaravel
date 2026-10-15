@extends('layouts.app')
@section('title', 'Gamificación | Juventud Iskali')
@section('page-title', 'Gamificación')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- KPIs de gamificación -->
      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">Niveles definidos</div>
          <div class="kpi-value">4</div>
        </div>
        <div class="kpi amber">
          <div class="kpi-label">Insignias activas</div>
          <div class="kpi-value">8</div>
        </div>
        <div class="kpi blue">
          <div class="kpi-label">Reconocimientos emitidos</div>
          <div class="kpi-value">23</div>
        </div>
        <div class="kpi pink">
          <div class="kpi-label">Donadores con nivel</div>
          <div class="kpi-value">86</div>
        </div>
      </div>

      <!-- Dos columnas: Niveles + Insignias -->
      <div class="two-col">

        <!-- Tabla de niveles -->
        <div class="card">
          <div class="card-header">
            <h3>Niveles de gamificación</h3>
            <button class="btn btn-primary" onclick="showToast('Módulo de niveles — próximamente')">+ Nuevo nivel</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Pts mínimos</th>
                <th>Pts máximos</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>#N1</td>
                <td><span class="badge badge-gray">Bronce</span></td>
                <td>0</td>
                <td>99</td>
                <td>Primer nivel</td>
              </tr>
              <tr>
                <td>#N2</td>
                <td><span class="badge badge-amber">Plata</span></td>
                <td>100</td>
                <td>499</td>
                <td>Donador frecuente</td>
              </tr>
              <tr>
                <td>#N3</td>
                <td><span class="badge badge-blue">Oro</span></td>
                <td>500</td>
                <td>999</td>
                <td>Donador destacado</td>
              </tr>
              <tr>
                <td>#N4</td>
                <td><span class="badge badge-pink">Diamante</span></td>
                <td>1000</td>
                <td>—</td>
                <td>Donador élite</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Grid de insignias -->
        <div class="card">
          <div class="card-header">
            <h3>Insignias</h3>
            <button class="btn btn-primary" onclick="showToast('Módulo de insignias — próximamente')">+ Nueva insignia</button>
          </div>
          <div class="insignia-grid">
            <div class="ins-card">
              <div class="ins-icon">🌱</div>
              <div class="ins-name">Primera donación</div>
              <div class="ins-pts">+10 pts</div>
              <div class="ins-status"><span class="badge badge-green">Activa</span></div>
            </div>
            <div class="ins-card">
              <div class="ins-icon">🔥</div>
              <div class="ins-name">Donador frecuente</div>
              <div class="ins-pts">+50 pts</div>
              <div class="ins-status"><span class="badge badge-green">Activa</span></div>
            </div>
            <div class="ins-card">
              <div class="ins-icon">⭐</div>
              <div class="ins-name">Donador destacado</div>
              <div class="ins-pts">+100 pts</div>
              <div class="ins-status"><span class="badge badge-green">Activa</span></div>
            </div>
            <div class="ins-card">
              <div class="ins-icon">💎</div>
              <div class="ins-name">Gran contribuidor</div>
              <div class="ins-pts">+200 pts</div>
              <div class="ins-status"><span class="badge badge-green">Activa</span></div>
            </div>
            <div class="ins-card">
              <div class="ins-icon">🏅</div>
              <div class="ins-name">Campaña completada</div>
              <div class="ins-pts">+75 pts</div>
              <div class="ins-status"><span class="badge badge-green">Activa</span></div>
            </div>
            <div class="ins-card" onclick="openModal('modal-reconocimiento')" style="cursor:pointer;">
              <div class="ins-icon">+</div>
              <div class="ins-name">Asignar insignia</div>
              <div class="ins-pts">a donador</div>
            </div>
          </div>
        </div>

      </div><!-- /two-col -->

      <!-- Reconocimientos emitidos -->
      <div class="card">
        <div class="card-header">
          <h3>Reconocimientos emitidos</h3>
          <div style="display:flex;gap:8px;">
            <select id="filtro-tipo-reconocimiento" onchange="filtrarReconocimientos()" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
              <option value="">Todos los tipos</option>
              <option value="diploma">Diploma</option>
              <option value="carta">Carta</option>
              <option value="certificado">Certificado</option>
              <option value="voluntario_mes">Voluntario del Mes</option>
              <option value="mayor_asistencia">Mayor Asistencia</option>
              <option value="mayor_entregas">Mayor Entregas</option>
              <option value="donador_destacado">Donador Destacado</option>
              <option value="otro">Otro</option>
            </select>
            <button class="btn btn-primary" onclick="openModal('modal-reconocimiento')">+ Nuevo reconocimiento</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Tipo</th>
              <th>Receptor</th>
              <th>Descripción</th>
              <th>Campaña</th>
              <th>Emitido por</th>
              <th>Fecha</th>
              <th>PDF</th>
            </tr>
          </thead>
          <tbody id="tabla-reconocimientos">
            <tr>
              <td>#RC001</td>
              <td><span class="badge badge-blue">Diploma</span></td>
              <td>María García</td>
              <td>Reconocimiento por participación en campaña de invierno</td>
              <td>Invierno 2026</td>
              <td>Eva Sánchez</td>
              <td>2026-03-10</td>
              <td><a href="#" style="color:var(--accent);font-size:11px;">Descargar</a></td>
            </tr>
            <tr>
              <td>#RC002</td>
              <td><span class="badge badge-gold">Donador Destacado</span></td>
              <td>Empresa Alfa</td>
              <td>Donador más activo del trimestre</td>
              <td>—</td>
              <td>Eva Sánchez</td>
              <td>2026-03-15</td>
              <td><a href="#" style="color:var(--accent);font-size:11px;">Descargar</a></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal para emitir reconocimiento -->
      <div id="modal-reconocimiento" class="modal" style="display:none;">
        <div class="modal-overlay" onclick="closeModal('modal-reconocimiento')"></div>
        <div class="modal-content" style="width:90%;max-width:500px;">
          <div class="modal-header">
            <h3>Emitir Reconocimiento</h3>
            <button onclick="closeModal('modal-reconocimiento')" class="modal-close">×</button>
          </div>
          <div class="modal-body" style="padding:20px;">
            <form method="POST" action="#">
              @csrf
              <div class="form-group">
                <label for="tipo-reconoc">Tipo de Reconocimiento <span style="color:#f44336;">*</span></label>
                <select id="tipo-reconoc" name="tipo" required style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;">
                  <option value="">— Selecciona un tipo —</option>
                  <option value="diploma">Diploma</option>
                  <option value="carta">Carta</option>
                  <option value="certificado">Certificado</option>
                  <option value="voluntario_mes">Voluntario del Mes</option>
                  <option value="mayor_asistencia">Mayor Asistencia</option>
                  <option value="mayor_entregas">Mayor Entregas</option>
                  <option value="donador_destacado">Donador Destacado</option>
                  <option value="otro">Otro</option>
                </select>
              </div>

              <div class="form-group">
                <label>Receptor <span style="color:#f44336;">*</span></label>
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                  <label style="display:flex;align-items:center;gap:6px;">
                    <input type="radio" name="tipo-receptor" value="donador" checked onchange="cambiarTipoReceptor()"> Donador
                  </label>
                  <label style="display:flex;align-items:center;gap:6px;">
                    <input type="radio" name="tipo-receptor" value="usuario" onchange="cambiarTipoReceptor()"> Usuario/Voluntario
                  </label>
                </div>
                <input type="text" id="buscar-receptor" placeholder="Buscar por nombre o email..." style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;margin-bottom:10px;">
                <select id="select-receptor" name="id_receptor" required style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;">
                  <option value="">— Selecciona —</option>
                </select>
              </div>

              <div class="form-group">
                <label for="desc-reconoc">Descripción</label>
                <textarea id="desc-reconoc" name="descripcion" rows="3" placeholder="Describe brevemente el reconocimiento..." style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;resize:vertical;"></textarea>
              </div>

              <div class="form-group">
                <label for="campana-reconoc">Campaña (opcional)</label>
                <select id="campana-reconoc" name="id_campana" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;">
                  <option value="">— Sin campaña asociada —</option>
                  <option value="1">Campaña Invierno 2026</option>
                </select>
              </div>

              <div class="form-group">
                <label for="pdf-reconoc">Subir PDF (opcional)</label>
                <input type="file" id="pdf-reconoc" name="archivo_pdf" accept=".pdf" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;">
              </div>

              <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Emitir reconocimiento</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-reconocimiento')" style="flex:1;">Cancelar</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function filtrarReconocimientos() {
  const tipo = document.getElementById('filtro-tipo-reconocimiento').value;
  showToast('Filtro aplicado: ' + (tipo || 'Todos'));
}

function cambiarTipoReceptor() {
  const tipo = document.querySelector('input[name="tipo-receptor"]:checked').value;
  showToast('Buscando ' + (tipo === 'donador' ? 'donadores' : 'usuarios'));
}

function openModal(id) {
  document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

function emitirReconocimiento(e) {
  e.preventDefault();
  const tipo = document.getElementById('tipo-reconoc').value;
  const receptor = document.getElementById('select-receptor').value;
  const descripcion = document.getElementById('desc-reconoc').value;
  
  if (!tipo || !receptor) {
    showToast('Por favor completa los campos requeridos', 'error');
    return;
  }
  
  showToast('Reconocimiento emitido correctamente', 'success');
  closeModal('modal-reconocimiento');
  document.getElementById('form-reconocimiento').reset();
}
</script>

@endsection
