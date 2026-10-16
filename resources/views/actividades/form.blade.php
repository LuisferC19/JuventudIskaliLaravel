@extends('layouts.app')
@section('title', ($actividad->exists ? 'Editar actividad' : 'Nueva actividad').' | Juventud Iskali')
@section('page-title', $actividad->exists ? 'Editar actividad' : 'Nueva actividad')
@section('content')
<div class="main">
  @include('partials.topbar')
  <div class="content">
    <div class="card" style="max-width:760px;margin:0 auto;">
      <div class="card-header"><h3>{{ $actividad->exists ? 'Editar actividad' : 'Registrar actividad' }}</h3></div>
      <form method="POST" action="{{ $actividad->exists ? route('actividades.update', $actividad->id_actividad) : route('actividades.store') }}" style="padding:20px;">
        @csrf
        @if ($actividad->exists)
          @method('PUT')
        @endif
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">Revisa los campos marcados y vuelve a intentarlo.</div>
        @endif
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div style="grid-column:1/-1;">
            <label for="titulo">Título *</label>
            <input id="titulo" name="titulo" type="text" maxlength="150" required value="{{ old('titulo', $actividad->titulo) }}">
            @error('titulo')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div style="grid-column:1/-1;">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $actividad->descripcion) }}</textarea>
            @error('descripcion')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="fecha_inicio">Fecha de inicio *</label>
            <input id="fecha_inicio" name="fecha_inicio" type="datetime-local" required
                   value="{{ old('fecha_inicio', $actividad->fecha_inicio?->format('Y-m-d\TH:i')) }}">
            @error('fecha_inicio')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="fecha_fin">Fecha de fin</label>
            <input id="fecha_fin" name="fecha_fin" type="datetime-local"
                   value="{{ old('fecha_fin', $actividad->fecha_fin?->format('Y-m-d\TH:i')) }}">
            @error('fecha_fin')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="zona">Zona *</label>
            <select id="zona" name="zona" required>
              <option value="san_martin" @selected(old('zona', $actividad->zona) === 'san_martin')>San Martín</option>
              <option value="tlaxcala" @selected(old('zona', $actividad->zona) === 'tlaxcala')>Tlaxcala</option>
              <option value="ambas" @selected(old('zona', $actividad->zona) === 'ambas')>Ambas</option>
            </select>
            @error('zona')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div>
            <label for="estado">Estado *</label>
            <select id="estado" name="estado" required>
              <option value="planeada" @selected(old('estado', $actividad->estado) === 'planeada')>Planeada</option>
              <option value="en_curso" @selected(old('estado', $actividad->estado) === 'en_curso')>En curso</option>
              <option value="completada" @selected(old('estado', $actividad->estado) === 'completada')>Completada</option>
              <option value="cancelada" @selected(old('estado', $actividad->estado) === 'cancelada')>Cancelada</option>
            </select>
            @error('estado')<div class="field-error">{{ $message }}</div>@enderror
          </div>
          <div style="grid-column:1/-1;">
            <label for="id_usuario_responsable">Responsable</label>
            <select id="id_usuario_responsable" name="id_usuario_responsable">
              <option value="">Asignarme a mí</option>
              @foreach ($usuarios as $usuario)
                <option value="{{ $usuario->id_usuario }}" @selected((string) old('id_usuario_responsable', $actividad->id_responsable) === (string) $usuario->id_usuario)>
                  {{ $usuario->nombre }} {{ $usuario->apellido }} — {{ $usuario->email }}
                </option>
              @endforeach
            </select>
            @error('id_usuario_responsable')<div class="field-error">{{ $message }}</div>@enderror
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;margin-top:20px;">
          <a class="btn btn-secondary" href="{{ route('planning.index') }}">Cancelar</a>
          <div style="display:flex;gap:8px;">
            @if ($actividad->exists)
              <button class="btn btn-danger" type="submit" form="eliminar-actividad">Eliminar</button>
            @endif
            <button class="btn btn-primary" type="submit">{{ $actividad->exists ? 'Guardar cambios' : 'Crear actividad' }}</button>
          </div>
        </div>
      </form>
      @if ($actividad->exists)
        <form id="eliminar-actividad" method="POST" action="{{ route('actividades.destroy', $actividad->id_actividad) }}"
              onsubmit="return confirm('¿Eliminar esta actividad?')">
          @csrf
          @method('DELETE')
        </form>
      @endif
    </div>
  </div>
</div>
@endsection
