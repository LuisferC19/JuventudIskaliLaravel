@extends('layouts.app')
@section('title', 'Gamificación | Juventud Iskali')
@section('page-title', 'Gamificación')
@section('content')

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      @php
        $verDonadores = false;
        $verVoluntarios = false;
        $verPremios = false;
        $verCanjes = false;
        $verPenalizaciones = false;
        $verCampamentos = false;
      @endphp
      @can('permiso', ['donors.manage'])
        @php($verDonadores = true)
      @endcan
      @can('permiso', ['rewards.manage'])
        @php($verVoluntarios = true)
        @php($verPremios = true)
        @php($verCanjes = true)
      @endcan
      @can('permiso', ['penalties.manage'])
        @php($verVoluntarios = true)
        @php($verPenalizaciones = true)
      @endcan
      @can('permiso', ['camps.manage'])
        @php($verVoluntarios = true)
        @php($verCampamentos = true)
      @endcan
      @can('permiso', ['rewards.deliver'])
        @php($verCanjes = true)
      @endcan

      <div role="tablist" aria-label="Secciones de gamificación" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
        @if ($verDonadores)<button type="button" class="btn btn-secondary" role="tab" data-pestana="donadores" aria-selected="false">Donadores</button>@endif
        @if ($verVoluntarios)<button type="button" class="btn btn-secondary" role="tab" data-pestana="voluntarios" aria-selected="false">Voluntarios</button>@endif
        @if ($verPremios)<button type="button" class="btn btn-secondary" role="tab" data-pestana="premios" aria-selected="false">Premios</button>@endif
        @if ($verCanjes)<button type="button" class="btn btn-secondary" role="tab" data-pestana="canjes" aria-selected="false">Canjes</button>@endif
        @if ($verPenalizaciones)<button type="button" class="btn btn-secondary" role="tab" data-pestana="penalizaciones" aria-selected="false">Penalizaciones</button>@endif
        @if ($verCampamentos)<button type="button" class="btn btn-secondary" role="tab" data-pestana="campamentos" aria-selected="false">Campamentos</button>@endif
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

      @if ($verDonadores)
      <section id="panel-donadores" data-panel="donadores" role="tabpanel" hidden>
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
          @can('permiso', ['reconocimientos', 'crear'])
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('modal-diploma').style.display='flex'">Emitir diploma</button>
          @endcan
          <select id="filtro-tipo-reconocimiento" aria-label="Filtrar reconocimientos" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;">
            <option value="">Todos los tipos</option>
            @foreach (['diploma', 'carta', 'certificado', 'voluntario_mes', 'mayor_asistencia', 'mayor_entregas', 'donador_destacado', 'otro'] as $tipo)
              <option value="{{ $tipo }}">{{ str_replace('_', ' ', ucfirst($tipo)) }}</option>
            @endforeach
          </select>
        </div>
        <div style="overflow-x:auto;">
          <table id="tabla-reconocimientos">
            <thead><tr><th>ID</th><th>Folio</th><th>Tipo</th><th>Receptor</th><th>Descripción</th><th>Campaña</th><th>Emitido por</th><th>Fecha</th><th>PDF</th></tr></thead>
            <tbody>
              @forelse ($reconocimientos as $reconocimiento)
                <tr data-tipo="{{ $reconocimiento->tipo }}">
                  <td>#RC{{ $reconocimiento->id_reconocimiento }}</td>
                  <td><strong>{{ $reconocimiento->folio ?: '—' }}</strong></td>
                  <td><span class="badge badge-blue">{{ str_replace('_', ' ', ucfirst($reconocimiento->tipo)) }}</span></td>
                  <td>{{ $reconocimiento->receptor }}</td>
                  <td>{{ $reconocimiento->descripcion ?: '—' }}</td>
                  <td>{{ $reconocimiento->campana ?: '—' }}</td>
                  <td>{{ $reconocimiento->emisor ?: '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($reconocimiento->fecha_emision)->format('Y-m-d') }}</td>
                  <td>
                    @can('permiso', ['reconocimientos', 'ver'])
                      <a class="btn btn-secondary btn-sm" href="{{ route('reconocimientos.pdf', $reconocimiento->id_reconocimiento) }}" target="_blank">PDF</a>
                    @else
                      —
                    @endcan
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:20px;">No hay reconocimientos emitidos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      </section>
      @endif

      @if ($verVoluntarios)
      <section id="panel-voluntarios" data-panel="voluntarios" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header"><h3>Bolsa de puntos de voluntarios</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Voluntario</th><th>Zona</th><th>Bolsa 1</th><th>Bolsa 2</th><th>Faltas</th><th>Estado</th></tr></thead>
              <tbody>
                @forelse ($bolsas as $bolsa)
                  <tr>
                    <td>{{ trim($bolsa->nombre.' '.$bolsa->apellido) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($bolsa->zona_asignada)) }}</td>
                    <td>{{ number_format($bolsa->puntos_canjeables) }}</td>
                    <td>{{ number_format($bolsa->puntos_campamento) }}</td>
                    <td>{{ $bolsa->faltas_injustificadas }}</td>
                    <td>
                      @if ($bolsa->campamento_bloqueado)
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">Bloqueado</span>
                      @elseif ($bolsa->campamentos_calificados > 0)
                        <span class="badge badge-green">Califica a {{ $bolsa->campamentos_calificados }} campamento(s)</span>
                      @else
                        <span class="badge badge-gray">Sin campamento disponible</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center;color:var(--muted);">No hay voluntarios activos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      @endif

      @if ($verPremios)
      <section id="panel-premios" data-panel="premios" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header">
            <h3>Catálogo de premios</h3>
            @can('permiso', ['rewards.manage'])
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
                      @can('permiso', ['rewards.manage'])
                        <button type="button" class="btn btn-secondary btn-sm" data-premio="{{ json_encode(['id' => $premio->id_premio, 'nombre' => $premio->nombre, 'descripcion' => $premio->descripcion, 'costo_puntos' => $premio->costo_puntos, 'stock' => $premio->stock, 'activo' => (bool) $premio->activo]) }}" onclick="editarPremio(this)">Editar</button>
                      @endcan
                      @if ($premio->activo)
                        @can('permiso', ['rewards.manage'])
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
      @endif

      @if ($verCanjes)
      <section id="panel-canjes" data-panel="canjes" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header"><h3>Registrar canje</h3></div>
          <div class="card-body">
            @can('permiso', ['rewards.manage'])
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
                        @can('permiso', ['rewards.deliver'])
                          <form method="POST" action="{{ route('gamificacion.canjes.entregar', $canje->id_canje) }}" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-sm">Marcar entregado</button>
                          </form>
                        @endcan
                        @can('permiso', ['rewards.manage'])
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
      @endif

      @if ($verPenalizaciones)
      <section id="panel-penalizaciones" data-panel="penalizaciones" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header"><h3>Registrar penalización</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('gamificacion.penalizaciones.store') }}" class="form-grid">
              @csrf
              <div class="form-group">
                <label for="penalizacion-voluntario">Voluntario</label>
                <select id="penalizacion-voluntario" name="id_voluntario" required>
                  <option value="">Selecciona un voluntario</option>
                  @foreach ($bolsas as $bolsa)
                    <option value="{{ $bolsa->id_voluntario }}">{{ trim($bolsa->nombre.' '.$bolsa->apellido) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="penalizacion-actividad">Actividad</label>
                <select id="penalizacion-actividad" name="id_actividad" required>
                  <option value="">Selecciona una actividad</option>
                  @foreach ($actividades as $actividad)
                    <option value="{{ $actividad->id_actividad }}">{{ $actividad->titulo }} ({{ \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('Y-m-d') }})</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="penalizacion-tipo">Tipo</label>
                <select id="penalizacion-tipo" name="tipo" required>
                  <option value="retardo">Retardo</option>
                  <option value="inasistencia_injustificada">Inasistencia injustificada</option>
                </select>
              </div>
              <div class="form-group">
                <label for="penalizacion-motivo">Motivo</label>
                <input id="penalizacion-motivo" name="motivo" maxlength="255">
              </div>
              <div style="align-self:end;">
                <button type="submit" class="btn btn-primary">Registrar penalización</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3>Ausencias sin penalizar</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Voluntario</th><th>Actividad</th><th>Fecha</th><th>Acción</th></tr></thead>
              <tbody>
                @forelse ($ausenciasPendientes as $ausencia)
                  <tr>
                    <td>{{ trim($ausencia->voluntario_nombre.' '.$ausencia->voluntario_apellido) }}</td>
                    <td>{{ $ausencia->actividad_titulo }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($ausencia->fecha_inicio)->format('Y-m-d') }}</td>
                    <td>
                      <form method="POST" action="{{ route('gamificacion.penalizaciones.store') }}">
                        @csrf
                        <input type="hidden" name="id_voluntario" value="{{ $ausencia->id_voluntario }}">
                        <input type="hidden" name="id_actividad" value="{{ $ausencia->id_actividad }}">
                        <input type="hidden" name="tipo" value="inasistencia_injustificada">
                        <button type="submit" class="btn btn-secondary btn-sm">Penalizar inasistencia</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" style="text-align:center;color:var(--muted);">No hay ausencias pendientes de penalizar.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3>Historial de penalizaciones</h3></div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Fecha</th><th>Voluntario</th><th>Actividad</th><th>Tipo</th><th>Puntos descontados</th><th>Motivo</th><th>Registró</th><th>Estado</th><th>Acción</th></tr></thead>
              <tbody>
                @forelse ($penalizaciones as $penalizacion)
                  <tr @if ($penalizacion->anulada) style="color:var(--muted);background:#f3f4f6;" @endif>
                    <td>{{ \Illuminate\Support\Carbon::parse($penalizacion->fecha)->format('Y-m-d H:i') }}</td>
                    <td>{{ trim($penalizacion->voluntario_nombre.' '.$penalizacion->voluntario_apellido) }}</td>
                    <td>{{ $penalizacion->actividad_titulo }}</td>
                    <td>{{ $penalizacion->tipo === 'retardo' ? 'Retardo' : 'Inasistencia injustificada' }}</td>
                    <td>{{ number_format($penalizacion->puntos_descontados) }}</td>
                    <td>{{ $penalizacion->motivo ?: '—' }}</td>
                    <td>{{ trim(($penalizacion->registra_nombre ?? '').' '.($penalizacion->registra_apellido ?? '')) ?: '—' }}</td>
                    <td>{{ $penalizacion->anulada ? 'Anulada' : 'Activa' }}</td>
                    <td>
                      @unless ($penalizacion->anulada)
                        <form method="POST" action="{{ route('gamificacion.penalizaciones.anular', $penalizacion->id_penalizacion) }}" onsubmit="return confirm('¿Anular esta penalización y devolver los puntos?')">
                          @csrf
                          @method('PATCH')
                          <button type="submit" class="btn btn-secondary btn-sm">Anular</button>
                        </form>
                      @endunless
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" style="text-align:center;color:var(--muted);">No hay penalizaciones registradas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      @endif

        @can('permiso', ['reconocimientos', 'crear'])
        <div id="modal-diploma" class="modal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
          <div onclick="document.getElementById('modal-diploma').style.display='none'" style="position:absolute;inset:0;background:rgba(10,30,28,.45);"></div>
          <div class="modal-content" style="position:relative;width:min(620px,calc(100% - 28px));max-height:90vh;overflow:auto;">
            <div class="modal-header"><h3>Emitir diploma</h3><button type="button" class="modal-close" onclick="document.getElementById('modal-diploma').style.display='none'">×</button></div>
            <div class="modal-body">
              <form method="POST" action="{{ route('reconocimientos.store') }}">
                @csrf
                <div class="form-group">
                  <label for="diploma-donador">Donador *</label>
                  <select id="diploma-donador" name="id_donador" class="form-control" required>
                    @foreach ($donadores as $donador)<option value="{{ $donador->id_donador }}">{{ $donador->nombre ?: 'Donador anónimo' }}</option>@endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label for="diploma-campana">Campaña</label>
                  <select id="diploma-campana" name="id_campana" class="form-control">
                    <option value="">Sin campaña</option>
                    @foreach ($campanasDiploma as $campana)<option value="{{ $campana->id_campana }}">{{ $campana->nombre }}</option>@endforeach
                  </select>
                </div>
                <div class="form-group"><label for="diploma-descripcion">Descripción *</label><textarea id="diploma-descripcion" name="descripcion" maxlength="255" required rows="3"></textarea></div>
                <div class="form-group"><label for="diploma-otorgado">Otorgado por</label><input id="diploma-otorgado" name="otorgado_por" maxlength="150" value="{{ config('iskali.institucion.firma_nombre') }}"></div>
                <div style="display:flex;justify-content:flex-end;gap:10px;"><button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-diploma').style.display='none'">Cancelar</button><button type="submit" class="btn btn-primary">Emitir diploma</button></div>
              </form>
            </div>
          </div>
        </div>
        @endcan

      @if ($verCampamentos)
      <section id="panel-campamentos" data-panel="campamentos" role="tabpanel" hidden>
        <div class="card">
          <div class="card-header">
            <h3>Campamentos</h3>
            <button type="button" class="btn btn-primary" onclick="abrirModalCampamento()">Nuevo campamento</button>
          </div>
          <div style="overflow-x:auto;">
            <table>
              <thead><tr><th>Nombre</th><th>Fechas</th><th>Zona</th><th>Cupo</th><th>Umbral</th><th>Estado</th><th>Califican</th><th>Acción</th></tr></thead>
              <tbody>
                @forelse ($campamentos as $campamento)
                  <tr>
                    <td>{{ $campamento->nombre }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($campamento->fecha_inicio)->format('Y-m-d') }}@if ($campamento->fecha_fin) – {{ \Illuminate\Support\Carbon::parse($campamento->fecha_fin)->format('Y-m-d') }}@endif</td>
                    <td>{{ str_replace('_', ' ', ucfirst($campamento->zona)) }}</td>
                    <td>{{ $campamento->cupo ?? 'Sin límite' }}</td>
                    <td>{{ $campamento->umbral_puntos }} pts</td>
                    <td>{{ ucfirst($campamento->estado) }}</td>
                    <td>{{ $campamento->total_calificados }}</td>
                    <td style="white-space:nowrap;">
                      <button type="button" class="btn btn-secondary btn-sm" data-campamento="{{ json_encode(['id' => $campamento->id_campamento, 'nombre' => $campamento->nombre, 'descripcion' => $campamento->descripcion, 'fecha_inicio' => $campamento->fecha_inicio, 'fecha_fin' => $campamento->fecha_fin, 'zona' => $campamento->zona, 'cupo' => $campamento->cupo, 'umbral_puntos' => $campamento->umbral_puntos, 'estado' => $campamento->estado]) }}" data-voluntarios="{{ json_encode($campamento->voluntarios_que_califican) }}" onclick="verCampamento(this)">Ver</button>
                      <button type="button" class="btn btn-secondary btn-sm" data-campamento="{{ json_encode(['id' => $campamento->id_campamento, 'nombre' => $campamento->nombre, 'descripcion' => $campamento->descripcion, 'fecha_inicio' => $campamento->fecha_inicio, 'fecha_fin' => $campamento->fecha_fin, 'zona' => $campamento->zona, 'cupo' => $campamento->cupo, 'umbral_puntos' => $campamento->umbral_puntos, 'estado' => $campamento->estado]) }}" onclick="editarCampamento(this)">Editar</button>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" style="text-align:center;color:var(--muted);">No hay campamentos registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      @endif
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

@if ($verCampamentos)
<div id="modal-campamento" class="modal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <div onclick="cerrarModalCampamento()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>
  <div class="modal-content" style="position:relative;width:min(620px,calc(100% - 28px));max-height:90vh;overflow:auto;">
    <div class="modal-header">
      <h3 id="titulo-modal-campamento">Nuevo campamento</h3>
      <button type="button" class="modal-close" onclick="cerrarModalCampamento()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
      <form id="form-campamento" method="POST" action="{{ route('gamificacion.campamentos.store') }}">
        @csrf
        <input id="metodo-campamento" type="hidden" name="_method" value="POST">
        <div class="form-group">
          <label for="campamento-nombre">Nombre *</label>
          <input id="campamento-nombre" name="nombre" maxlength="150" required>
        </div>
        <div class="form-group">
          <label for="campamento-descripcion">Descripción</label>
          <textarea id="campamento-descripcion" name="descripcion" rows="3"></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label for="campamento-fecha-inicio">Fecha de inicio *</label>
            <input id="campamento-fecha-inicio" type="date" name="fecha_inicio" required>
          </div>
          <div class="form-group">
            <label for="campamento-fecha-fin">Fecha de fin</label>
            <input id="campamento-fecha-fin" type="date" name="fecha_fin">
          </div>
          <div class="form-group">
            <label for="campamento-zona">Zona *</label>
            <select id="campamento-zona" name="zona" required>
              <option value="san_martin">San Martín</option>
              <option value="tlaxcala">Tlaxcala</option>
              <option value="ambas">Ambas</option>
            </select>
          </div>
          <div class="form-group">
            <label for="campamento-cupo">Cupo</label>
            <input id="campamento-cupo" type="number" name="cupo" min="1">
          </div>
          <div class="form-group">
            <label for="campamento-umbral">Umbral de puntos (50 a 200) *</label>
            <input id="campamento-umbral" type="number" name="umbral_puntos" min="50" max="200" value="{{ $umbralDefecto }}" required>
          </div>
          <div class="form-group">
            <label for="campamento-estado">Estado</label>
            <select id="campamento-estado" name="estado">
              <option value="abierto">Abierto</option>
              <option value="cerrado">Cerrado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
          <button type="button" class="btn btn-secondary" onclick="cerrarModalCampamento()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar campamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="modal-voluntarios-campamento" class="modal" style="display:none;position:fixed;inset:0;z-index:1001;align-items:center;justify-content:center;">
  <div onclick="cerrarModalVoluntariosCampamento()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>
  <div class="modal-content" style="position:relative;width:min(620px,calc(100% - 28px));max-height:90vh;overflow:auto;">
    <div class="modal-header">
      <h3 id="titulo-voluntarios-campamento">Voluntarios que califican</h3>
      <button type="button" class="modal-close" onclick="cerrarModalVoluntariosCampamento()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Voluntario</th><th>Zona</th><th>Bolsa 1</th><th>Bolsa 2</th></tr></thead>
          <tbody id="lista-voluntarios-campamento"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endif

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
const botonPestanaGuardada = Array.from(pestanasGamificacion).find((boton) => boton.dataset.pestana === pestanaGuardada);
(botonPestanaGuardada || pestanasGamificacion[0])?.click();

document.getElementById('filtro-tipo-reconocimiento')?.addEventListener('change', function () {
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

const formularioCampamento = document.getElementById('form-campamento');
const rutaActualizarCampamento = @json(route('gamificacion.campamentos.update', ['campamento' => '__ID__']));

function abrirModalCampamento() {
  formularioCampamento.reset();
  formularioCampamento.action = @json(route('gamificacion.campamentos.store'));
  document.getElementById('metodo-campamento').value = 'POST';
  document.getElementById('titulo-modal-campamento').textContent = 'Nuevo campamento';
  document.getElementById('campamento-umbral').value = @json($umbralDefecto);
  document.getElementById('campamento-estado').value = 'abierto';
  document.getElementById('modal-campamento').style.display = 'flex';
}

function editarCampamento(boton) {
  const campamento = JSON.parse(boton.dataset.campamento);
  formularioCampamento.action = rutaActualizarCampamento.replace('__ID__', campamento.id);
  document.getElementById('metodo-campamento').value = 'PUT';
  document.getElementById('titulo-modal-campamento').textContent = 'Editar campamento';
  document.getElementById('campamento-nombre').value = campamento.nombre;
  document.getElementById('campamento-descripcion').value = campamento.descripcion || '';
  document.getElementById('campamento-fecha-inicio').value = campamento.fecha_inicio;
  document.getElementById('campamento-fecha-fin').value = campamento.fecha_fin || '';
  document.getElementById('campamento-zona').value = campamento.zona;
  document.getElementById('campamento-cupo').value = campamento.cupo || '';
  document.getElementById('campamento-umbral').value = campamento.umbral_puntos;
  document.getElementById('campamento-estado').value = campamento.estado;
  document.getElementById('modal-campamento').style.display = 'flex';
}

function cerrarModalCampamento() {
  document.getElementById('modal-campamento').style.display = 'none';
}

function verCampamento(boton) {
  const campamento = JSON.parse(boton.dataset.campamento);
  const voluntarios = JSON.parse(boton.dataset.voluntarios);
  const tabla = document.getElementById('lista-voluntarios-campamento');
  document.getElementById('titulo-voluntarios-campamento').textContent = `Voluntarios que califican: ${campamento.nombre}`;
  tabla.replaceChildren();
  if (voluntarios.length === 0) {
    const fila = tabla.insertRow();
    const celda = fila.insertCell();
    celda.colSpan = 4;
    celda.textContent = 'Ningún voluntario califica para este campamento.';
  } else {
    voluntarios.forEach((voluntario) => {
      const fila = tabla.insertRow();
      fila.insertCell().textContent = `${voluntario.nombre} ${voluntario.apellido}`;
      fila.insertCell().textContent = voluntario.zona_asignada.replaceAll('_', ' ');
      fila.insertCell().textContent = Number(voluntario.puntos_canjeables).toLocaleString();
      fila.insertCell().textContent = Number(voluntario.puntos_campamento).toLocaleString();
    });
  }
  document.getElementById('modal-voluntarios-campamento').style.display = 'flex';
}

function cerrarModalVoluntariosCampamento() {
  document.getElementById('modal-voluntarios-campamento').style.display = 'none';
}
</script>
@endsection
