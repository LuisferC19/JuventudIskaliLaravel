@php
  $modoEdicion = $modoEdicion ?? false;
  $tipoFormulario = old('tipo', $tipoDonacion ?? (isset($donacion) && $donacion->especie ? 'especie' : 'economica'));
  $especie = $donacion->especie ?? null;
  $economica = $donacion->economica ?? null;
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
              <div class="form-group full">
                <label for="tipo">Tipo de donación *</label>
                @if ($modoEdicion)
                  <div>{{ $tipoFormulario === 'especie' ? 'En especie' : 'Económica' }}</div>
                  <input type="hidden" name="tipo" value="{{ $tipoFormulario }}">
                @else
                  <select id="tipo" name="tipo" required>
                    <option value="especie" @selected($tipoFormulario === 'especie')>En especie</option>
                    <option value="economica" @selected($tipoFormulario === 'economica')>Económica</option>
                  </select>
                @endif
                @error('tipo')<div class="field-error">{{ $message }}</div>@enderror
              </div>
            </div>
            <div id="especie-fields" style="display:{{ $tipoFormulario === 'especie' ? 'block' : 'none' }};background:rgba(10,175,160,.04);border:1px solid rgba(10,175,160,.12);border-radius:10px;padding:20px;margin:20px 0;">
              <div style="font-size:12px;font-weight:600;color:var(--primary);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">Detalle de donación en especie</div>
              <div class="form-grid">
                <div class="form-group">
                  <label for="id_tipo_bien">Tipo de bien *</label>
                  <select id="id_tipo_bien" name="id_tipo_bien" {{ $tipoFormulario === 'especie' ? 'required' : '' }}>
                    <option value="">Selecciona un bien</option>
                    @foreach ($tiposBien as $bien)
                      <option value="{{ $bien->id_tipo_bien }}" @selected(old('id_tipo_bien', $especie->id_tipo_bien ?? '') == $bien->id_tipo_bien)>{{ $bien->nombre }} ({{ $bien->unidad_medida }})</option>
                    @endforeach
                  </select>
                  @error('id_tipo_bien')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="cantidad">Cantidad *</label>
                  <input type="number" id="cantidad" name="cantidad" min="0.01" step="0.01" value="{{ old('cantidad', $especie->cantidad ?? '') }}" {{ $tipoFormulario === 'especie' ? 'required' : '' }}>
                  @error('cantidad')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group full">
                  <label for="descripcion">Descripción</label>
                  <textarea id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $especie->descripcion ?? '') }}</textarea>
                  @error('descripcion')<div class="field-error">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
            <div id="economica-fields" style="display:{{ $tipoFormulario === 'economica' ? 'block' : 'none' }};background:rgba(232,160,32,.04);border:1px solid rgba(232,160,32,.15);border-radius:10px;padding:20px;margin:20px 0;">
              <div style="font-size:12px;font-weight:600;color:var(--amber);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">Detalle de donación económica</div>
              <div class="form-grid">
                <div class="form-group"><label for="monto">Monto *</label><input type="number" id="monto" name="monto" min="0.01" step="0.01" value="{{ old('monto', $economica->monto ?? '') }}" {{ $tipoFormulario === 'economica' ? 'required' : '' }}>@error('monto')<div class="field-error">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="moneda">Moneda</label><input id="moneda" name="moneda" maxlength="3" value="{{ old('moneda', $economica->moneda ?? 'MXN') }}">@error('moneda')<div class="field-error">{{ $message }}</div>@enderror</div>
                <div class="form-group">
                  <label for="metodo_pago">Método de pago *</label>
                  <select id="metodo_pago" name="metodo_pago" {{ $tipoFormulario === 'economica' ? 'required' : '' }}>
                    <option value="">Selecciona</option>
                    @foreach (['efectivo', 'transferencia', 'cheque', 'otro'] as $metodo)
                      <option value="{{ $metodo }}" @selected(old('metodo_pago', $economica->metodo_pago ?? '') === $metodo)>{{ ucfirst($metodo) }}</option>
                    @endforeach
                  </select>
                  @error('metodo_pago')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group"><label for="referencia_pago">Referencia de pago</label><input id="referencia_pago" name="referencia_pago" maxlength="100" value="{{ old('referencia_pago', $economica->referencia_pago ?? '') }}">@error('referencia_pago')<div class="field-error">{{ $message }}</div>@enderror</div>
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
@unless ($modoEdicion)
<script>
  document.getElementById('tipo')?.addEventListener('change', function () {
    const especie = this.value === 'especie';
    document.getElementById('especie-fields').style.display = especie ? 'block' : 'none';
    document.getElementById('economica-fields').style.display = especie ? 'none' : 'block';
    document.querySelectorAll('#especie-fields [required], #economica-fields [required]').forEach((field) => field.required = false);
    document.querySelectorAll(especie ? '#especie-fields [id="id_tipo_bien"], #especie-fields [id="cantidad"]' : '#economica-fields [id="monto"], #economica-fields [id="metodo_pago"]').forEach((field) => field.required = true);
  });
</script>
@endunless
