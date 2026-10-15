@extends('layouts.app')
@section('title', 'Nuevo donador | Juventud Iskali')
@section('page-title', 'Nuevo donador')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content" style="padding:24px;">

      <!-- Breadcrumb -->
      <div style="margin-bottom:16px;font-size:13px;color:var(--muted);">
        <a href="{{ route('donadores.index') }}" style="color:var(--primary);text-decoration:none;">Donadores</a>
        <span style="margin:0 8px;opacity:.5;">›</span>
        <span>Nuevo Donador</span>
      </div>

      <div class="card" style="max-width:760px;">
        <div class="card-header">
          <h3>Nuevo donador</h3>
                  </div>

        <div class="card-body" style="padding:24px;">

          <!-- Mensaje de error de sesión -->
          
          
          <form method="POST" action="{{ route('donadores.store') }}" id="form-donador" data-validate="true">
            @csrf

            <!-- SELECTOR TIPO -->
            <div class="form-group" style="margin-bottom:20px;">
              <label for="tipo_persona" style="font-size:13px;font-weight:500;color:var(--muted);display:block;margin-bottom:7px;">
                Tipo de Donador <span style="color:#f44336;">*</span>
              </label>
              <select id="tipo_persona" name="tipo_persona" required onchange="toggleDonadorType()"
                      style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:inherit;font-size:14px;outline:none;">
                <option value="fisica" @selected(old('tipo_persona', 'fisica') === 'fisica')>Persona Física (individuo)</option>
                <option value="moral" @selected(old('tipo_persona') === 'moral')>Persona Moral (empresa / organización)</option>
                <option value="grupo" @selected(old('tipo_persona') === 'grupo')>Grupo</option>
                <option value="anonimo" @selected(old('tipo_persona') === 'anonimo')>Anónimo</option>
              </select>
              <div class="field-error">@error('tipo_persona'){{ $message }}@enderror</div>
            </div>

            <!-- ═══ CAMPOS PERSONA FÍSICA ═══ -->
            <div id="fisica-fields" style="display:block;">
              <div style="background:rgba(10,175,160,.04);border:1px solid rgba(10,175,160,.12);border-radius:10px;padding:20px;margin-bottom:20px;">
                <div style="font-size:12px;font-weight:600;color:var(--primary);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">
                  Datos personales
                </div>
                <div class="form-grid">

                  <div class="form-group">
                    <label for="nombre">Nombre <span style="color:#f44336;">*</span></label>
                    <input type="text" id="nombre" name="nombre"
                           value="{{ old('nombre') }}"
                           placeholder="Nombre(s)"
                           autocomplete="given-name"
                           data-rules="required|alpha|min:2|max:100|no_special"
                           data-msg_required="El nombre es obligatorio."
                           data-msg_alpha="Solo letras y espacios. No uses números ni símbolos.">
                    <div class="field-error">@error('nombre'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="apellido">Apellido <span style="color:#f44336;">*</span></label>
                    <input type="text" id="apellido" name="apellido"
                           value="{{ old('apellido') }}"
                           placeholder="Apellido(s)"
                           autocomplete="family-name"
                           data-rules="required|alpha|min:2|max:100|no_special">
                    <div class="field-error">@error('apellido'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="curp">CURP</label>
                    <input type="text" id="curp" name="curp"
                           value="{{ old('curp') }}"
                           placeholder="CURP (18 caracteres)"
                           maxlength="18"
                           style="text-transform:uppercase;"
                           data-rules="curp"
                           data-msg_curp="La CURP debe tener 18 caracteres con el formato correcto (ej: HEGJ830428HMNRRL05)."
                           oninput="this.value=this.value.toUpperCase()">
                    <div class="field-error">@error('curp'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento') }}"
                           data-rules="date">
                    <div class="field-error">@error('fecha_nacimiento'){{ $message }}@enderror</div>
                  </div>

                </div>
              </div>
            </div>

            <!-- ═══ CAMPOS PERSONA MORAL ═══ -->
            <div id="moral-fields" style="display:none;">
              <div style="background:rgba(232,160,32,.04);border:1px solid rgba(232,160,32,.15);border-radius:10px;padding:20px;margin-bottom:20px;">
                <div style="font-size:12px;font-weight:600;color:var(--amber);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">
                  Datos de la organización
                </div>
                <div class="form-grid">

                  <div class="form-group full">
                    <label for="razon_social">Razón Social <span style="color:#f44336;">*</span></label>
                    <input type="text" id="razon_social" name="razon_social"
                           value="{{ old('razon_social') }}"
                           placeholder="Nombre oficial de la empresa u organización"
                           data-rules="required|min:3|max:150|no_special">
                    <div class="field-error">@error('razon_social'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="rfc">RFC</label>
                    <input type="text" id="rfc" name="rfc"
                           value="{{ old('rfc') }}"
                           placeholder="RFC (12 caracteres)"
                           maxlength="13"
                           style="text-transform:uppercase;"
                           data-rules="rfc_moral"
                           data-msg_rfc_moral="El RFC de persona moral debe tener 12 caracteres (ej: EAL240201ABC)."
                           oninput="this.value=this.value.toUpperCase()">
                    <div class="field-error">@error('rfc'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="representante_legal">Representante Legal</label>
                    <input type="text" id="representante_legal" name="representante_legal"
                           value="{{ old('representante_legal') }}"
                           placeholder="Nombre del representante"
                           data-rules="alpha|max:200|no_special">
                    <div class="field-error">@error('representante_legal'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group full">
                    <label for="giro_comercial">Giro Comercial</label>
                    <input type="text" id="giro_comercial" name="giro_comercial"
                           value="{{ old('giro_comercial') }}"
                           placeholder="Ej: Empresa privada, Fundación sin fines de lucro"
                           data-rules="max:100|no_special">
                    <div class="field-error">@error('giro_comercial'){{ $message }}@enderror</div>
                  </div>

                </div>
              </div>
            </div>

            <div id="grupo-fields" style="display:none;">
              <div class="form-grid">
                <div class="form-group full">
                  <label for="nombre_grupo">Nombre del grupo <span style="color:#f44336;">*</span></label>
                  <input type="text" id="nombre_grupo" name="nombre_grupo" value="{{ old('nombre_grupo') }}" maxlength="150">
                  <div class="field-error">@error('nombre_grupo'){{ $message }}@enderror</div>
                </div>
                <div class="form-group full">
                  <label for="representante">Representante</label>
                  <input type="text" id="representante" name="representante" value="{{ old('representante') }}" maxlength="200">
                  <div class="field-error">@error('representante'){{ $message }}@enderror</div>
                </div>
              </div>
            </div>

            <!-- ═══ CAMPOS COMUNES ═══ -->
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;">
              <div style="font-size:12px;font-weight:600;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">
                Información de contacto
              </div>
              <div class="form-grid">

                <div class="form-group full">
                  <label for="email">Correo electrónico <span style="color:#f44336;">*</span></label>
                  <input type="email" id="email" name="email"
                         value="{{ old('email') }}"
                         placeholder="correo@ejemplo.com"
                         autocomplete="email"
                         data-rules="required|email">
                  <div class="field-error">@error('email'){{ $message }}@enderror</div>
                </div>

                <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input type="tel" id="telefono" name="telefono"
                         value="{{ old('telefono') }}"
                         placeholder="222 123 4567"
                         maxlength="15"
                         data-rules="phone"
                         data-msg_phone="Ingresa 10 dígitos. Solo números y guiones.">
                  <div class="field-error">@error('telefono'){{ $message }}@enderror</div>
                </div>

                <div class="form-group">
                  <label for="puntos_acumulados">Puntos acumulados</label>
                  <input type="number" id="puntos_acumulados" name="puntos_acumulados"
                         value="{{ old('puntos_acumulados', 0) }}"
                         min="0" max="9999999"
                         placeholder="0"
                         data-rules="numeric|min_val:0"
                         data-msg_min_val="Los puntos no pueden ser negativos.">
                  <div class="field-error">@error('puntos_acumulados'){{ $message }}@enderror</div>
                </div>

                <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:28px;">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', false)) style="width:18px;height:18px;accent-color:var(--primary);">
                    Donador activo
                  </label>
                </div>

              </div>
            </div>

            <!-- Botones -->
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
              <a href="{{ route('donadores.index') }}"
                 style="padding:10px 24px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);font-size:14px;font-weight:500;text-decoration:none;transition:all .2s;"
                 onmouseover="this.style.borderColor='#888'" onmouseout="this.style.borderColor='var(--border)'">
                Cancelar
              </a>
              <button type="submit" class="btn btn-primary" style="padding:10px 28px;">
                Guardar donador              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS de validaciones -->
<script>
function toggleDonadorType() {
  const tipo = document.getElementById('tipo_persona').value;
  document.getElementById('fisica-fields').style.display = tipo === 'fisica' ? 'block' : 'none';
  document.getElementById('moral-fields').style.display  = tipo === 'moral'  ? 'block' : 'none';
  document.getElementById('grupo-fields').style.display  = tipo === 'grupo'  ? 'block' : 'none';

  // Ajustar el atributo required dinámicamente
  document.getElementById('nombre').dataset.rules       = tipo === 'fisica' ? 'required|alpha|min:2|max:100|no_special' : '';
  document.getElementById('apellido').dataset.rules     = tipo === 'fisica' ? 'required|alpha|min:2|max:100|no_special' : '';
  document.getElementById('razon_social').dataset.rules = tipo === 'moral'  ? 'required|min:3|max:150|no_special' : '';
  document.getElementById('nombre_grupo').dataset.rules = tipo === 'grupo' ? 'required|max:150' : '';
}

document.addEventListener('DOMContentLoaded', toggleDonadorType);
</script>

@endsection
