@extends('layouts.app')
@section('title', 'Nuevo usuario | Juventud Iskali')
@section('page-title', 'Nuevo usuario')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- Breadcrumb -->
      <div style="margin-bottom:16px;font-size:13px;color:var(--muted,#999);">
        <a href="{{ route('usuarios.index') }}" style="color:var(--primary);">Usuarios</a>
        <span style="margin:0 8px;">›</span>
        <span>Nuevo Usuario</span>
      </div>

      <div class="card" style="max-width:640px;">

        <div class="card-header">
          <h3>
            <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/avatar_usuario.png') }}" alt="Usuario" style="width:20px;height:20px;">
            Nuevo Usuario          </h3>
        </div>

        <div class="card-body" style="padding:24px;">

          
                    <form method="POST" action="{{ route('usuarios.store') }}">
                      @csrf

            
            <div class="form-grid">

              <!-- ── Nombre ───────────────────────────────────────────── -->
              <div class="form-group">
                <label for="nombre">Nombre <span style="color:#f44336;">*</span></label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       placeholder="Ej: Juan"
                       maxlength="100"
                       required
                       autocomplete="given-name"
                       inputmode="text"
                       data-rules="required|alpha|min:2|max:100|no_special"
                       data-msg_required="El nombre es obligatorio."
                       data-msg_alpha="Solo letras y espacios. No uses números ni símbolos."
                       data-msg_min="El nombre debe tener al menos 2 caracteres."
                       data-msg_max="El nombre no puede superar 100 caracteres."
                       data-msg_no_special="No se permiten caracteres especiales.">
                <div class="field-error">@error('nombre'){{ $message }}@enderror</div>
              </div>

              <!-- ── Apellido ─────────────────────────────────────────── -->
              <div class="form-group">
                <label for="apellido">Apellido <span style="color:#f44336;">*</span></label>
                <input type="text"
                       id="apellido"
                       name="apellido"
                       value="{{ old('apellido') }}"
                       placeholder="Ej: García López"
                       maxlength="100"
                       required
                       autocomplete="family-name"
                       inputmode="text"
                       data-rules="required|alpha|min:2|max:100|no_special"
                       data-msg_required="El apellido es obligatorio."
                       data-msg_alpha="Solo letras y espacios. No uses números ni símbolos."
                       data-msg_min="El apellido debe tener al menos 2 caracteres."
                       data-msg_max="El apellido no puede superar 100 caracteres."
                       data-msg_no_special="No se permiten caracteres especiales.">
                <div class="field-error">@error('apellido'){{ $message }}@enderror</div>
              </div>

              <!-- ── Correo electrónico ──────────────────────────────── -->
              <div class="form-group full">
                <label for="email">Correo Electrónico <span style="color:#f44336;">*</span></label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="correo@iskalli.mx"
                       maxlength="150"
                       required
                       autocomplete="email"
                       data-rules="required|email|max:150"
                       data-msg_required="El correo electrónico es obligatorio."
                       data-msg_email="Ingresa un correo válido (ej: correo@dominio.com)."
                       data-msg_max="El correo no puede superar 150 caracteres.">
                <div class="field-error">@error('email'){{ $message }}@enderror</div>
              </div>

              <!-- ── Contraseña ───────────────────────────────────────── -->
              <div class="form-group">
                <label for="password">
                  Contraseña *                </label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Mínimo 6 caracteres"
                       minlength="6"
                       required                       autocomplete="new-password"
                       data-rules="required|min:6"
                       data-msg_required="La contraseña es obligatoria."
                       data-msg_min="La contraseña debe tener al menos 6 caracteres.">
                <div class="field-error">@error('password'){{ $message }}@enderror</div>
                              </div>

              <!-- ── Confirmar contraseña ────────────────────────────── -->
              <div class="form-group" id="campo-confirmar"
                   style="">
                <label for="confirmar_password">
                  Confirmar Contraseña <span style="color:#f44336;" id="asterisco-confirmar">*</span>
                </label>
                <input type="password"
                       id="confirmar_password"
                       name="confirmar_password"
                       placeholder="Repite la contraseña"
                       minlength="6"
                       required                       autocomplete="new-password"
                       data-rules="match:password"
                       data-msg_match="Las contraseñas no coinciden.">
                <div class="field-error">@error('confirmar_password'){{ $message }}@enderror</div>
              </div>

              <!-- ── Rol del usuario ──────────────────────────────────── -->
                              <div class="form-group full">
                  <label for="id_rol">Rol del Usuario <span style="color:#f44336;">*</span></label>
                  <select id="id_rol" name="id_rol" required
                          data-rules="required"
                          data-msg_required="Debes seleccionar un rol.">
                    <option value="">— Selecciona un rol —</option>
                    @foreach ($roles as $rol)
                      <option value="{{ $rol->id_rol }}" @selected(old('id_rol') == $rol->id_rol)>{{ $rol->nombre }}</option>
                    @endforeach
                                      </select>
                  <div class="field-error">@error('id_rol'){{ $message }}@enderror</div>
                </div>
              
              <!-- ── Estado activo ───────────────────────────────────── -->
              <div class="form-group full form-checkbox">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                  <input type="hidden" name="activo" value="0">
                  <input type="checkbox" name="activo" value="1"
                         @checked(old('activo', true))>
                  <span>Usuario activo</span>
                </label>
              </div>

            </div><!-- /form-grid -->

            <!-- Botones de acción -->
            <div style="display:flex;gap:12px;margin-top:24px;padding-top:20px;
                        border-top:1px solid var(--border,#eee);">
              <a href="{{ route('usuarios.index') }}"
                 class="btn btn-secondary">
                ← Volver a la Lista
              </a>
              <button type="submit" class="btn btn-primary">
                <img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/iconos/aprobado_carpeta.png') }}" alt="Guardar" style="width:16px;height:16px;">
                Crear Usuario              </button>
            </div>

          </form>
        </div><!-- /card-body -->
      </div><!-- /card -->

    </div><!-- /content -->
  </div><!-- /main -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var passwordInput  = document.getElementById('password');
  var confirmarCampo = document.getElementById('campo-confirmar');
  var confirmarInput = document.getElementById('confirmar_password');
  var esEdicion      = false;

  if (esEdicion) {
    // En edición: mostrar/ocultar campo "confirmar" según si hay texto en password
    passwordInput.addEventListener('input', function () {
      if (this.value.length > 0) {
        confirmarCampo.style.display = 'block';
        confirmarInput.setAttribute('data-rules', 'required|match:password');
        confirmarInput.setAttribute('required', '');
      } else {
        confirmarCampo.style.display = 'none';
        confirmarInput.removeAttribute('data-rules');
        confirmarInput.removeAttribute('required');
        confirmarInput.value = '';
      }
    });
  }
});
</script>

@endsection
