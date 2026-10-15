@extends('layouts.app')
@section('title', 'Nuevo beneficiario | Juventud Iskali')
@section('page-title', 'Nuevo beneficiario')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content" style="padding:24px;">

      <!-- Breadcrumb -->
      <div style="margin-bottom:16px;font-size:13px;color:var(--muted);">
        <a href="{{ route('beneficiarios.index') }}" style="color:var(--primary);text-decoration:none;">Beneficiarios</a>
        <span style="margin:0 8px;opacity:.5;">›</span>
        <span>Nuevo Beneficiario</span>
      </div>

      <div class="card" style="max-width:760px;">
        <div class="card-header">
          <h3>Nuevo Beneficiario</h3>
                  </div>

        <div class="card-body" style="padding:24px;">

          
          <form method="POST" action="{{ route('beneficiarios.store') }}" id="form-beneficiario" data-validate="true">
            @csrf

            <!-- SELECTOR TIPO -->
            <div class="form-group" style="margin-bottom:20px;">
              <label style="font-size:13px;font-weight:500;color:var(--muted);display:block;margin-bottom:7px;">
                Tipo de Beneficiario <span style="color:#f44336;">*</span>
              </label>
              <select id="tipo_persona" name="tipo_persona" required onchange="toggleTipoBeneficiario()"
                      style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:inherit;font-size:14px;outline:none;">
                <option value="fisica" @selected(old('tipo_persona', 'fisica') === 'fisica')>Persona Física (individuo / familia)</option>
                <option value="moral" @selected(old('tipo_persona') === 'moral')>Persona Moral (albergue, escuela, organización)</option>
              </select>
              <div class="field-error">@error('tipo_persona'){{ $message }}@enderror</div>
            </div>

            <!-- ═══ PERSONA FÍSICA ═══ -->
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
                           data-msg_alpha="Solo letras y espacios. No uses números ni símbolos especiales.">
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
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad"
                           value="{{ old('edad') }}"
                           placeholder="Ej: 34"
                           min="0" max="150"
                           data-rules="numeric|min_val:0|max_val:150"
                           data-msg_max_val="La edad no puede superar 150 años.">
                    <div class="field-error">@error('edad'){{ $message }}@enderror</div>
                  </div>

                  <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento') }}"
                           data-rules="date">
                  </div>

                  <div class="form-group full">
                    <label for="curp">CURP</label>
                    <input type="text" id="curp" name="curp"
                           value="{{ old('curp') }}"
                           placeholder="HEGJ830428HMNRRL05"
                           maxlength="18"
                           style="text-transform:uppercase;"
                           data-rules="curp"
                           oninput="this.value=this.value.toUpperCase()">
                    <div class="field-error">@error('curp'){{ $message }}@enderror</div>
                  </div>

                </div>
              </div>
            </div>

            <!-- ═══ PERSONA MORAL ═══ -->
            <div id="moral-fields" style="display:none;">
              <div style="background:rgba(232,160,32,.04);border:1px solid rgba(232,160,32,.15);border-radius:10px;padding:20px;margin-bottom:20px;">
                <div style="font-size:12px;font-weight:600;color:var(--amber);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">
                  Datos de la organización
                </div>
                <div class="form-grid">

                  <div class="form-group full">
                    <label for="razon_social">Razón Social / Nombre de la organización <span style="color:#f44336;">*</span></label>
                    <input type="text" id="razon_social" name="razon_social"
                           value="{{ old('razon_social') }}"
                           placeholder="Ej: Albergue Esperanza A.C."
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
                           oninput="this.value=this.value.toUpperCase()">
                    <div class="field-error">@error('rfc'){{ $message }}@enderror</div>
                  </div>

                </div>
              </div>
            </div>

            <!-- ═══ DATOS COMUNES ═══ -->
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;">
              <div style="font-size:12px;font-weight:600;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;">
                Ubicación y contacto
              </div>
              <div class="form-grid">

                <div class="form-group full">
                  <label for="id_comunidad">Comunidad <span style="color:#f44336;">*</span></label>
                  <select id="id_comunidad" name="id_comunidad" required
                          style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:inherit;font-size:14px;outline:none;">
                    <option value="">— Selecciona una comunidad —</option>
                    @foreach ($comunidades as $comunidad)
                      <option value="{{ $comunidad->id_comunidad }}" @selected(old('id_comunidad') == $comunidad->id_comunidad)>{{ $comunidad->nombre }}</option>
                    @endforeach
                                      </select>
                                      <div class="field-error">@error('id_comunidad'){{ $message }}@enderror</div>
                </div>

                <div class="form-group full">
                  <label for="direccion">Dirección</label>
                  <input type="text" id="direccion" name="direccion"
                         value="{{ old('direccion') }}"
                         placeholder="Calle, número, colonia"
                         data-rules="max:255|no_special">
                  <div class="field-error">@error('direccion'){{ $message }}@enderror</div>
                </div>

                <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input type="tel" id="telefono" name="telefono"
                         value="{{ old('telefono') }}"
                         placeholder="222 123 4567"
                         maxlength="15"
                         data-rules="phone">
                  <div class="field-error">@error('telefono'){{ $message }}@enderror</div>
                </div>

                <div class="form-group">
                  <label for="estado">Estado del beneficiario <span style="color:#f44336;">*</span></label>
                  <select id="estado" name="estado" required
                          style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:inherit;font-size:14px;outline:none;">
                    <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo</option>
                    <option value="en_espera" @selected(old('estado') === 'en_espera')>En espera</option>
                    <option value="inactivo" @selected(old('estado') === 'inactivo')>Inactivo</option>
                  </select>
                  <div class="field-error">@error('estado'){{ $message }}@enderror</div>
                </div>

              </div>
            </div>

            <!-- Botones -->
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
              <a href="{{ route('beneficiarios.index') }}"
                 style="padding:10px 24px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);font-size:14px;font-weight:500;text-decoration:none;">
                Cancelar
              </a>
              <button type="submit" class="btn btn-primary" style="padding:10px 28px;">
                Registrar beneficiario              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function toggleTipoBeneficiario() {
  const tipo = document.getElementById('tipo_persona').value;
  document.getElementById('fisica-fields').style.display = tipo === 'fisica' ? 'block' : 'none';
  document.getElementById('moral-fields').style.display  = tipo === 'moral'  ? 'block' : 'none';

  // Ajustar reglas según el tipo seleccionado
  const nombre      = document.getElementById('nombre');
  const apellido    = document.getElementById('apellido');
  const razon       = document.getElementById('razon_social');

  if (tipo === 'fisica') {
    nombre.dataset.rules   = 'required|alpha|min:2|max:100|no_special';
    apellido.dataset.rules = 'required|alpha|min:2|max:100|no_special';
    razon.dataset.rules    = '';
  } else {
    nombre.dataset.rules   = '';
    apellido.dataset.rules = '';
    razon.dataset.rules    = 'required|min:3|max:150|no_special';
  }
}

document.addEventListener('DOMContentLoaded', toggleTipoBeneficiario);
</script>

@endsection
