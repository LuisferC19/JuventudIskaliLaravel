/**
 * public/js/iskali_validaciones.js
 * Validaciones de formularios en tiempo real para Iskali.
 * Importar DESPUÉS de iskalli.js en cualquier vista que tenga formularios.
 *
 * USO:
 *   <script src="<?= BASE_URL ?>/public/js/iskali_validaciones.js"></script>
 *   En el formulario HTML agrega: data-validate="true"
 *   En cada campo agrega:  data-rules="required|alpha|min:2|max:100"
 *
 * REGLAS DISPONIBLES:
 *   required       Campo obligatorio (no vacío)
 *   alpha          Solo letras, tildes, ñ y espacios
 *   alphanum       Letras, números y espacios
 *   email          Formato de correo electrónico
 *   numeric        Solo números
 *   phone          Teléfono de 10 dígitos (permite guiones y espacios)
 *   curp           Formato CURP mexicano
 *   rfc_fisica     RFC persona física (13 chars)
 *   rfc_moral      RFC persona moral (12 chars)
 *   rfc            RFC (12 o 13 chars)
 *   min:N          Mínimo N caracteres
 *   max:N          Máximo N caracteres
 *   min_val:N      Valor mínimo numérico
 *   max_val:N      Valor máximo numérico
 *   date           Fecha válida YYYY-MM-DD
 *   match:#id      Debe coincidir con el valor del campo #id
 *   no_special     Sin caracteres especiales (<>'"&;)
 */

