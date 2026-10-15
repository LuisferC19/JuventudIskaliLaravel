@extends('layouts.app')
@section('title', 'Editar usuario | Juventud Iskali')
@section('page-title', 'Editar usuario')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- Breadcrumb -->
      <div style="margin-bottom:16px;font-size:13px;color:var(--muted,#999);">
        <a href="{{ route('usuarios.index') }}" style="color:var(--primary);">Usuarios</a>
        <span style="margin:0 8px;">›</span>
        <span>Editar Usuario</span>
      </div>

      <div class="card" style="max-width:640px;">

        <div class="card-header">
          <h3>
            <img src="./public/iconos/avatar_usuario.png" alt="Usuario" style="width:20px;height:20px;">
            Editar Usuario          </h3>
        </div>

        <div class="card-body" style="padding:24px;">

                      <!-- Información del usuario actual -->
            <div style="background:var(--surface,#f5f5f5);padding:14px 16px;border-radius:8px;
                        margin-bottom:24px;font-size:13px;color:var(--muted,#666);">
              <strong style="color:var(--text);">ID:</strong> #{{ $usuario->id_usuario }}              &nbsp;·&nbsp;
              <strong style="color:var(--text);">Creado:</strong>
              {{ $usuario->created_at?->format('d/m/Y') ?? '—' }}                          </div>
          
                    <form method="POST" action="{{ route('usuarios.update', $usuario->id_usuario) }}">
                      @csrf
                      @method('PUT')

            
            <div class="form-grid">

              <!-- ── Nombre ───────────────────────────────────────────── -->
              <div class="form-group">
                <label for="nombre">Nombre <span style="color:#f44336;">*</span></label>
                <input type="text"
                       id="nombre"
                       name="nombre"
                       value="{{ old('nombre', $usuario->nombre) }}"
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
                       value="{{ old('apellido', $usuario->apellido) }}"
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
                       value="{{ old('email', $usuario->email) }}"
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
                  Nueva Contraseña (opcional)                </label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Dejar vacío para mantener la actual"
                       minlength="6"
                                              autocomplete="new-password"
                       data-rules="min:6"
                       data-msg_required="La contraseña es obligatoria."
                       data-msg_min="La contraseña debe tener al menos 6 caracteres.">
                <div class="field-error">@error('password'){{ $message }}@enderror</div>
                                  <small style="color:var(--muted,#999);font-size:11px;margin-top:4px;display:block;">
                    Solo ingresa si deseas cambiar la contraseña actual.
                  </small>
                              </div>

              <!-- ── Confirmar contraseña ────────────────────────────── -->
              <div class="form-group" id="campo-confirmar"
                   style="display:none;">
                <label for="confirmar_password">
                  Confirmar Contraseña <span style="color:#f44336;" id="asterisco-confirmar">*</span>
                </label>
                <input type="password"
                       id="confirmar_password"
                       name="confirmar_password"
                       placeholder="Repite la contraseña"
                       minlength="6"
                                              autocomplete="new-password"
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
                      <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario->id_rol) == $rol->id_rol)>{{ $rol->nombre }}</option>
                    @endforeach
                                      </select>
                  <div class="field-error">@error('id_rol'){{ $message }}@enderror</div>
                </div>
              
              <!-- ── Estado activo ───────────────────────────────────── -->
              <div class="form-group full form-checkbox">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                  <input type="hidden" name="activo" value="0">
                  <input type="checkbox" name="activo" value="1"
                         @checked(old('activo', $usuario->activo))>
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
                <img src="./public/iconos/aprobado_carpeta.png" alt="Guardar" style="width:16px;height:16px;">
                Guardar Cambios              </button>
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
  var esEdicion      = true;

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
