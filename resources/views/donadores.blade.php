@extends('layouts.app')
@section('title', 'Donadores | Juventud Iskali')
@section('page-title', 'Donadores')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- ===== Tarjetas de estadísticas ===== -->
      <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:var(--primary);">{{ $totalDonadores }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Donadores totales</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#4caf50;">{{ $donadoresActivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Donadores activos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#f44336;">{{ $donadoresInactivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Donadores inactivos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#2196f3;">{{ $donadoresFisicos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Personas físicas</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#ff9800;">{{ $donadoresMorales }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Personas morales</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#2196f3;">{{ $donadoresGrupos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Grupos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#607d8b;">{{ $donadoresAnonimos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Anónimos</div>
        </div>

      </div>

      <div class="card">
        <div class="card-header">
          <h3>Donadores</h3>
          <div class="toolbar">
            <input type="text" class="search-input" placeholder="Buscar donador..." oninput="filtrarTabla(this,'tabla-donadores')">
                          @can('permiso', ['donadores', 'crear'])
                            <a href="{{ route('donadores.create') }}" class="btn btn-primary">+ Nuevo donador</a>
                          @endcan
                      </div>
        </div>

                
        <table id="tabla-donadores">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Tipo</th>
              <th>Email</th>
              <th>Teléfono</th>
              <th>Puntos</th>
              <th>Nivel</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($donadores as $donador)
            @php
              $activo = in_array(strtolower((string) data_get($donador, 'activo')), ['1', 'si', 'sí', 'activo', 'true'], true);
              $nombre = ($donador->nombre_completo ?? null) ?: ($donador->razon_social ?? null) ?: trim((($donador->nombre ?? '') . ' ' . ($donador->apellido ?? '')));
              $tipo = match (data_get($donador, 'tipo_persona')) {
                'fisica' => 'Física',
                'moral' => 'Moral',
                'grupo' => 'Grupo',
                'anonimo' => 'Anónimo',
                'persona' => 'Persona',
                'organizacion' => 'Organización',
                default => 'Desconocido',
              };
            @endphp
            <tr>
              <td>{{ data_get($donador, 'id_donador', '—') }}</td>
              <td>{{ $nombre ?: '—' }}</td>
              <td>{{ $tipo }}</td>
              <td>{{ data_get($donador, 'email', data_get($donador, 'correo', '—')) }}</td>
              <td>{{ data_get($donador, 'telefono', '—') }}</td>
              <td>{{ data_get($donador, 'puntos_acumulados', '—') }}</td>
              <td>{{ data_get($donador, 'nivel', data_get($donador, 'nombre_nivel', '—')) }}</td>
              <td>{{ $activo ? 'Sí' : 'No' }}</td>
              <td style="white-space:nowrap;">
                @can('permiso', ['donadores', 'editar'])
                  <a class="btn btn-secondary btn-sm" href="{{ route('donadores.edit', data_get($donador, 'id_donador')) }}">Editar</a>
                @endcan
                @can('permiso', ['donadores', 'eliminar'])
                  <form method="POST" action="{{ route('donadores.destroy', data_get($donador, 'id_donador')) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este donador?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                  </form>
                @endcan
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="padding:16px;text-align:center;">No hay donadores registrados.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

@endsection
