@php
  $notificacionesNoLeidas = \Illuminate\Support\Facades\DB::table('notificaciones')
      ->where('id_usuario', auth()->user()->id_usuario)
      ->whereIn('canal', ['web', 'web,app'])
      ->where('leida', false)
      ->count();
@endphp
<header class="topbar">
  <div class="topbar-title">@yield('page-title', 'Juventud Iskali')</div>
  <div class="topbar-actions">
    <a class="topbar-button" href="{{ route('dashboard') }}" aria-label="Inicio">Inicio</a>
    @can('permiso', ['notificaciones', 'ver'])
      <div style="position:relative;">
        <button class="topbar-button" id="notificaciones-toggle" type="button" aria-label="Notificaciones" aria-expanded="false">
          Notificaciones <span id="notificaciones-contador">{{ $notificacionesNoLeidas }}</span>
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
          const refresh = async () => {
            try {
              const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
              const data = await response.json();
              if (!response.ok) throw new Error(data.message || 'No se pudieron cargar las notificaciones.');
              counter.textContent = data.total_no_leidas;
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
    <span>{{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
      @csrf
      <button class="topbar-button topbar-logout" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 17l5-5-5-5M15 12H3"></path>
          <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path>
        </svg>
        <span>Salir</span>
      </button>
    </form>
  </div>
</header>
@if (session('success'))
  <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
