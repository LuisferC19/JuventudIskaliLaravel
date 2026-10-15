@extends('layouts.app')
@section('title', 'Beneficiarios | Juventud Iskali')
@section('page-title', 'Beneficiarios')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- Tarjetas de estadísticas -->
      <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:var(--primary);">{{ $totalBeneficiarios }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Beneficiarios totales</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#4caf50;">{{ $beneficiariosActivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Beneficiarios activos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#f44336;">{{ $beneficiariosInactivos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Beneficiarios inactivos</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#3f51b5;">{{ $beneficiariosFisicos }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Personas físicas</div>
        </div>

        <div class="card" style="flex:1;min-width:150px;padding:20px;text-align:center;">
          <div style="font-size:32px;font-weight:700;color:#ff9800;">{{ $beneficiariosMorales }}</div>
          <div style="font-size:13px;color:var(--muted);margin-top:4px;">Personas morales</div>
        </div>

      </div>

      <!-- Tabla de beneficiarios -->
      <div class="card">
        <div class="card-header">
          <h3>Beneficiarios</h3>
          <div class="toolbar">
            <input type="text" class="search-input" placeholder="Buscar beneficiario..." oninput="filtrarTabla(this,'tabla-beneficiarios')">
                          @can('permiso', ['beneficiarios', 'crear'])
                            <a href="{{ route('beneficiarios.create') }}" class="btn btn-primary">+ Registrar</a>
                          @endcan
                      </div>
        </div>

        
        <div style="overflow-x:auto;">
          <table id="tabla-beneficiarios" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Tipo</th>
                <th>Edad</th>
                <th>Comunidad</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Registrado por</th>
                <th>Creado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            @forelse ($beneficiarios as $beneficiario)
              @php
                $estado = data_get($beneficiario, 'activo', data_get($beneficiario, 'estado'));
                $activo = in_array(strtolower((string) $estado), ['1', 'si', 'sí', 'activo', 'true'], true);
                $nombre = ($beneficiario->nombre_completo ?? null) ?: ($beneficiario->razon_social ?? null) ?: trim((($beneficiario->nombre ?? '') . ' ' . ($beneficiario->apellido ?? '')));
                $creado = data_get($beneficiario, 'created_at');
              @endphp
              <tr style="border-bottom:1px solid var(--border,#eee);">
                <td style="padding:12px;">#{{ data_get($beneficiario, 'id_beneficiario', '—') }}</td>
                <td style="padding:12px;">{{ $nombre ?: '—' }}</td>
                <td style="padding:12px;">{{ data_get($beneficiario, 'tipo_persona', '—') }}</td>
                <td style="padding:12px;">{{ data_get($beneficiario, 'edad') ?? '—' }}</td>
                <td style="padding:12px;">{{ data_get($beneficiario, 'comunidad', data_get($beneficiario, 'nombre_comunidad', '—')) }}</td>
                <td style="padding:12px;">{{ data_get($beneficiario, 'telefono', '—') }}</td>
                <td style="padding:12px;">
                  <span class="badge {{ $activo ? 'badge-green' : 'badge-red' }}">{{ $activo ? 'Activo' : 'Inactivo' }}</span>
                </td>
                <td style="padding:12px;">{{ data_get($beneficiario, 'registrado_por', data_get($beneficiario, 'nombre_registrador', '—')) }}</td>
                <td style="padding:12px;">{{ $creado ? \Illuminate\Support\Carbon::parse($creado)->format('d/m/Y') : '—' }}</td>
                <td style="padding:12px;white-space:nowrap;">
                  @can('permiso', ['beneficiarios', 'editar'])
                    <a class="btn btn-secondary btn-sm" href="{{ route('beneficiarios.edit', data_get($beneficiario, 'id_beneficiario')) }}">Editar</a>
                  @endcan
                  @can('permiso', ['beneficiarios', 'eliminar'])
                    <form method="POST" action="{{ route('beneficiarios.destroy', data_get($beneficiario, 'id_beneficiario')) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este beneficiario?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-secondary btn-sm" type="submit">Eliminar</button>
                    </form>
                  @endcan
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" style="padding:16px;text-align:center;">No hay beneficiarios registrados.</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection
