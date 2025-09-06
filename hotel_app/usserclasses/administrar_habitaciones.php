<?php
require '../db/dbconeccion.php';

// ==========================
// API AJAX
// ==========================
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');

    $stmt = $pdo->query("
   SELECT h.habitacion_id,
       h.nombre AS numero,
       h.tipo, 
       h.camas AS capacidad,
       COALESCE(r.precio_total, h.precio) AS precio,
       h.estado,
       r.reserva_id,
       r.huesped,
       r.fecha_inicio,
       r.fecha_fin,
       r.camas AS camas_ocupadas,
       CASE 
           WHEN r.reserva_id IS NOT NULL AND CURDATE() BETWEEN r.fecha_inicio AND r.fecha_fin THEN 'Ocupada'
           WHEN r.reserva_id IS NOT NULL AND CURDATE() < r.fecha_inicio THEN 'Reservada'
           ELSE h.estado
       END AS estado_actual
FROM habitaciones h
LEFT JOIN (
    SELECT r1.*
    FROM reservascreadas r1
    INNER JOIN (
        SELECT habitacion_id, MIN(fecha_inicio) AS fecha_inicio_min
        FROM reservascreadas
        WHERE CURDATE() <= fecha_fin 
          AND sw_eliminado = 0   -- ✅ aquí va el filtro correcto
        GROUP BY habitacion_id
    ) r2 ON r1.habitacion_id = r2.habitacion_id AND r1.fecha_inicio = r2.fecha_inicio_min
    WHERE r1.sw_eliminado = 0   -- ✅ también acá por seguridad
) r ON h.habitacion_id = r.habitacion_id
ORDER BY h.habitacion_id ASC
");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Habitaciones de Hotel</title>
  <!-- Bootstrap local -->
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { background: #f4f6f9; }
      .habitacion-card {
          border: none; border-radius: 15px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          transition: transform 0.2s, box-shadow 0.2s;
          cursor: pointer;
      }
      .habitacion-card:hover {
          transform: translateY(-5px); box-shadow: 0 6px 16px rgba(0,0,0,0.15);
      }
      .estado-badge { font-size: 0.85rem; font-weight: bold; padding: 4px 10px; border-radius: 20px; }
      .estado-disponible { background: #d4edda; color: #155724; }
      .estado-ocupada { background: #f8d7da; color: #721c24; }
      .estado-reservada { background: #d1ecf1; color: #0c5460; }
      .estado-mantenimiento { background: #fff3cd; color: #856404; }
      .header-title {
          font-size: 2rem; font-weight: 700; color: #333;
          display: flex; align-items: center; gap: 12px;
      }
      .volver-btn {
          background: #0d6efd; color: #fff; border-radius: 30px;
          padding: 8px 16px; border: none; transition: 0.3s;
      }
      .volver-btn:hover { background: #0b5ed7; }
      .reserva-info {
          background: #f8f9fa;
          border-radius: 8px;
          padding: 15px;
          margin-top: 15px;
      }
      .no-reserva {
          color: #6c757d;
          font-style: italic;
      }
  </style>
</head>
<body>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="header-title">
          <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-7 7a.5.5 0 1 0 .708.708L2 8.207V15.5A1.5 1.5 0 0 0 3.5 17h9A1.5 1.5 0 0 0 14 15.5V8.207l.646.647a.5.5 0 0 0 .708-.708l-7-7z"/>
          </svg>
          Habitaciones del Hotel
      </h2>
      <button class="volver-btn" onclick="window.history.back()">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 0 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
          </svg>
          Volver
      </button>
  </div>

  <div class="row g-4" id="listaHabitaciones"></div>
</div>

<!-- MODAL DETALLES -->
<div class="modal fade" id="modalVerDetalles" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header bg-light">
            <h5 class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                    <path d="M3 2a1 1 0 0 0-1 1v11h1V3a1 1 0 0 1 1-1h9V1H3a2 2 0 0 0-2 2v12h2v1h10v-1h2V3h-2V2H3z"/>
                    <path d="M9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                Habitación <span id="roomNumber"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Información de la Habitación</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Estado:</strong>
                            <span id="roomStatus" class="badge"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Capacidad:</strong>
                            <span id="roomCapacity"></span> personas
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Tipo:</strong>
                            <span id="roomType"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Precio:</strong>
                            <span id="roomPrice" class="fw-bold text-success"></span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            <path fill-rule="evenodd" d="M14 14s-1-4-6-4-6 4-6 4 1 1 6 1 6-1 6-1z"/>
                        </svg>
                        Información de Reserva
                    </h6>
                    <div id="reservaInfo"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS local -->
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function loadRooms() {
    fetch("?action=list")
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById("listaHabitaciones");
            container.innerHTML = "";
            data.forEach(h => {
                let badgeClass = h.estado_actual === "Disponible" ? "estado-disponible" :
                                 h.estado_actual === "Ocupada" ? "estado-ocupada" : 
                                 h.estado_actual === "Reservada" ? "estado-reservada" : "estado-mantenimiento";

                container.innerHTML += `
                  <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card habitacion-card" data-bs-toggle="modal" data-bs-target="#modalVerDetalles"
                        onclick="showRoomDetails(${JSON.stringify(h).replace(/"/g, '&quot;')})">
                      <div class="card-body">
                        <h5 class="card-title d-flex align-items-center gap-2">
                          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M3 2a1 1 0 0 0-1 1v11h1V3a1 1 0 0 1 1-1h9V1H3a2 2 0 0 0-2 2v12h2v1h10v-1h2V3h-2V2H3z"/>
                              <path d="M9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                          </svg>
                          ${h.numero}
                        </h5>
                        <p class="card-text text-muted">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                              <path fill-rule="evenodd" d="M14 14s-1-4-6-4-6 4-6 4 1 1 6 1 6-1 6-1z"/>
                          </svg>
                          Capacidad: ${h.capacidad}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge estado-badge ${badgeClass}">${h.estado_actual}</span>
                            ${h.huesped ? `<small class="text-muted">${h.huesped}</small>` : ''}
                        </div>
                      </div>
                    </div>
                  </div>`;
            });
        })
        .catch(error => {
            console.error('Error al cargar habitaciones:', error);
            document.getElementById("listaHabitaciones").innerHTML = 
                '<div class="col-12"><div class="alert alert-danger">Error al cargar las habitaciones</div></div>';
        });
}

function showRoomDetails(habitacion) {
    document.getElementById("roomNumber").innerText = habitacion.numero;
    document.getElementById("roomCapacity").innerText = habitacion.capacidad;
    document.getElementById("roomType").innerText = habitacion.tipo;
    document.getElementById("roomPrice").innerText = `$${Number(habitacion.precio).toLocaleString()}`;

    const statusElement = document.getElementById("roomStatus");
    statusElement.innerText = habitacion.estado_actual;
    statusElement.className = "badge ";
    
    if (habitacion.estado_actual === "Disponible") statusElement.className += "estado-disponible";
    else if (habitacion.estado_actual === "Ocupada") statusElement.className += "estado-ocupada";
    else if (habitacion.estado_actual === "Reservada") statusElement.className += "estado-reservada";
    else statusElement.className += "estado-mantenimiento";

    const reservaInfoElement = document.getElementById("reservaInfo");
    
    if (habitacion.huesped && habitacion.fecha_inicio && habitacion.fecha_fin) {
        const fechaInicio = new Date(habitacion.fecha_inicio + 'T00:00:00');
        const fechaFin = new Date(habitacion.fecha_fin + 'T00:00:00');
        const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
        
        const opcionesFecha = { year: 'numeric', month: 'long', day: 'numeric' };
        const fechaInicioStr = fechaInicio.toLocaleDateString('es-ES', opcionesFecha);
        const fechaFinStr = fechaFin.toLocaleDateString('es-ES', opcionesFecha);
        const diasEstadia = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24));
        
        let tipoReserva = '';
        if (fechaInicio > hoy) tipoReserva = '<span class="badge bg-info mb-2">Reserva Futura</span>';
        else if (fechaInicio <= hoy && fechaFin >= hoy) tipoReserva = '<span class="badge bg-success mb-2">Huésped Actual</span>';
        
        reservaInfoElement.innerHTML = `
            <div class="reserva-info">
                ${tipoReserva}
                <div class="mb-2"><strong>Huésped:</strong><br><span class="text-primary">${habitacion.huesped}</span></div>
                <div class="mb-2"><strong>Check-in:</strong><br><small>${fechaInicioStr}</small></div>
                <div class="mb-2"><strong>Check-out:</strong><br><small>${fechaFinStr}</small></div>
                <div class="mb-2"><strong>Estadía:</strong><br><small>${diasEstadia} día${diasEstadia !== 1 ? 's' : ''}</small></div>
                <div class="mb-0"><strong>Camas ocupadas:</strong><br><small>${habitacion.camas_ocupadas} de ${habitacion.capacidad}</small></div>
            </div>`;
    } else {
        reservaInfoElement.innerHTML = `
            <div class="no-reserva text-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16" class="text-muted mb-2">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                <p class="mb-0">No hay reservas activas</p>
                <small>Esta habitación está disponible para nuevas reservas</small>
            </div>`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadRooms();
});
</script>
</body>
</html>
