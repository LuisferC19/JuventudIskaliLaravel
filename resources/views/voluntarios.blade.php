@extends('layouts.app')
@section('title', 'Voluntarios | Juventud Iskali')
@section('page-title', 'Voluntarios')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- Tabla de voluntarios -->
      <div class="card">
        <div class="card-header">
          <h3>Voluntarios</h3>
          <div class="toolbar">
            <input type="text" class="search-input" placeholder="Buscar voluntario..." oninput="filtrarTabla(this,'tabla-voluntarios')">
            <select style="width:150px;">
              <option value="">Todas las disponibilidades</option>
              <option>Tiempo completo</option>
              <option>Lunes–viernes</option>
              <option>Fines de semana</option>
              <option>Bajo demanda</option>
            </select>
            <button class="btn btn-primary" onclick="openModal('modal-voluntario')">+ Nuevo voluntario</button>
          </div>
        </div>

        <table id="tabla-voluntarios">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Teléfono</th>
              <th>Zona asignada</th>
              <th>Disponibilidad</th>
              <th>Fecha ingreso</th>
              <th>Activo</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#V001</td>
              <td>Jessica R.</td>
              <td>222-500-0001</td>
              <td>San Marcos</td>
              <td><span class="badge badge-blue">Tiempo completo</span></td>
              <td>2025-06-01</td>
              <td><span class="badge badge-green">Sí</span></td>
              <td><button class="btn btn-secondary btn-sm" onclick="openModal('modal-asignacion')">Ver asignaciones</button></td>
            </tr>
            <tr>
              <td>#V002</td>
              <td>Carlos M.</td>
              <td>222-500-0002</td>
              <td>La Loma</td>
              <td><span class="badge badge-gray">Fines de semana</span></td>
              <td>2025-09-15</td>
              <td><span class="badge badge-green">Sí</span></td>
              <td><button class="btn btn-secondary btn-sm" onclick="openModal('modal-asignacion')">Ver asignaciones</button></td>
            </tr>
            <tr>
              <td>#V003</td>
              <td>Fernando R.</td>
              <td>222-500-0003</td>
              <td>El Rincón</td>
              <td><span class="badge badge-amber">Lunes–viernes</span></td>
              <td>2025-11-01</td>
              <td><span class="badge badge-green">Sí</span></td>
              <td><button class="btn btn-secondary btn-sm" onclick="openModal('modal-asignacion')">Ver asignaciones</button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Asignaciones de voluntarios -->
      <div class="card">
        <div class="card-header">
          <h3>Asignaciones de voluntarios</h3>
          <button class="btn btn-primary" onclick="openModal('modal-asignacion')">+ Nueva asignación</button>
        </div>

        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Voluntario</th>
              <th>Entrega</th>
              <th>Asignado por</th>
              <th>Fecha asignación</th>
              <th>Fecha compromiso</th>
              <th>Estado</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#A001</td>
              <td>Jessica R.</td>
              <td>#E002 — Medicamentos</td>
              <td>Eva Sánchez</td>
              <td>2026-03-18 08:00</td>
              <td>2026-03-20 09:00</td>
              <td><span class="badge badge-amber">Pendiente</span></td>
              <td>—</td>
            </tr>
            <tr>
              <td>#A002</td>
              <td>Jessica R.</td>
              <td>#E003 — Ropa 3 pzas</td>
              <td>Eva Sánchez</td>
              <td>2026-03-19 07:30</td>
              <td>2026-03-19 14:00</td>
              <td><span class="badge badge-blue">En camino</span></td>
              <td>Salió puntual</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Registro de Asistencia a Actividades -->
      <div class="card">
        <div class="card-header">
          <h3>Registro de Asistencia a Actividades</h3>
          <div class="toolbar">
            <select id="selector-actividad" onchange="cargarVoluntariosActividad(this.value)">
              <option value="">Seleccionar actividad...</option>
                                                <option value="1">
                    Entrega de víveres — San Martín 
                    (05/10/2026)
                  </option>
                                  <option value="2">
                    Jornada de salud — Tlaxcala 
                    (12/10/2026)
                  </option>
                                  <option value="3">
                    Recolecta de ropa de invierno 
                    (20/10/2026)
                  </option>
                                          </select>
            <button class="btn btn-primary" onclick="openModal('modal-qr-asistencia')">Generar QR de asistencia</button>
          </div>
        </div>

        <table id="tabla-asistencia">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Zona</th>
              <th>Estado</th>
              <th>Método</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="6" style="text-align:center;color:#999;">Selecciona una actividad para ver los voluntarios</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Modal: QR de Asistencia -->
