@php
  $modoEdicion = $modoEdicion ?? false;
  $fechaActual = isset($entrega) ? \Illuminate\Support\Carbon::parse($entrega->fecha_entrega)->format('Y-m-d\TH:i') : '';
@endphp
<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content" style="padding:24px;">
    <div class="card" style="max-width:800px;">
      <div class="card-header"><h3>{{ $modoEdicion ? 'Editar entrega' : 'Nueva entrega' }}</h3></div>
      <div class="card-body" style="padding:24px;">
        <form method="POST" action="{{ $modoEdicion ? route('entregas.update', $entrega->id_entrega) : route('entregas.store') }}" data-validate="true">
          @csrf
          @if ($modoEdicion) @method('PUT') @endif
          @error('entrega')<div class="field-error">{{ $message }}</div>@enderror
          <div class="form-grid">
            @if (!$modoEdicion)
              <div class="form-group full"><label for="id_donacion">Donación verificada *</label><select id="id_donacion" name="id_donacion" required><option value="">Selecciona una donación verificada</option>@foreach ($donaciones as $donacion)<option value="{{ $donacion->id_donacion }}" @selected(old('id_donacion') == $donacion->id_donacion)>#{{ $donacion->id_donacion }} · {{ $donacion->tipo_donacion }} · {{ $donacion->donador }} · {{ $donacion->campana }} · {{ $donacion->cantidad }} {{ $donacion->unidad_medida }}</option>@endforeach</select>@error('id_donacion')<div class="field-error">{{ $message }}</div>@enderror</div>
            @else
              <div class="form-group full"><label>Donación (no editable)</label><div>#{{ $entrega->id_donacion }}</div></div>
            @endif
            <div class="form-group full"><label for="id_beneficiario">Beneficiario *</label><select id="id_beneficiario" name="id_beneficiario" required><option value="">Selecciona un beneficiario</option>@foreach ($beneficiarios as $beneficiario)<option value="{{ $beneficiario->id_beneficiario }}" @selected(old('id_beneficiario', $entrega->id_beneficiario ?? '') == $beneficiario->id_beneficiario)>{{ $beneficiario->nombre }}</option>@endforeach</select>@error('id_beneficiario')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="cantidad_entregada">Cantidad entregada *</label><input type="number" id="cantidad_entregada" name="cantidad_entregada" min="0.01" step="0.01" required value="{{ old('cantidad_entregada', $entrega->cantidad_entregada ?? '') }}">@error('cantidad_entregada')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="fecha_entrega">Fecha de entrega *</label><input type="datetime-local" id="fecha_entrega" name="fecha_entrega" required value="{{ old('fecha_entrega', $fechaActual) }}">@error('fecha_entrega')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-group full"><label for="evidencia_url">URL de evidencia</label><input type="url" id="evidencia_url" name="evidencia_url" maxlength="255" value="{{ old('evidencia_url', $entrega->evidencia_url ?? '') }}">@error('evidencia_url')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-group full"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="4">{{ old('observaciones', $entrega->observaciones ?? '') }}</textarea>@error('observaciones')<div class="field-error">{{ $message }}</div>@enderror</div>
          </div>
          <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;"><a href="{{ route('entregas.index') }}" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">{{ $modoEdicion ? 'Guardar cambios' : 'Registrar entrega' }}</button></div>
        </form>
      </div>
    </div>
  </div>
</div></div>
