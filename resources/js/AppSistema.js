/**
 * public/js/iskalli.js
 * JavaScript principal del Sistema Iskalli
 * Contiene funcionalidad de modales, toast, filtrado de tablas y landing.
 */

'use strict';

/* ══════════════════════════════════════
   MODALES
   ══════════════════════════════════════ */

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.add('open');
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove('open');
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        overlay.classList.remove('open');
      }
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
        m.classList.remove('open');
      });
    }
  });
});

var mensajesGuardado = {
  'donación':                 'Donación registrada. Inventario actualizado.',
  'beneficiario':             'Beneficiario registrado correctamente.',
  'entrega':                  'Entrega registrada correctamente.',
  'campaña':                  'Campaña creada correctamente.',
  'avance de campaña':        'Avance de campaña registrado.',
  'movimiento de inventario': 'Movimiento de inventario guardado.',
  'donador':                  'Donador registrado correctamente.',
  'voluntario':               'Voluntario registrado correctamente.',
  'asignación':               'Asignación de voluntario guardada.',
  'reconocimiento':           'Reconocimiento emitido correctamente.',
  'queja':                    'Queja / sugerencia registrada.',
  'notificación':             'Notificación enviada.',
  'usuario':                  'Usuario creado correctamente.',
};

var _toastTimer = null;

function showToast(mensaje) {
  var toast    = document.getElementById('toast');
  var toastMsg = document.getElementById('toast-msg');

  if (!toast || !toastMsg) return;

  if (_toastTimer) clearTimeout(_toastTimer);

  toastMsg.textContent = mensaje;
  toast.classList.add('visible');

  _toastTimer = setTimeout(function () {
    toast.classList.remove('visible');
  }, 3000);
}

function saveAndClose(modalId, tipo) {
  closeModal(modalId);
  var msg = mensajesGuardado[tipo] || 'Registro guardado correctamente.';
  showToast(msg);
}

function filtrarTabla(input, tablaId) {
  var texto = input.value.toLowerCase().trim();
  var tabla = document.getElementById(tablaId);
  if (!tabla) return;

  var filas = tabla.querySelectorAll('tbody tr');
  filas.forEach(function (fila) {
    var contenido = fila.textContent.toLowerCase();
    fila.style.display = contenido.includes(texto) ? '' : 'none';
  });
}

function activarTab(el) {
  var padre = el.closest('.tabs');
  if (!padre) return;
  padre.querySelectorAll('.tab').forEach(function (t) {
    t.classList.remove('active');
  });
  el.classList.add('active');
  showToast('Filtro aplicado: ' + el.textContent.trim());
}

function toggleSidebar() {
  var sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

document.addEventListener('DOMContentLoaded', function () {
  var formLogin = document.getElementById('form-login');
  if (!formLogin) return;

  formLogin.addEventListener('submit', function (e) {
    var email    = document.getElementById('email');
    var password = document.getElementById('password');
    var errDiv   = document.getElementById('login-error-js');

    if (!email || !password) return;

    if (!email.value.trim() || !password.value.trim()) {
      e.preventDefault();
      if (errDiv) {
        errDiv.textContent = 'Por favor completa todos los campos.';
        errDiv.classList.add('show');
      }
    }
  });
});

window.addEventListener('scroll', function () {
  var navbar = document.getElementById('navbar');
  if (!navbar) return;
  if (window.scrollY > 40) navbar.classList.add('scrolled');
  else navbar.classList.remove('scrolled');
});

var landingObserver = new IntersectionObserver(function (entries) {
  entries.forEach(function (entry) {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });

function initLandingReveal() {
  document.querySelectorAll('.proj-card, .como-card, .value-card, .colab-chip').forEach(function (el) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(24px)';
    el.style.transition = 'opacity .5s ease, transform .5s ease';
    landingObserver.observe(el);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  initLandingReveal();
});

Object.assign(window, {
  openModal,
  closeModal,
  showToast,
  saveAndClose,
  filtrarTabla,
  activarTab,
  toggleSidebar
});
