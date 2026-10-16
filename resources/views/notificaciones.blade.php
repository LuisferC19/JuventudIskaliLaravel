@extends('layouts.app')
@section('title', 'Notificaciones | Juventud Iskali')
@section('page-title', 'Notificaciones')
@section('content')

<div style="display:flex;width:100%;">
  <div class="main">
    @include('partials.topbar')
    <div class="content">
      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">Total notificaciones</div>
          <div class="kpi-value">{{ $notificaciones->count() }}</div>
        </div>
        <div class="kpi amber">
          <div class="kpi-label">No leídas</div>
          <div class="kpi-value">{{ $noLeidas }}</div>
        </div>
        <div class="kpi blue">
          <div class="kpi-label">Leídas</div>
          <div class="kpi-value">{{ $notificaciones->count() - $noLeidas }}</div>
        </div>
      </div>
      <div class="kpi amber">
        <div class="kpi-label">No leídas</div>
        <div class="kpi-value">{{ $noLeidas }}</div>
      </div>
      <div class="kpi blue">
        <div class="kpi-label">Leídas</div>
        <div class="kpi-value">{{ $totalNotificaciones - $noLeidas }}</div>
      </div>
    </div>

      <div class="card">
        <div class="card-header">
          <h3>Mis Notificaciones</h3>
          @can('permiso', ['notificaciones', 'editar'])
          @if ($noLeidas > 0)
            <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
              @csrf
              <button class="btn btn-secondary" type="submit">Marcar todas como leídas</button>
            </form>
          @endif
          @endcan
        </div>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th style="width:70px;">Leída</th>
                <th>Tipo</th>
                <th>Asunto</th>
                <th>Mensaje</th>
                <th>Canal</th>
                <th>Fecha</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($notificaciones as $notificacion)
                <tr @if (!$notificacion->leida) style="background:rgba(10,175,160,.05);" @endif>
                  <td>
                    @if ($notificacion->leida)
                      <span style="font-size:11px;color:var(--muted);">Leída</span>
                    @else
                      <span class="badge badge-teal">Nueva</span>
                    @endif
                  </td>
                  <td><span class="badge badge-gray" style="text-transform:capitalize;">{{ $notificacion->tipo }}</span></td>
                  <td><strong>{{ $notificacion->asunto }}</strong></td>
                  <td style="max-width:300px;color:var(--muted);font-size:13px;">{{ $notificacion->mensaje }}</td>
                  <td><span style="font-size:11px;background:var(--border);padding:2px 6px;border-radius:4px;">{{ str_replace(',', ', ', $notificacion->canal) }}</span></td>
                  <td style="font-size:12px;color:var(--muted);white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($notificacion->fecha_envio)->format('d/m/Y H:i') }}</td>
                  <td>
                    @if (!$notificacion->leida)
                      @can('permiso', ['notificaciones', 'editar'])
                      <form method="POST" action="{{ route('notificaciones.leer', $notificacion->id_notificacion) }}">
                        @csrf
                        <button class="btn btn-xs btn-secondary" type="submit">Marcar leída</button>
                      </form>
                      @else
                        <span style="font-size:11px;color:var(--muted);">—</span>
                      @endcan
                    @else
                      <span style="font-size:11px;color:var(--muted);">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px;">No tienes notificaciones.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
