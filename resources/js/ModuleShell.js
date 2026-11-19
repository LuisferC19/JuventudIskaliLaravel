import '../css/ModulosAdmi.css';
import './AppSistema.js';
import './Validaciones.js';

(function () {
  'use strict';

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

  if (!document.getElementById('toast')) {
    var toast = document.createElement('div');
    toast.id = 'toast';
    toast.innerHTML = '<span id="toast-msg"></span>';
    document.body.appendChild(toast);
  }
}());