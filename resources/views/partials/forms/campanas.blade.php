<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content" style="padding:24px;">
      <div class="card" style="max-width:760px;">
        <div class="card-header"><h3>{{ $modoEdicion ? 'Editar campaña' : 'Nueva campaña' }}</h3></div>
        <div class="card-body" style="padding:24px;">
          <form method="POST" action="{{ $modoEdicion ? route('campanas.update', $campana->id_campana) : route('campanas.store') }}" data-validate="true">
            @csrf
            @if ($modoEdicion ?? false) @method('PUT') @endif
            <div class="form-grid">
              <div class="form-group full">
                <label for="nombre">Nombre *</label>
                <input id="nombre" name="nombre" maxlength="150" required value="{{ old('nombre', $campana->nombre ?? '') }}">
                @error('nombre')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group full">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $campana->descripcion ?? '') }}</textarea>
                @error('descripcion')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="tipo_meta">Tipo de meta *</label>
                <select id="tipo_meta" name="tipo_meta" required>
                  <option value="">Selecciona</option>
                  @foreach (['economica' => 'Económica', 'especie' => 'En especie', 'mixta' => 'Mixta', 'servicio' => 'Servicio'] as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('tipo_meta', $campana->tipo_meta ?? '') === $valor)>{{ $etiqueta }}</option>
                  @endforeach
                </select>
                @error('tipo_meta')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="meta_economica">Meta económica (MXN)</label>
                <input type="number" id="meta_economica" name="meta_economica" min="0" step="0.01" value="{{ old('meta_economica', $campana->meta_economica ?? '0.00') }}">
                @error('meta_economica')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="fecha_inicio">Fecha de inicio *</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" required value="{{ old('fecha_inicio', optional($campana->fecha_inicio ?? null)->format('Y-m-d')) }}">
                @error('fecha_inicio')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="fecha_cierre">Fecha de cierre *</label>
                <input type="date" id="fecha_cierre" name="fecha_cierre" required value="{{ old('fecha_cierre', optional($campana->fecha_cierre ?? null)->format('Y-m-d')) }}">
                @error('fecha_cierre')<div class="field-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="estado">Estado</label>
                <input id="estado" value="{{ ucfirst($campana->estado ?? 'borrador') }}" disabled>
              </div>
              <div class="form-group full">
                <label for="imagen_url">URL de imagen</label>
                <input type="url" id="imagen_url" name="imagen_url" maxlength="255" value="{{ old('imagen_url', $campana->imagen_url ?? '') }}" placeholder="https://...">
                @error('imagen_url')<div class="field-error">{{ $message }}</div>@enderror
              </div>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
              <a href="{{ route('campanas.index') }}" class="btn btn-secondary">Cancelar</a>
              <button type="submit" class="btn btn-primary">{{ $modoEdicion ? 'Actualizar campaña' : 'Guardar campaña' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
