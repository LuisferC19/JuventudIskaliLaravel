@extends('layouts.app')
@section('title', 'Usuarios | Juventud Iskali')
@section('page-title', 'Usuarios')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- ===== Tarjetas de estadísticas ===== -->
      <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:var(--primary);">{{ $totalUsuarios }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Usuarios totales</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#4caf50;">{{ $usuariosActivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Usuarios activos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#f44336;">{{ $usuariosInactivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Usuarios inactivos</div>
        </div>

      </div>

      <!-- ===== Tabla principal ===== -->
      <div class="card">
        <div class="card-header">
          <h3>
            Gestión de Usuarios
          </h3>
          <div class="toolbar">
            <input
              type="text"
              class="search-input"
              id="buscar-usuarios"
              placeholder="Buscar por nombre, email o rol…"
              oninput="filtrarTabla(this,'tabla-usuarios')"
            >
            @can('permiso', ['usuarios', 'crear'])
              <button class="btn btn-primary btn-icon" type="button" onclick="openModal('modal-crear-usuario')">
             <img src="./public/iconos/nuevo.png" alt="Nuevo" style="width:16px;height:16px;">
              Nuevo Usuario
              </button>
            @endcan
          </div>
        </div>

        <!-- ── Mensaje de retroalimentación ── -->
        
        <!-- ── Tabla de usuarios ── -->
        <div style="overflow-x:auto;margin-top:16px;">
          <table id="tabla-usuarios" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="background:var(--surface,#f5f5f5);border-bottom:2px solid var(--border,#ddd);">
                <th style="padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">ID</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Nombre Completo</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Correo Electrónico</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Rol</th>
                <th style="padding:12px 16px;text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Estado</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Creado</th>
                <th style="padding:12px 16px;text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($usuarios as $usuario)
                @php
                  $nombre = trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? ''));
                  $activo = (bool) $usuario->activo;
                @endphp
                <tr style="border-bottom:1px solid var(--border,#eee);" onmouseover="this.style.background='var(--surface,#fafafa)'" onmouseout="this.style.background=''">
                  <td style="padding:12px 16px;color:var(--muted,#999);font-size:13px;">#{{ $usuario->id_usuario }}</td>
                  <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                      <div style="width:34px;height:34px;border-radius:50%;background:var(--primary,#3f51b5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0;">
                        {{ strtoupper(substr($usuario->nombre ?? '', 0, 1) . substr($usuario->apellido ?? '', 0, 1)) }}
                      </div>
                      <div><strong>{{ $nombre ?: '—' }}</strong></div>
                    </div>
                  </td>
                  <td style="padding:12px 16px;font-size:13px;">{{ $usuario->email }}</td>
                  <td style="padding:12px 16px;">
                    <span style="background:#e8eaf6;color:#3949ab;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:500;">{{ $usuario->rol ?? 'Sin rol' }}</span>
                  </td>
                  <td style="padding:12px 16px;text-align:center;">
                    <span style="background:{{ $activo ? '#e8f5e9' : '#ffebee' }};color:{{ $activo ? '#2e7d32' : '#c62828' }};padding:3px 10px;border-radius:12px;font-size:12px;font-weight:500;">● {{ $activo ? 'Activo' : 'Inactivo' }}</span>
                  </td>
                  <td style="padding:12px 16px;font-size:12px;color:var(--muted,#999);">{{ $usuario->created_at ? \Illuminate\Support\Carbon::parse($usuario->created_at)->format('d/m/Y') : '—' }}</td>
                  <td style="padding:12px 16px;text-align:center;white-space:nowrap;">
                    @can('permiso', ['usuarios', 'editar'])
                      <a class="btn btn-secondary btn-sm" href="{{ route('usuarios.edit', $usuario->id_usuario) }}">Editar</a>
                      @if ((int) ($usuario->intentos_fallidos ?? 0) >= 5)
                        <form method="POST" action="{{ route('usuarios.desbloquear', $usuario->id_usuario) }}" style="display:inline;">
                          @csrf
                          <button class="btn btn-secondary btn-sm" type="submit">Desbloquear</button>
                        </form>
                      @endif
                    @endcan
                    @can('permiso', ['usuarios', 'eliminar'])
                      @if ((int) $usuario->id_usuario !== (int) auth()->id())
                        <form method="POST" action="{{ route('usuarios.destroy', $usuario->id_usuario) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                        </form>
                      @endif
                    @endcan
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" style="padding:16px;text-align:center;">No hay usuarios registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- ── Pie de tabla ── -->
        <div style="margin:16px;padding-top:12px;border-top:1px solid var(--border,#eee);
                    color:var(--muted,#999);font-size:12px;display:flex;justify-content:space-between;align-items:center;">
          <span><strong>{{ $totalUsuarios }}</strong> usuario(s) registrado(s)</span>
        </div>

      </div><!-- /card tabla -->

    </div><!-- /content -->
  </div><!-- /main -->
</div>

<div class="modal-overlay" id="modal-crear-usuario" style="{{ $errors->any() ? 'display:flex;' : 'display:none;' }}">
  <div class="modal" style="max-width:560px;width:95%;">

    <div class="modal-header">
      <h3>
        <img src="./public/iconos/agregar_usuario_circulo.png" alt="Nuevo usuario" style="width:24px;height:24px;">
        Crear Nuevo Usuario
      </h3>
      <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-crear-usuario')">✕</button>
    </div>

    <form method="POST" action="{{ route('usuarios.store') }}">
      @csrf

      <!-- CORRECCIÓN #5: Token CSRF (faltaba en el modal de crear) -->
      
      <div class="modal-body">
        <div class="form-grid">

          <!-- Nombre -->
          <div class="form-group">
            <label for="c_nombre">Nombre <span style="color:#f44336;">*</span></label>
            <input type="text"
                   id="c_nombre"
                   name="nombre"
                   value="{{ old('nombre') }}"
                   placeholder="Ej: Juan"
                   maxlength="100"
                   required
                   autocomplete="given-name"
                   data-rules="required|alpha|min:2|max:100|no_special"
                   data-msg_required="El nombre es obligatorio."
                   data-msg_alpha="Solo letras y espacios. No uses números ni símbolos."
                   data-msg_min="El nombre debe tener al menos 2 caracteres."
                   data-msg_no_special="No se permiten caracteres especiales.">
            <div class="field-error">@error('nombre'){{ $message }}@enderror</div>
          </div>

          <!-- Apellido -->
          <div class="form-group">
            <label for="c_apellido">Apellido <span style="color:#f44336;">*</span></label>
            <input type="text"
                   id="c_apellido"
                   name="apellido"
                   value="{{ old('apellido') }}"
                   placeholder="Ej: García López"
                   maxlength="100"
                   required
                   autocomplete="family-name"
                   data-rules="required|alpha|min:2|max:100|no_special"
                   data-msg_required="El apellido es obligatorio."
                   data-msg_alpha="Solo letras y espacios. No uses números ni símbolos."
                   data-msg_min="El apellido debe tener al menos 2 caracteres."
                   data-msg_no_special="No se permiten caracteres especiales.">
            <div class="field-error">@error('apellido'){{ $message }}@enderror</div>
          </div>

          <!-- Email -->
          <div class="form-group full">
            <label for="c_email">Correo Electrónico <span style="color:#f44336;">*</span></label>
            <input type="email"
                   id="c_email"
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

          <!-- Contraseña -->
          <div class="form-group">
            <label for="c_password">Contraseña <span style="color:#f44336;">*</span></label>
            <input type="password"
                   id="c_password"
                   name="password"
                   placeholder="Mínimo 6 caracteres"
                   minlength="6"
                   required
                   autocomplete="new-password"
                   data-rules="required|min:6"
                   data-msg_required="La contraseña es obligatoria."
                   data-msg_min="La contraseña debe tener al menos 6 caracteres.">
            <div class="field-error">@error('password'){{ $message }}@enderror</div>
          </div>

          <!-- Confirmar contraseña -->
          <div class="form-group">
            <label for="c_confirmar">Confirmar Contraseña <span style="color:#f44336;">*</span></label>
            <input type="password"
                   id="c_confirmar"
                   name="confirmar_password"
                   placeholder="Repite la contraseña"
                   minlength="6"
                   required
                   autocomplete="new-password"
                   data-rules="required|match:c_password"
                   data-msg_required="Confirma la contraseña."
                   data-msg_match="Las contraseñas no coinciden.">
            <div class="field-error">@error('confirmar_password'){{ $message }}@enderror</div>
          </div>

          <!-- Rol -->
          <div class="form-group full">
            <label for="c_rol">Rol del Usuario <span style="color:#f44336;">*</span></label>
            <select id="c_rol" name="id_rol" required
                    data-rules="required"
                    data-msg_required="Debes seleccionar un rol.">
              <option value="">— Selecciona un rol —</option>
              @foreach ($roles as $rol)
                <option value="{{ $rol->id_rol }}" @selected(old('id_rol') == $rol->id_rol)>{{ $rol->nombre }}</option>
              @endforeach
            </select>
            <div class="field-error">@error('id_rol'){{ $message }}@enderror</div>
          </div>

          <!-- Activo -->
          <div class="form-group full form-checkbox">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="hidden" name="activo" value="0">
              <input type="checkbox" name="activo" value="1" @checked(old('activo', true))>
              <span>Usuario activo al crear</span>
            </label>
          </div>

        </div><!-- /form-grid -->
      </div><!-- /modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-crear-usuario')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <img src="./public/iconos/aprobado_carpeta.png" alt="Guardar" style="width:16px;height:16px;">
          Guardar Usuario
        </button>
      </div>

    </form>
  </div><!-- /modal -->
</div><!-- /modal-overlay -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  var overlay = document.getElementById('modal-crear-usuario');
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal('modal-crear-usuario');
    });
  }
});
</script>

@endsection