<div id="modal-qr-asistencia" class="modal">
  <div class="modal-content" style="text-align:center;max-width:400px;">
    <div class="modal-header">
      <h3>Código QR de Asistencia</h3>
      <button type="button" class="modal-close" onclick="closeModal('modal-qr-asistencia')">✕</button>
    </div>
    <div class="modal-body">
      <div id="qr-container" style="margin:20px auto;"></div>
      <p style="margin-top:15px;font-size:12px;color:#666;">Token: <span id="qr-token" style="font-family:monospace;"></span></p>
      <p style="font-size:12px;color:#999;">Los voluntarios pueden escanear este código para registrar su asistencia.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-qr-asistencia')">Cerrar</button>
    </div>
  </div>
</div>

<script>
// Cargar voluntarios según actividad seleccionada
function cargarVoluntariosActividad(id_actividad) {
  if (!id_actividad) {
    document.getElementById('tabla-asistencia').querySelector('tbody').innerHTML = 
      '<tr><td colspan="6" style="text-align:center;color:#999;">Selecciona una actividad para ver los voluntarios</td></tr>';
    return;
  }

  fetch(`index.php?modulo=voluntarios&accion=obtener_asistencia&id_actividad=${id_actividad}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const tbody = document.getElementById('tabla-asistencia').querySelector('tbody');
        tbody.innerHTML = '';
        
        if (data.asistencia.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">No hay voluntarios asignados a esta actividad</td></tr>';
        } else {
          data.asistencia.forEach(asist => {
            const estado = asist.presente ? '<span class="badge badge-green">Presente</span>' : '<span class="badge badge-gray">Ausente</span>';
            const metodo = asist.metodo === 'qr' ? '<span class="badge badge-blue">QR</span>' : '<span class="badge badge-amber">Manual</span>';
            tbody.innerHTML += `
              <tr>
                <td>${asist.id_asistencia}</td>
                <td>${asist.nombre_voluntario}</td>
                <td>${asist.zona_asignada}</td>
                <td>${estado}</td>
                <td>${metodo}</td>
                <td>
                  ${!asist.presente ? `<button class="btn btn-secondary btn-sm" onclick="registrarAsistenciaManual(${asist.id_voluntario}, ${id_actividad})">Registrar</button>` : '—'}
                </td>
              </tr>
            `;
          });
        }
      } else {
        alert('Error al cargar asistencia: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al cargar datos');
    });
}

// Registrar asistencia manual
function registrarAsistenciaManual(id_voluntario, id_actividad) {
  const formData = new FormData();
  formData.append('id_voluntario', id_voluntario);
  formData.append('id_actividad', id_actividad);
  formData.append('presente', '1');
  formData.append('metodo', 'manual');

  fetch('index.php?modulo=voluntarios&accion=registrar_asistencia', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Asistencia registrada');
        cargarVoluntariosActividad(id_actividad); // Recargar tabla
      } else {
        alert('Error: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al registrar asistencia');
    });
}

// Generar QR de asistencia
function openModal(modalId) {
  if (modalId === 'modal-qr-asistencia') {
    const idActividad = document.getElementById('selector-actividad').value;
    if (!idActividad) {
      alert('Por favor selecciona una actividad');
      return;
    }

    fetch('index.php?modulo=voluntarios&accion=generar_qr', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id_actividad=${idActividad}`
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Limpiar contenedor QR
          document.getElementById('qr-container').innerHTML = '';
          document.getElementById('qr-token').textContent = data.token.substring(0, 16) + '...';

          // Generar código QR con qrcode.js
          new QRCode(document.getElementById('qr-container'), {
            text: data.token,
            width: 250,
            height: 250,
            colorDark: 'var(--teal)',
            colorLight: '#fff'
          });

          // Mostrar modal
          document.getElementById(modalId).style.display = 'flex';
        } else {
          alert('Error al generar QR: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error al generar QR');
      });
  } else {
    // Abrir otro modal (existente)
    document.getElementById(modalId).style.display = 'flex';
  }
}

// Cerrar modal
function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
  const modal = event.target.closest('.modal');
  if (modal && event.target === modal) {
    modal.style.display = 'none';
  }
});
</script>

<!-- Cargar librería QR Code (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

@endsection
