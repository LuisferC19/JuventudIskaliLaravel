<div style="display:flex;width:100%;"><div class="main">
  @include('partials.topbar')
  <div class="content" style="padding:24px;">
    <div class="card" style="max-width:760px;">
      <div class="card-header"><h3>{{ ($modoEdicion ?? false) ? 'Editar voluntario' : 'Alta de voluntario' }}</h3></div>
      <div class="card-body" style="padding:24px;">
        <form method="POST" action="{{ ($modoEdicion ?? false) ? route('voluntarios.update', $voluntario->id_voluntario) : route('voluntarios.store') }}" data-validate="true">
          @csrf
          @if ($modoEdicion ?? false) @method('PUT') @endif
          <div class="form-group">
            <label for="id_usuario">Usuario Voluntario *</label>
            <select id="id_usuario" name="id_usuario" required>
              <option value="">Seleccionar usuario...</option>
              @foreach ($usuariosDisponibles as $usuario)
                <option value="{{ $usuario->id_usuario }}" @selected(old('id_usuario', $voluntario->id_usuario ?? '') == $usuario->id_usuario)>{{ $usuario->nombre }} {{ $usuario->apellido }} — {{ $usuario->email }}</option>
              @endforeach
            </select>
            @error('id_usuario')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label for="telefono_contacto">Teléfono de contacto</label>
              <input type="tel" id="telefono_contacto" name="telefono_contacto" maxlength="20" value="{{ old('telefono_contacto', $voluntario->telefono_contacto ?? '') }}">
              @error('telefono_contacto')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label for="zona_asignada">Zona asignada *</label>
              <select id="zona_asignada" name="zona_asignada" required>
                @foreach (['san_martin' => 'San Martín', 'tlaxcala' => 'Tlaxcala', 'ambas' => 'Ambas', 'otra' => 'Otra'] as $valor => $etiqueta)
                  <option value="{{ $valor }}" @selected(old('zona_asignada', $voluntario->zona_asignada ?? 'ambas') === $valor)>{{ $etiqueta }}</option>
                @endforeach
              </select>
              @error('zona_asignada')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label for="disponibilidad">Disponibilidad *</label>
              <select id="disponibilidad" name="disponibilidad" required>
                @foreach (['tiempo_completo' => 'Tiempo completo', 'medio_tiempo' => 'Medio tiempo', 'fines_semana' => 'Fines de semana', 'bajo_demanda' => 'Bajo demanda'] as $valor => $etiqueta)
                  <option value="{{ $valor }}" @selected(old('disponibilidad', $voluntario->disponibilidad ?? '') === $valor)>{{ $etiqueta }}</option>
                @endforeach
              </select>
              @error('disponibilidad')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label for="fecha_ingreso">Fecha de ingreso *</label>
              <input type="date" id="fecha_ingreso" name="fecha_ingreso" required value="{{ old('fecha_ingreso', $voluntario->fecha_ingreso ?? now()->toDateString()) }}">
              @error('fecha_ingreso')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group full">
              <label for="notas">Notas</label>
              <textarea id="notas" name="notas" rows="4" maxlength="65535">{{ old('notas', $voluntario->notas ?? '') }}</textarea>
              @error('notas')<div class="field-error">{{ $message }}</div>@enderror
            </div>
          </div>
          <div style="display:flex;gap:10px;margin-top:20px;">
            <button class="btn btn-primary" type="submit">{{ ($modoEdicion ?? false) ? 'Guardar cambios' : 'Registrar voluntario' }}</button>
            <a class="btn btn-secondary" href="{{ route('voluntarios.index') }}">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div></div>
