@extends('layouts.app')
@section('title', 'Gamificación | Juventud Iskali')
@section('page-title', 'Gamificación')
@section('content')

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div role="tablist" aria-label="Secciones de gamificación" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
        <button type="button" class="btn btn-secondary" role="tab" data-pestana="donadores" aria-selected="true">Donadores</button>
        <button type="button" class="btn btn-secondary" role="tab" data-pestana="voluntarios" aria-selected="false">Voluntarios</button>
        <button type="button" class="btn btn-secondary" role="tab" data-pestana="premios" aria-selected="false">Premios</button>
        <button type="button" class="btn btn-secondary" role="tab" data-pestana="canjes" aria-selected="false">Canjes</button>
      </div>

      @if (session('success'))
        <div role="status" style="padding:12px 16px;margin-bottom:16px;border-radius:8px;background:#dcfce7;color:#166534;">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div role="alert" style="padding:12px 16px;margin-bottom:16px;border-radius:8px;background:#fee2e2;color:#991b1b;">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div role="alert" style="padding:12px 16px;margin-bottom:16px;border-radius:8px;background:#fee2e2;color:#991b1b;">
          @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
      @endif

      <section id="panel-donadores" data-panel="donadores" role="tabpanel">
      <div class="kpi-grid">
        <div class="kpi"><div class="kpi-label">Niveles definidos</div><div class="kpi-value">{{ $totalNiveles }}</div></div>
        <div class="kpi amber"><div class="kpi-label">Insignias activas</div><div class="kpi-value">{{ $insigniasActivas }}</div></div>
        <div class="kpi blue"><div class="kpi-label">Reconocimientos emitidos</div><div class="kpi-value">{{ $reconocimientosEmitidos }}</div></div>
        <div class="kpi pink"><div class="kpi-label">Donadores con nivel</div><div class="kpi-value">{{ $donadoresConNivel }}</div></div>
      </div>

      <div class="two-col">
        <div class="card">
          <div class="card-header"><h3>Niveles de gamificación</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>ID</th><th>Nombre</th><th>Pts mínimos</th><th>Pts máximos</th><th>Descripción</th></tr></thead>
              <tbody>
                @forelse ($niveles as $nivel)
                  <tr>
                    <td>#N{{ $nivel->id_nivel }}</td>
                    <td><span class="badge badge-gray">{{ $nivel->nombre }}</span></td>
                    <td>{{ number_format($nivel->puntos_minimos) }}</td>
                    <td>{{ number_format($nivel->puntos_maximos) }}</td>
                    <td>{{ $nivel->descripcion ?: '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" style="text-align:center;color:var(--muted);">No hay niveles configurados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3>Insignias activas</h3></div>
          <div class="insignia-grid">
            @forelse ($insignias as $insignia)
              <div class="ins-card">
                @if ($insignia->imagen_url && filter_var($insignia->imagen_url, FILTER_VALIDATE_URL) && str_starts_with($insignia->imagen_url, 'https://'))
                  <img class="ins-icon" src="{{ $insignia->imagen_url }}" alt="{{ $insignia->nombre }}" loading="lazy">
                @else
                  <div class="ins-icon" aria-hidden="true">✦</div>
                @endif
                <div class="ins-name">{{ $insignia->nombre }}</div>
                <div class="ins-pts">+{{ number_format($insignia->puntos_otorgados) }} pts</div>
                @if ($insignia->descripcion)<div style="font-size:11px;color:var(--muted);">{{ $insignia->descripcion }}</div>@endif
                <div class="ins-status"><span class="badge badge-green">Activa</span></div>
              </div>
            @empty
              <p style="color:var(--muted);font-size:13px;">No hay insignias activas.</p>
            @endforelse
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Reconocimientos emitidos</h3>
          <select id="filtro-tipo-reconocimiento" aria-label="Filtrar reconocimientos" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
            <option value="">Todos los tipos</option>
            @foreach (['diploma', 'carta', 'certificado', 'voluntario_mes', 'mayor_asistencia', 'mayor_entregas', 'donador_destacado', 'otro'] as $tipo)
              <option value="{{ $tipo }}">{{ str_replace('_', ' ', ucfirst($tipo)) }}</option>
            @endforeach
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table id="tabla-reconocimientos">
            <thead><tr><th>ID</th><th>Tipo</th><th>Receptor</th><th>Descripción</th><th>Campaña</th><th>Emitido por</th><th>Fecha</th><th>PDF</th></tr></thead>
            <tbody>
              @forelse ($reconocimientos as $reconocimiento)
                <tr data-tipo="{{ $reconocimiento->tipo }}">
                  <td>#RC{{ $reconocimiento->id_reconocimiento }}</td>
                  <td><span class="badge badge-blue">{{ str_replace('_', ' ', ucfirst($reconocimiento->tipo)) }}</span></td>
                  <td>{{ $reconocimiento->receptor }}</td>
                  <td>{{ $reconocimiento->descripcion ?: '—' }}</td>
                  <td>{{ $reconocimiento->campana ?: '—' }}</td>
                  <td>{{ $reconocimiento->emisor ?: '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($reconocimiento->fecha_emision)->format('Y-m-d') }}</td>
                  <td>
                    @if ($reconocimiento->archivo_pdf_url && (str_starts_with($reconocimiento->archivo_pdf_url, '/storage/') || (filter_var($reconocimiento->archivo_pdf_url, FILTER_VALIDATE_URL) && str_starts_with($reconocimiento->archivo_pdf_url, 'https://'))))
                      <a href="{{ str_starts_with($reconocimiento->archivo_pdf_url, '/storage/') ? asset(ltrim($reconocimiento->archivo_pdf_url, '/')) : $reconocimiento->archivo_pdf_url }}" target="_blank" rel="noopener">Descargar</a>
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No hay reconocimientos emitidos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      </section>

      <section id="panel-voluntarios" data-panel="voluntarios" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header"><h3>Bolsa de puntos de voluntarios</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Voluntario</th><th>Zona</th><th>Bolsa 1</th><th>Bolsa 2</th><th>Campamento</th></tr></thead>
              <tbody>
                @forelse ($bolsas as $bolsa)
                  <tr>
                    <td>{{ trim($bolsa->nombre.' '.$bolsa->apellido) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($bolsa->zona_asignada)) }}</td>
                    <td>{{ number_format($bolsa->puntos_canjeables) }}</td>
                    <td>{{ number_format($bolsa->puntos_campamento) }}</td>
                    <td>
                      @if ($bolsa->campamento_bloqueado)
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">Bloqueado</span>
                      @elseif ($bolsa->puntos_campamento >= $umbral)
                        <span class="badge badge-green">Califica a campamento</span>
                      @else
                        <span class="badge" style="background:#fef3c7;color:#92400e;">En progreso ({{ $bolsa->puntos_campamento }}/{{ $umbral }})</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" style="text-align:center;color:var(--muted);">No hay voluntarios activos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="panel-premios" data-panel="premios" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header">
            <h3>Catálogo de premios</h3>
            @can('permiso', ['gamificacion', 'crear'])
              <button type="button" class="btn btn-primary" onclick="abrirModalPremio()">Nuevo premio</button>
            @endcan
          </div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Premio</th><th>Descripción</th><th>Costo</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($premios as $premio)
                  <tr>
                    <td>{{ $premio->nombre }}</td>
                    <td>{{ $premio->descripcion ?: '—' }}</td>
                    <td>{{ number_format($premio->costo_puntos) }} pts</td>
                    <td>{{ number_format($premio->stock) }}</td>
                    <td><span class="badge {{ $premio->activo ? 'badge-green' : 'badge-gray' }}">{{ $premio->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td style="white-space:nowrap;">
                      @can('permiso', ['gamificacion', 'editar'])
                        <button type="button" class="btn btn-secondary btn-sm" data-premio="{{ json_encode(['id' => $premio->id_premio, 'nombre' => $premio->nombre, 'descripcion' => $premio->descripcion, 'costo_puntos' => $premio->costo_puntos, 'stock' => $premio->stock, 'activo' => (bool) $premio->activo]) }}" onclick="editarPremio(this)">Editar</button>
                      @endcan
                      @if ($premio->activo)
                        @can('permiso', ['gamificacion', 'eliminar'])
                          <form method="POST" action="{{ route('gamificacion.premios.destroy', $premio->id_premio) }}" style="display:inline;" onsubmit="return confirm('¿Desactivar este premio?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary btn-sm">Desactivar</button>
                          </form>
                        @endcan
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center;color:var(--muted);">No hay premios en el catálogo.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="panel-canjes" data-panel="canjes" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header"><h3>Registrar canje</h3></div>
          <div class="card-body">
            @can('permiso', ['gamificacion', 'crear'])
              <form method="POST" action="{{ route('gamificacion.canjes.store') }}" class="form-grid">
                @csrf
                <div class="form-group">
                  <label for="canje-voluntario">Voluntario</label>
                  <select id="canje-voluntario" name="id_voluntario" required>
                    <option value="">Selecciona un voluntario</option>
                    @foreach ($bolsas as $bolsa)
                      <option value="{{ $bolsa->id_voluntario }}">{{ trim($bolsa->nombre.' '.$bolsa->apellido) }} ({{ $bolsa->puntos_canjeables }} pts)</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label for="canje-premio">Premio</label>
                  <select id="canje-premio" name="id_premio" required>
                    <option value="">Selecciona un premio</option>
                    @foreach ($premios->where('activo', true) as $premio)
                      <option value="{{ $premio->id_premio }}">{{ $premio->nombre }} ({{ $premio->costo_puntos }} pts, stock {{ $premio->stock }})</option>
                    @endforeach
                  </select>
                </div>
                <div style="align-self:end;">
                  <button type="submit" class="btn btn-primary">Registrar canje</button>
                </div>
              </form>
            @else
              <p style="color:var(--muted);">No tienes permiso para registrar canjes.</p>
            @endcan
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Historial de canjes</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Fecha</th><th>Voluntario</th><th>Premio</th><th>Puntos</th><th>Estado</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($canjes as $canje)
                  <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($canje->fecha_solicitud)->format('Y-m-d H:i') }}</td>
                    <td>{{ trim($canje->voluntario_nombre.' '.$canje->voluntario_apellido) }}</td>
                    <td>{{ $canje->premio_nombre }}</td>
                    <td>{{ number_format($canje->puntos_usados) }}</td>
                    <td>{{ ucfirst($canje->estado) }}</td>
                    <td style="white-space:nowrap;">
                      @if ($canje->estado === 'pendiente')
                        @can('permiso', ['gamificacion', 'editar'])
                          <form method="POST" action="{{ route('gamificacion.canjes.entregar', $canje->id_canje) }}" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-sm">Marcar entregado</button>
                          </form>
                          <form method="POST" action="{{ route('gamificacion.canjes.cancelar', $canje->id_canje) }}" style="display:inline;" onsubmit="return confirm('¿Cancelar el canje y devolver los puntos?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-sm">Cancelar</button>
                          </form>
                        @endcan
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center;color:var(--muted);">No hay canjes registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<div id="modal-premio" class="modal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <div onclick="cerrarModalPremio()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>
  <div class="modal-content" style="position:relative;width:min(560px,calc(100% - 28px));max-height:90vh;overflow:auto;">
    <div class="modal-header">
      <h3 id="titulo-modal-premio">Nuevo premio</h3>
      <button type="button" class="modal-close" onclick="cerrarModalPremio()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
      <form id="form-premio" method="POST" action="{{ route('gamificacion.premios.store') }}">
        @csrf
        <input id="metodo-premio" type="hidden" name="_method" value="POST">
        <div class="form-group">
          <label for="premio-nombre">Nombre *</label>
          <input id="premio-nombre" name="nombre" maxlength="100" required>
        </div>
        <div class="form-group">
          <label for="premio-descripcion">Descripción</label>
          <textarea id="premio-descripcion" name="descripcion" maxlength="255" rows="3"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="premio-costo">Costo en puntos *</label>
            <input id="premio-costo" type="number" name="costo_puntos" min="1" max="100000" required>
          </div>
          <div class="form-group">
            <label for="premio-stock">Stock *</label>
            <input id="premio-stock" type="number" name="stock" min="0" max="100000" required>
          </div>
        </div>
        <input type="hidden" name="activo" value="0">
        <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
          <input id="premio-activo" type="checkbox" name="activo" value="1" checked>
          Activo
        </label>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <button type="button" class="btn btn-secondary" onclick="cerrarModalPremio()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar premio</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const pestanasGamificacion = document.querySelectorAll('[data-pestana]');
const panelesGamificacion = document.querySelectorAll('[data-panel]');
pestanasGamificacion.forEach((boton) => {
  boton.addEventListener('click', () => {
    const pestana = boton.dataset.pestana;
    localStorage.setItem('gamificacion-pestana', pestana);
    pestanasGamificacion.forEach((elemento) => elemento.setAttribute('aria-selected', String(elemento === boton)));
    panelesGamificacion.forEach((panel) => { panel.hidden = panel.dataset.panel !== pestana; });
  });
});
const pestanaGuardada = localStorage.getItem('gamificacion-pestana');
const botonPestanaGuardada = document.querySelector(`[data-pestana="${pestanaGuardada}"]`);
if (botonPestanaGuardada) botonPestanaGuardada.click();

document.getElementById('filtro-tipo-reconocimiento').addEventListener('change', function () {
  const tipo = this.value;
  document.querySelectorAll('#tabla-reconocimientos tbody tr[data-tipo]').forEach((fila) => {
    fila.hidden = tipo !== '' && fila.dataset.tipo !== tipo;
  });
});

const formularioPremio = document.getElementById('form-premio');
const rutaActualizarPremio = @json(route('gamificacion.premios.update', ['premio' => '__ID__']));

function abrirModalPremio() {
  formularioPremio.reset();
  formularioPremio.action = @json(route('gamificacion.premios.store'));
  document.getElementById('metodo-premio').value = 'POST';
  document.getElementById('titulo-modal-premio').textContent = 'Nuevo premio';
  document.getElementById('premio-activo').checked = true;
  document.getElementById('modal-premio').style.display = 'flex';
}

function editarPremio(boton) {
  const premio = JSON.parse(boton.dataset.premio);
  formularioPremio.action = rutaActualizarPremio.replace('__ID__', premio.id);
  document.getElementById('metodo-premio').value = 'PUT';
  document.getElementById('titulo-modal-premio').textContent = 'Editar premio';
  document.getElementById('premio-nombre').value = premio.nombre;
  document.getElementById('premio-descripcion').value = premio.descripcion || '';
  document.getElementById('premio-costo').value = premio.costo_puntos;
  document.getElementById('premio-stock').value = premio.stock;
  document.getElementById('premio-activo').checked = premio.activo;
  document.getElementById('modal-premio').style.display = 'flex';
}

function cerrarModalPremio() {
  document.getElementById('modal-premio').style.display = 'none';
}
</script>
@endsection
