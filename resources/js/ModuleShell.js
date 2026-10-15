import '../css/ModulosAdmi.css';
import './AppSistema.js';
import './Validaciones.js';

(function () {
  'use strict';

  var page = window.location.pathname.split('/').pop().toLowerCase();
  var items = [
    { label: 'Dashboard', file: 'dashboard.blade.php', group: 'Principal' },
    { label: 'Campañas', file: 'campanas.blade.php', group: 'Gestión' },
    { label: 'Donadores', file: 'donadores.blade.php', group: 'Gestión' },
    { label: 'Donaciones', file: 'donaciones.blade.php', group: 'Gestión' },
    { label: 'Beneficiarios', file: 'beneficiarios.blade.php', group: 'Gestión' },
    { label: 'Entregas', file: 'entregas.blade.php', group: 'Gestión' },
    { label: 'Usuarios', file: 'usuarios.blade.php', group: 'Gestión' },
    { label: 'Inventario', file: 'inventario.blade.php', group: 'Control' },
    { label: 'Voluntarios', file: 'voluntarios.blade.php', group: 'Control' },
    { label: 'Respaldos', file: 'respaldos.blade.php', group: 'Control' },
    { label: 'Gamificación', file: 'gamificacion.blade.php', group: 'Extras' },
    { label: 'Planning', file: 'planning.blade.php', group: 'Extras' },
    { label: 'Reportes', file: 'reportes.blade.php', group: 'Extras' },
    { label: 'Notificaciones', file: 'notificaciones.blade.php', group: 'Extras' }
  ];

  var activeFile = page.replace(/_(nuevo|editar)\.blade\.php$/, '.blade.php');
  if (!activeFile.endsWith('.blade.php')) {
    activeFile += '.blade.php';
  }
  var activeItem = items.find(function (item) { return item.file === activeFile; });
  var pageTitle = activeItem ? activeItem.label : 'Sistema Iskali';

  document.title = pageTitle + ' | Juventud Iskali';

  var viewport = document.createElement('meta');
  viewport.name = 'viewport';
  viewport.content = 'width=device-width, initial-scale=1.0';
  document.head.appendChild(viewport);

  var fontLink = document.createElement('link');
  fontLink.rel = 'stylesheet';
  fontLink.href = 'https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap';
  document.head.appendChild(fontLink);

  var pageTopbar = document.querySelector('.topbar');
  if (pageTopbar) {
    var menuButton = document.createElement('button');
    menuButton.className = 'mobile-menu-toggle';
    menuButton.type = 'button';
    menuButton.setAttribute('aria-label', 'Abrir navegación');
    menuButton.title = 'Abrir navegación';
    menuButton.textContent = '\u2630';
    menuButton.addEventListener('click', function () {
      var sidebar = document.querySelector('.sidebar');
      if (sidebar) sidebar.classList.toggle('open');
    });
    pageTopbar.insertBefore(menuButton, pageTopbar.firstChild);
  }

  document.querySelectorAll('a[href*="&accion="]').forEach(function (link) {
    var match = link.getAttribute('href').match(/^(.+?)\.blade\.php&accion=(nuevo|crear|editar)(?:&id=\d+)?$/);
    if (match) {
      link.href = match[1] + (match[2] === 'editar' ? '_editar' : '_nuevo') + '.blade.php';
    }
  });

  if (!document.getElementById('toast')) {
    var toast = document.createElement('div');
    toast.id = 'toast';
    toast.innerHTML = '<span id="toast-msg"></span>';
    document.body.appendChild(toast);
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    var action = form.getAttribute('action') || '';
    if (event.defaultPrevented) return;
    if (form.querySelector('input[name="_token"]')) return;

    event.preventDefault();
    if (action.includes('accion=borrar')) {
      var row = form.closest('tr');
      if (row) row.remove();
      if (typeof window.showToast === 'function') {
        window.showToast('Registro quitado de la vista. La maqueta no guarda cambios.');
      }
      return;
    }

    if (!form.hasAttribute('onsubmit') && typeof window.showToast === 'function') {
      window.showToast('Formulario validado. Los cambios no se guardan en esta maqueta.');
    }
  });
}());