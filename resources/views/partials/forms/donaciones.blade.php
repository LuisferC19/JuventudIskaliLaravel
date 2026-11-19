@php
  $modoEdicion = $modoEdicion ?? false;
  $donacion = $donacion ?? null;
  $especie = $donacion?->especie;
  $tipoBienActual = $especie?->tipoBien;
@endphp
<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content" style="padding:24px;">
      <div class="card" style="max-width:900px;">
        <div class="card-header"><h3>{{ $modoEdicion ? 'Editar donación pendiente' : 'Nueva donación' }}</h3></div>
        <div class="card-body" style="padding:24px;">
          <form method="POST" action="{{ $modoEdicion ? route('donaciones.update', $donacion->id_donacion) : route('donaciones.store') }}" id="form-donacion" data-validate="true">
            @csrf
            @if ($modoEdicion) @method('PUT') @endif
            @error('donacion')<div class="field-error">{{ $message }}</div>@enderror
            <div class="form-grid">
              @if (!$modoEdicion)
                <div class="form-group">
                  <label for="id_donador">Donador *</label>
                  <select id="id_donador" name="id_donador" required>
                    <option value="">Selecciona un donador</option>
                    @foreach ($donadores as $donador)
                      <option value="{{ $donador->id_donador }}" @selected(old('id_donador') == $donador->id_donador)>{{ $donador->nombre }}</option>
                    @endforeach
                  </select>
                  @error('id_donador')<div class="field-error">{{ $message }}</div>@enderror
                </div>
              @else
                <div class="form-group"><label>Donador</label><div>{{ $donacion->donador?->fisico?->nombre ?? $donacion->donador?->moral?->razon_social ?? $donacion->donador?->grupo?->nombre_grupo ?? '—' }}</div></div>
              @endif
              <div class="form-group">
                <label for="id_campana">Campaña {{ $modoEdicion ? '' : 'activa' }} *</label>
                <select id="id_campana" name="id_campana" required>
                  <option value="">Selecciona una campaña</option>
                  @foreach (($modoEdicion ? $todasCampanas : $campanas) as $campana)
                    <option value="{{ $campana->id_campana }}" @selected(old('id_campana', $donacion->id_campana ?? '') == $campana->id_campana)>{{ $campana->nombre }}</option>
                  @endforeach
                </select>
                @error('id_campana')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="fecha_recepcion">Fecha de recepción *</label>
                <input type="date" id="fecha_recepcion" name="fecha_recepcion" required value="{{ old('fecha_recepcion', isset($donacion) ? $donacion->fecha_recepcion?->format('Y-m-d') : now()->toDateString()) }}">
                @error('fecha_recepcion')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="evidencia_url">URL de evidencia</label>
                <input type="url" id="evidencia_url" name="evidencia_url" maxlength="255" placeholder="https://..." value="{{ old('evidencia_url', $donacion->evidencia_url ?? '') }}">
                @error('evidencia_url')<div class="field-error">{{ $message }}</div>@enderror
              </div>
            </div>
            <div id="especie-fields" style="background:rgba(10,175,160,.04);border:1px solid rgba(10,175,160,.12);border-radius:10px;padding:20px;margin:20px 0;">
              <div style="font-size:12px;font-weight:600;color:var(--primary);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">Detalle de donación en especie</div>
              <div class="form-grid">
                <div class="form-group">
                  <label for="producto">Producto donado *</label>
                  <input type="text" name="producto" id="producto" list="lista-productos" maxlength="100" required value="{{ old('producto', $tipoBienActual?->nombre ?? '') }}">
                  <datalist id="lista-productos">
                    @foreach ($tiposBien as $bien)
                      <option value="{{ $bien->nombre }}"></option>
                    @endforeach
                  </datalist>
                  @error('producto')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="unidad_medida">Unidad *</label>
                  <select id="unidad_medida" name="unidad_medida" required>
                    <option value="">Selecciona una unidad</option>
                    @foreach (['pieza', 'par', 'kg', 'litro', 'caja', 'paquete', 'kit'] as $unidad)
                      <option value="{{ $unidad }}" @selected(old('unidad_medida', $tipoBienActual?->unidad_medida ?? $especie?->unidad_medida ?? '') === $unidad)>{{ ucfirst($unidad) }}</option>
                    @endforeach
                  </select>
                  @error('unidad_medida')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="cantidad">Cantidad *</label>
                  <input type="number" id="cantidad" name="cantidad" min="0.01" step="0.01" value="{{ old('cantidad', $especie?->cantidad ?? '') }}" required>
                  @error('cantidad')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group full">
                  <label for="descripcion">Descripción</label>
                  <textarea id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $especie?->descripcion ?? '') }}</textarea>
                  @error('descripcion')<div class="field-error">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
              <a href="{{ route('donaciones.index') }}" class="btn btn-secondary">Cancelar</a>
              <button type="submit" class="btn btn-primary">{{ $modoEdicion ? 'Guardar cambios' : 'Registrar donación' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
