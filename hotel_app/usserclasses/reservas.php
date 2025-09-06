<?php
// ==========================
// API AJAX - Procesamiento antes de cualquier salida HTML
// ==========================
$action = $_GET['action'] ?? '';

if ($action) {
    ob_start();
    require '../db/dbconeccion.php';
    ob_clean();
    header('Content-Type: application/json');

    try {
        if ($action === 'habitaciones') {
            $stmt = $pdo->query("SELECT habitacion_id, nombre, tipo, camas, precio, precio_camionero, precio_comun, estado 
                                 FROM habitaciones 
                                 ORDER BY nombre ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

       if ($action === 'reservas') {

    // 🔹 ELIMINAR AUTOMÁTICAMENTE LAS RESERVAS VENCIDAS
    $stmt = $pdo->prepare("UPDATE reservascreadas 
                           SET sw_eliminado = 1 
                           WHERE fecha_fin < CURDATE() 
                           AND sw_eliminado = 0");
    $stmt->execute();

    // 🔹 Cargar las reservas vigentes
    $stmt = $pdo->query("
        SELECT r.reserva_id AS id, h.nombre AS habitacion, r.huesped, r.fecha_inicio, r.fecha_fin, 
               r.camas, r.tipo_cliente, r.precio_total
        FROM reservascreadas r
        INNER JOIN habitaciones h ON h.habitacion_id = r.habitacion_id
        WHERE r.sw_eliminado = 0
        ORDER BY r.fecha_inicio ASC
    ");
    $reservas = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $reservas[] = [
            'id' => $r['id'],
            'title' => "Habitación {$r['habitacion']} - {$r['huesped']} ({$r['camas']} cama" . ($r['camas']>1?'s':'') . ")",
            'start' => $r['fecha_inicio'],
            'end' => $r['fecha_fin'],
            'extendedProps' => [
                'habitacion' => $r['habitacion'],
                'huesped' => $r['huesped'],
                'camas' => $r['camas'],
                'tipo_cliente' => $r['tipo_cliente'],
                'precio_total' => $r['precio_total'],
            ]
        ];
    }
    echo json_encode($reservas);
    exit;
}

        if ($action === 'guardar') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido');

            $habitacion_id = $data['habitacion_id'];
            $huesped = $data['huesped'];
            $camas = $data['camas'];
            $fecha_inicio = $data['fecha_inicio'];
            $fecha_fin = $data['fecha_fin'];
            $tipo_cliente = $data['tipo_cliente'] ?? 'Común';
            $reserva_id = $data['reserva_id'] ?? null;

            $fechaFinObj = new DateTime($fecha_fin);
            $fechaInicioObj = new DateTime($fecha_inicio);
            $ayer = new DateTime('yesterday');

            if ($fechaInicioObj < $ayer) {
                throw new Exception('No se pueden crear reservas en fechas pasadas.');
            }
            if ($fechaFinObj <= $fechaInicioObj) {
                throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio.');
            }

            // Lógica para detectar conflicto de fechas
            $sql_conflicto = "SELECT COUNT(*) as conflictos 
                              FROM reservascreadas 
                              WHERE habitacion_id = ? 
                                AND sw_eliminado = 0
                                AND NOT (? <= fecha_inicio OR ? > fecha_fin)";
            $parametros = [$habitacion_id, $fecha_fin, $fecha_inicio];
            
            if ($reserva_id) {
                $sql_conflicto .= " AND reserva_id != ?";
                $parametros[] = $reserva_id;
            }

            $stmt = $pdo->prepare($sql_conflicto);
            $stmt->execute($parametros);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado['conflictos'] > 0) {
                throw new Exception('La habitación ya está reservada en esas fechas.');
            }

            // Obtener precio de la habitación según tipo de cliente
            $stmt = $pdo->prepare("SELECT precio_camionero, precio_comun FROM habitaciones WHERE habitacion_id = ?");
            $stmt->execute([$habitacion_id]);
            $precios = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$precios) throw new Exception('Habitación no encontrada.');

            $dias = (new DateTime($fecha_inicio))->diff(new DateTime($fecha_fin))->days;
            $precio_unitario = ($tipo_cliente === 'Camionero') ? $precios['precio_camionero'] : $precios['precio_comun'];
            $precio_total = $dias * $precio_unitario;

            if ($reserva_id) {
                $stmt = $pdo->prepare("UPDATE reservascreadas 
                                       SET habitacion_id=?, huesped=?, camas=?, fecha_inicio=?, fecha_fin=?, tipo_cliente=?, precio_total=? 
                                       WHERE reserva_id=?");
                $stmt->execute([$habitacion_id, $huesped, $camas, $fecha_inicio, $fecha_fin, $tipo_cliente, $precio_total, $reserva_id]);
                echo json_encode(['status'=>'ok','message'=>'Reserva actualizada']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO reservascreadas (habitacion_id,huesped,camas,fecha_inicio,fecha_fin,tipo_cliente,precio_total) 
                                       VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$habitacion_id, $huesped, $camas, $fecha_inicio, $fecha_fin, $tipo_cliente, $precio_total]);
                echo json_encode(['status'=>'ok','message'=>'Reserva creada','id'=>$pdo->lastInsertId()]);
            }
            exit;
        }

        if ($action === 'eliminar') {
            $reserva_id = $_GET['reserva_id'] ?? null;
            if (!$reserva_id) throw new Exception('ID de reserva requerido');
            
            $stmt = $pdo->prepare("UPDATE reservascreadas SET sw_eliminado=1 WHERE reserva_id=?");
            $stmt->execute([$reserva_id]);
            echo json_encode(['status'=>'ok','message'=>'Reserva marcada como eliminada']);
            exit;
        }

        echo json_encode(['status'=>'error','message'=>'Acción no válida']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Reservas</title>
<link href="../calendar/dist/index.global.min.css" rel="stylesheet"/>
<script src="../calendar/dist/index.global.min.js"></script>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
<style>
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}
body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
.title-page { text-align: center; margin: 2rem 0 3rem; font-weight: bold; color: #5d34a8; }
#calendar { 
    background: #fff; 
    border-radius: 15px; 
    padding: 1rem; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
}
.back-button-container { display: flex; justify-content: center; margin: 2rem 0; }
.fc-day-past { background-color: #f8f9fa; }
.fc-day-other { visibility: hidden; }
.fc-prev-button { display: none; }
.fc-toolbar.fc-header-toolbar { flex-direction: column; align-items: center; }
.fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:first-child,
.fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child { margin-top: 15px; }
</style>
</head>
<body>
<div class="container py-4">
  <h2 class="title-page">📅 Gestión de Reservas</h2>
  <div id="calendar"></div>
</div>

<div class="back-button-container">
  <a onclick="window.history.back()" class="btn btn-secondary">⬅ Volver</a>
</div>

<div class="modal fade" id="reservaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formReserva">
        <div class="modal-header">
          <h5 class="modal-title">Nueva Reserva</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="fechaInicio">
          <input type="hidden" id="reservaId">
          <div class="mb-3">
            <label class="form-label">Habitación</label>
            <select id="habitacion" class="form-select" required>
              <option value="">Cargando habitaciones...</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre del huésped</label>
            <input type="text" id="huesped" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Cantidad de días</label>
            <input type="number" id="dias" class="form-control" min="1" value="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Camas a ocupar</label>
            <select id="camas" class="form-select" required>
              <option value="">Seleccione una habitación primero</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo de Cliente</label>
            <select id="tipoCliente" class="form-select" required>
              <option value="Común">Común</option>
              <option value="Camionero">Camionero</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar Reserva</button>
          <button type="button" id="btnEliminar" class="btn btn-danger" style="display: none;">Eliminar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../alerts/sweetalert2.all.min.js"></script>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
  const calendarEl = document.getElementById('calendar');
  const reservaModal = new bootstrap.Modal(document.getElementById('reservaModal'));
  const formReserva = document.getElementById('formReserva');
  const btnEliminar = document.getElementById('btnEliminar');
  const habitacionSelect = document.getElementById('habitacion');
  const camasSelect = document.getElementById('camas');
  const tipoClienteSelect = document.getElementById('tipoCliente');

  async function fetchWithErrorHandling(url, options = {}) {
    try {
      const response = await fetch(url, options);
      const text = await response.text();
      try { return JSON.parse(text); } 
      catch { throw new Error('Respuesta del servidor no válida'); }
    } catch (error) { Swal.fire('Error','Error de comunicación: '+error.message,'error'); throw error; }
  }

  // Cargar habitaciones
  try {
    const habitaciones = await fetchWithErrorHandling('?action=habitaciones');
    habitacionSelect.innerHTML = '<option value="">Seleccione...</option>';
    habitaciones.forEach(h => {
        habitacionSelect.innerHTML += `<option value="${h.nombre}" data-id="${h.habitacion_id}" data-camas="${h.camas}">${h.nombre} (${h.camas} cama${h.camas>1?'s':''}) - ${h.tipo}</option>`;
    });
  } catch { habitacionSelect.innerHTML = '<option value="">Error al cargar habitaciones</option>'; }

  let reservas = [];
  try { reservas = await fetchWithErrorHandling('?action=reservas'); } catch {}

  const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'es',
      selectable: true,
      editable: true,
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
      buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },
      events: reservas,
      dateClick(info) {
          const today = new Date();
          today.setHours(0,0,0,0);
          const selectedDate = new Date(info.date);
          selectedDate.setHours(0,0,0,0);
          
          if (selectedDate < today) { 
              Swal.fire('Error','No se pueden crear reservas en días pasados.','error'); 
              return; 
          }

          formReserva.reset();
          document.getElementById('reservaId').value = '';
          document.getElementById('fechaInicio').value = info.dateStr;
          camasSelect.innerHTML = '<option value="">Seleccione una habitación primero</option>';
          document.querySelector('.modal-title').textContent = 'Nueva Reserva';
          btnEliminar.style.display = 'none';
          reservaModal.show();
      },
      eventClick(info) {
          const e = info.event;
          document.getElementById('reservaId').value = e.id;
          document.getElementById('fechaInicio').value = e.startStr;
          habitacionSelect.value = e.extendedProps.habitacion;
          document.getElementById('huesped').value = e.extendedProps.huesped;
          document.getElementById('dias').value = Math.ceil((new Date(e.end)-new Date(e.start))/86400000);
          tipoClienteSelect.value = e.extendedProps.tipo_cliente || 'Común';

          const selectedOption = habitacionSelect.querySelector(`option[value="${e.extendedProps.habitacion}"]`);
          const totalCamas = selectedOption ? selectedOption.dataset.camas : 0;
          camasSelect.innerHTML = '';
          for(let i=1;i<=totalCamas;i++) camasSelect.innerHTML += `<option value="${i}">${i} cama${i>1?'s':''}</option>`;
          camasSelect.value = e.extendedProps.camas;

          document.querySelector('.modal-title').textContent = 'Editar/Eliminar Reserva';
          btnEliminar.style.display = 'block';
          reservaModal.show();
      }
  });
  calendar.render();

  async function recargarCalendario() {
    try {
      const nuevasReservas = await fetchWithErrorHandling('?action=reservas');
      calendar.removeAllEvents();
      calendar.addEventSource(nuevasReservas);
    } catch {}
  }

  habitacionSelect.addEventListener('change', function() {
      camasSelect.innerHTML = '';
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption && selectedOption.dataset.camas) {
          const totalCamas = selectedOption.dataset.camas;
          for(let i=1;i<=totalCamas;i++) camasSelect.innerHTML += `<option value="${i}">${i} cama${i>1?'s':''}</option>`;
      }
  });

  formReserva.addEventListener('submit', async function(e) {
      e.preventDefault();
      const habitacionOption = habitacionSelect.options[habitacionSelect.selectedIndex];
      if (!habitacionOption || !habitacionOption.dataset.id) { Swal.fire('Error', 'Seleccione una habitación válida', 'error'); return; }

      const datos = {
          reserva_id: document.getElementById('reservaId').value,
          habitacion_id: habitacionOption.dataset.id,
          huesped: document.getElementById('huesped').value,
          camas: document.getElementById('camas').value,
          fecha_inicio: document.getElementById('fechaInicio').value,
          fecha_fin: new Date(new Date(document.getElementById('fechaInicio').value).getTime() + (parseInt(document.getElementById('dias').value) * 86400000)).toISOString().split('T')[0],
          tipo_cliente: tipoClienteSelect.value
      };

      try {
          const result = await fetchWithErrorHandling('?action=guardar',{
              method:'POST',
              headers:{'Content-Type':'application/json'},
              body: JSON.stringify(datos)
          });
          if(result.status==='ok'){ Swal.fire('¡Éxito!', result.message, 'success'); reservaModal.hide(); await recargarCalendario(); }
          else Swal.fire('Error', result.message, 'error');
      } catch {}
  });

  btnEliminar.addEventListener('click', async function(){
      const id = document.getElementById('reservaId').value;
      Swal.fire({title:'¿Eliminar reserva?',showCancelButton:true,confirmButtonText:'Sí, eliminar'})
      .then(async(result)=>{
          if(result.isConfirmed){
              try{
                  const res = await fetchWithErrorHandling('?action=eliminar&reserva_id='+id);
                  if(res.status==='ok'){ Swal.fire('Eliminada!', res.message, 'success'); reservaModal.hide(); await recargarCalendario();}
                  else Swal.fire('Error', res.message, 'error');
              } catch {}
          }
      });
  });

});
</script>
</body>
</html>