;(function() {
  'use strict';

  /* ════════════════════════════════════════
     MENSAJES POR DEFECTO
  ════════════════════════════════════════ */
  const MSGS = {
    required:    'Este campo es obligatorio.',
    alpha:       'Solo se permiten letras, tildes y espacios. Sin números ni símbolos.',
    alphanum:    'Solo se permiten letras, números y espacios.',
    email:       'Ingresa un correo electrónico válido (ej: correo@dominio.com).',
    numeric:     'Solo se permiten números.',
    phone:       'Ingresa un teléfono válido de 10 dígitos.',
    curp:        'El CURP debe tener el formato correcto (18 caracteres alfanuméricos).',
    rfc_fisica:  'El RFC de persona física debe tener 13 caracteres.',
    rfc_moral:   'El RFC de persona moral debe tener 12 caracteres.',
    rfc:         'El RFC debe tener 12 o 13 caracteres.',
    min:         'Mínimo {n} caracteres.',
    max:         'Máximo {n} caracteres permitidos.',
    min_val:     'El valor mínimo permitido es {n}.',
    max_val:     'El valor máximo permitido es {n}.',
    date:        'Ingresa una fecha válida.',
    match:       'Los valores no coinciden.',
    no_special:  'No se permiten caracteres especiales (< > \' " & ;).',
  };

  /* ════════════════════════════════════════
     REGLAS DE VALIDACIÓN
  ════════════════════════════════════════ */
  const RULES = {
    required:   v => v.trim().length > 0,
    alpha:      v => /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/.test(v.trim()),
    alphanum:   v => /^[a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ\s]+$/.test(v.trim()),
    email:      v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()),
    numeric:    v => /^\d+$/.test(v.trim()),
    phone:      v => /^[\d\s\-\+\(\)]{10,15}$/.test(v.trim()) && v.replace(/\D/g,'').length === 10,
    curp:       v => /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/i.test(v.trim().toUpperCase()),
    rfc_fisica: v => /^[A-Z&Ñ]{4}\d{6}[A-Z\d]{3}$/i.test(v.trim().toUpperCase()),
    rfc_moral:  v => /^[A-Z&Ñ]{3}\d{6}[A-Z\d]{3}$/i.test(v.trim().toUpperCase()),
    rfc:        v => /^[A-Z&Ñ]{3,4}\d{6}[A-Z\d]{3}$/i.test(v.trim().toUpperCase()),
    no_special: v => !/[<>'"&;]/.test(v),
    date:       v => { const d = new Date(v); return !isNaN(d.getTime()); },
    min:       (v,n) => v.trim().length >= parseInt(n, 10),
    max:       (v,n) => v.trim().length <= parseInt(n, 10),
    min_val:   (v,n) => parseFloat(v) >= parseFloat(n),
    max_val:   (v,n) => parseFloat(v) <= parseFloat(n),
    match:     (v,id) => {
      const el = document.getElementById(id.replace('#',''));
      return el ? v === el.value : true;
    },
  };

  /* ════════════════════════════════════════
     HELPERS UI
  ════════════════════════════════════════ */
  function getErrorEl(input) {
    // Buscar elemento hermano con class field-error
    let el = input.parentNode.querySelector('.field-error');
    if (!el) {
      el = document.createElement('div');
      el.className = 'field-error';
      el.style.cssText = 'font-size:11px;color:#f44336;margin-top:5px;display:none;';
      input.parentNode.appendChild(el);
    }
    return el;
  }

  function showError(input, msg) {
    const el = getErrorEl(input);
    el.textContent = msg;
    el.style.display = 'block';
    input.classList.add('invalid');
    input.classList.remove('valid');
  }

  function clearError(input) {
    const el = getErrorEl(input);
    el.style.display = 'none';
    input.classList.remove('invalid');
    if (input.value.trim()) input.classList.add('valid');
  }

  /* ════════════════════════════════════════
     VALIDAR UN CAMPO
  ════════════════════════════════════════ */
  function validateField(input) {
    const rulesStr = input.dataset.rules || '';
    if (!rulesStr) return true;

    const value = input.value;
    const rules  = rulesStr.split('|');

    for (const rule of rules) {
      const [name, param] = rule.split(':');
      const fn = RULES[name];
      if (!fn) continue;

      // Si el campo está vacío y la regla no es "required", saltar
      if (name !== 'required' && value.trim() === '') continue;

      const ok = param !== undefined ? fn(value, param) : fn(value);

      if (!ok) {
        let msg = input.dataset['msg_' + name] || MSGS[name] || `Error en el campo.`;
        if (param) msg = msg.replace('{n}', param);
        showError(input, msg);
        return false;
      }
    }

    clearError(input);
    return true;
  }

  /* ════════════════════════════════════════
     BLOQUEAR TECLAS SEGÚN TIPO DE CAMPO
  ════════════════════════════════════════ */
  function blockInvalidKeys(input) {
    const rules = (input.dataset.rules || '').split('|');

    function isControlKey(e) {
      return e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1;
    }

    function handlePaste(e, regex, replaceMsg) {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      const clean = text.replace(regex, '');
      document.execCommand('insertText', false, clean);
      if (text !== clean) flashError(input, replaceMsg);
    }

    if (rules.includes('alpha')) {
      input.addEventListener('keydown', function(e) {
        if (isControlKey(e)) return;
        if (!/[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/.test(e.key)) {
          e.preventDefault();
          flashError(input, 'Solo letras y espacios permitidos.');
        }
      });

      input.addEventListener('paste', function(e) {
        handlePaste(e, /[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g, 'Se eliminaron caracteres no permitidos.');
      });
    }

    if (rules.includes('alphanum')) {
      input.addEventListener('keydown', function(e) {
        if (isControlKey(e)) return;
        if (!/[a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ\s]/.test(e.key)) {
          e.preventDefault();
          flashError(input, 'Solo letras, números y espacios permitidos.');
        }
      });

      input.addEventListener('paste', function(e) {
        handlePaste(e, /[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜñÑ\s]/g, 'Se eliminaron caracteres no permitidos.');
      });
    }

    if (rules.includes('numeric')) {
      input.addEventListener('keydown', function(e) {
        if (isControlKey(e)) return;
        if (!/[0-9]/.test(e.key)) {
          e.preventDefault();
          flashError(input, 'Solo se permiten números.');
        }
      });

      input.addEventListener('paste', function(e) {
        handlePaste(e, /[^0-9]/g, 'Solo se permiten números.');
      });
    }

    if (rules.includes('phone')) {
      input.addEventListener('keydown', function(e) {
        if (isControlKey(e)) return;
        if (!/[\d\s\-\+\(\)]/.test(e.key)) {
          e.preventDefault();
          flashError(input, 'Solo dígitos y guiones permitidos.');
        }
      });

      input.addEventListener('paste', function(e) {
        handlePaste(e, /[^\d\s\-\+\(\)]/g, 'Se eliminaron caracteres no permitidos.');
      });
    }

    if (rules.includes('no_special')) {
      input.addEventListener('keydown', function(e) {
        if (isControlKey(e)) return;
        if (/[<>'"&;]/.test(e.key)) {
          e.preventDefault();
          flashError(input, 'Caracteres especiales no permitidos.');
        }
      });
    }
  }

  /* Flash de error temporal (borde rojo por 1.5 s) */
  function flashError(input, msg) {
    const el = getErrorEl(input);
    el.textContent = msg;
    el.style.display = 'block';
    input.style.borderColor = '#f44336';
    clearTimeout(input._flashTimer);
    input._flashTimer = setTimeout(() => {
      el.style.display = 'none';
      input.style.borderColor = '';
    }, 1800);
  }

  /* ════════════════════════════════════════
     INICIALIZAR TODOS LOS FORMULARIOS
  ════════════════════════════════════════ */
  function init() {
    const forms = document.querySelectorAll('form[data-validate="true"]');

    forms.forEach(form => {
      const fields = form.querySelectorAll('input[data-rules], select[data-rules], textarea[data-rules]');

      fields.forEach(input => {
        // Validación en tiempo real
        input.addEventListener('input',  () => validateField(input));
        input.addEventListener('blur',   () => validateField(input));
        input.addEventListener('change', () => validateField(input));

        // Bloquear teclas no permitidas
        blockInvalidKeys(input);
      });

      // Validación al enviar
      form.addEventListener('submit', function(e) {
        let valid = true;
        fields.forEach(input => {
          if (!validateField(input)) valid = false;
        });
        if (!valid) {
          e.preventDefault();
          // Scroll al primer error
          const first = form.querySelector('.invalid');
          if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    });
  }

  // Ejecutar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Exponer API pública para validación manual
  window.IskalliValidar = {
    campo: validateField,
    form: function(formEl) {
      let valid = true;
      formEl.querySelectorAll('[data-rules]').forEach(f => {
        if (!validateField(f)) valid = false;
      });
      return valid;
    }
  };

})();