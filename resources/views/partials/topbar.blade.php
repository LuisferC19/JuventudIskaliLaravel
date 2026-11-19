<header class="topbar">
  <div class="topbar-title">@yield('page-title', 'Juventud Iskali')</div>
  <div class="topbar-actions">
    <a class="topbar-button" href="{{ route('dashboard') }}" aria-label="Inicio">
      <img src="{{ asset('iconos/Dashboard.png') }}" alt="" style="width:16px;height:16px;">
      Inicio
    </a>
    <span style="font-size:11px;color:var(--muted);">{{ now()->locale('es')->isoFormat('ddd DD MMM YYYY') }}</span>
    <span aria-hidden="true" style="width:1px;height:16px;background:var(--border);"></span>
    @can('permiso', ['notificaciones', 'ver'])
      <div style="position:relative;">
        <button class="topbar-button" id="notificaciones-toggle" type="button" aria-label="Notificaciones" aria-expanded="false">
          <img src="{{ asset('iconos/notificacion.png') }}" alt="" style="width:16px;height:16px;">
          <span id="notificaciones-contador" @if ($notificacionesNoLeidas === 0) hidden @endif style="position:absolute;top:-3px;right:-3px;min-width:18px;height:18px;padding:0 4px;box-sizing:border-box;background:var(--danger);border-radius:50%;border:1.5px solid #fff;display:{{ $notificacionesNoLeidas === 0 ? 'none' : 'flex' }};align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:700;">{{ min($notificacionesNoLeidas, 9) }}</span>
        </button>
        <div id="notificaciones-dropdown" hidden style="position:absolute;right:0;top:calc(100% + 8px);z-index:1000;width:min(360px,90vw);max-height:420px;overflow:auto;background:#fff;border:1px solid var(--border,#d0ecea);border-radius:10px;box-shadow:0 12px 32px rgba(10,30,28,.16);padding:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
            <strong>Notificaciones recientes</strong>
            @can('permiso', ['notificaciones', 'editar'])
              <button type="button" id="notificaciones-leer-todas" class="btn btn-secondary btn-sm">Marcar todas leídas</button>
            @endcan
          </div>
          <div id="notificaciones-lista" style="font-size:12px;color:var(--muted);">Cargando...</div>
          <a href="{{ route('notificaciones.index') }}" style="display:block;margin-top:10px;">Ver todas</a>
        </div>
      </div>
      <script>
        (() => {
          const toggle = document.getElementById('notificaciones-toggle');
          const dropdown = document.getElementById('notificaciones-dropdown');
          const list = document.getElementById('notificaciones-lista');
          const counter = document.getElementById('notificaciones-contador');
          const csrf = document.querySelector('meta[name="csrf-token"]').content;
          const readUrl = @json(route('notificaciones.leer', ['notificacion' => '__ID__']));
          const endpoint = @json(route('notificaciones.recientes'));

          const renderError = (message) => {
            list.textContent = message;
          };
          const closeDropdown = () => {
            dropdown.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
          };
          const refresh = async () => {
            try {
              const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
              const data = await response.json();
              if (!response.ok) throw new Error(data.message || 'No se pudieron cargar las notificaciones.');
              const totalNoLeidas = Number(data.total_no_leidas) || 0;
              counter.textContent = String(Math.min(totalNoLeidas, 9));
              counter.hidden = totalNoLeidas === 0;
              counter.style.display = totalNoLeidas === 0 ? 'none' : 'flex';
              list.replaceChildren();
              if (data.notificaciones.length === 0) {
                list.textContent = 'No tienes notificaciones pendientes.';
                return;
              }
              data.notificaciones.forEach((notificacion) => {
                const item = document.createElement('div');
                item.style.cssText = 'padding:9px 0;border-bottom:1px solid var(--border,#d0ecea)';
                const title = document.createElement('strong');
                title.textContent = notificacion.asunto;
                const message = document.createElement('div');
                message.textContent = notificacion.mensaje;
                message.style.cssText = 'margin-top:4px;max-height:3.5em;overflow:hidden';
                item.append(title, message);
                @can('permiso', ['notificaciones', 'editar'])
                  const mark = document.createElement('button');
                  mark.type = 'button';
                  mark.className = 'btn btn-secondary btn-sm';
                  mark.textContent = 'Marcar leída';
                  mark.addEventListener('click', async () => {
                    try {
                      const result = await fetch(readUrl.replace('__ID__', encodeURIComponent(notificacion.id_notificacion)), {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
                      });
                      const payload = await result.json();
                      if (!result.ok) throw new Error(payload.message || 'No se pudo marcar como leída.');
                      await refresh();
                    } catch (error) {
                      renderError(error.message);
                    }
                  });
                  item.append(mark);
                @endcan
                list.append(item);
              });
            } catch (error) {
              renderError(error.message);
            }
          };

          toggle.addEventListener('click', async () => {
            const opening = dropdown.hidden;
            dropdown.hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));
            if (opening) await refresh();
          });
          document.addEventListener('click', (event) => {
            if (!toggle.contains(event.target) && !dropdown.contains(event.target)) closeDropdown();
          });
          const markAll = document.getElementById('notificaciones-leer-todas');
          if (markAll) {
            markAll.addEventListener('click', async () => {
              try {
                const response = await fetch(@json(route('notificaciones.leer-todas')), {
                  method: 'POST',
                  headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No se pudieron marcar todas como leídas.');
                await refresh();
              } catch (error) {
                renderError(error.message);
              }
            });
          }
        })();
      </script>
    @endcan
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
      @csrf
      <button class="topbar-button topbar-logout" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
        <img src="{{ asset('iconos/cerrar_sesion_puerta.png') }}" alt="" style="width:16px;height:16px;">
        <span>Salir</span>
      </button>
    </form>
    <div style="width:28px;height:28px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:600;" title="{{ $nombreCompleto }}">
      {{ $iniciales }}
    </div>
  </div>
</header>
@if (session('success'))
  <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
