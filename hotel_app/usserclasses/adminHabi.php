<?php
// ==========================
// CONEXIÓN A BASE DE DATOS
// ==========================
// include('../header.php');
require '../db/dbconeccion.php';

// ==========================
// API SIMPLE AJAX
// ==========================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
  
    if ($_GET['action'] === 'list') {
        $stmt = $pdo->query("SELECT * FROM habitaciones ORDER BY habitacion_id ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['action'] === 'save') {
        $id              = $_POST['habitacion_id'] ?? null;
        $nombre          = $_POST['nombre'];
        $tipo            = $_POST['tipo'];
        $camas           = $_POST['camas'];
        $precio_camionero= $_POST['precio_camionero'];
        $precio_comun    = $_POST['precio_comun'];
        $estado          = $_POST['estado'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE habitaciones 
                SET nombre=?, tipo=?, camas=?, precio_camionero=?, precio_comun=?, estado=? 
                WHERE habitacion_id=?");
            $stmt->execute([$nombre, $tipo, $camas, $precio_camionero, $precio_comun, $estado, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO habitaciones(nombre, tipo, camas, precio_camionero, precio_comun, estado) 
                                   VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nombre, $tipo, $camas, $precio_camionero, $precio_comun, $estado]);
            $id = $pdo->lastInsertId();
        }
        echo json_encode(["success" => true, "id" => $id]);
        exit;
    }

    if ($_GET['action'] === 'delete') {
        $id = $_POST['habitacion_id'];
        $stmt = $pdo->prepare("DELETE FROM habitaciones WHERE habitacion_id=?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Administrar Habitaciones</title>

  <!-- Bootstrap -->
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="../icons/css/all.min.css" rel="stylesheet">

  <style>
    body { background: #f5f7fb; font-family: 'Inter', sans-serif; }
    .card-room {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      transition: transform .2s;
    }
    .card-room:hover { transform: translateY(-5px); }
    .badge { font-size: .8rem; }
    .card-room .price {
      font-weight: bold;
      color: #0d6efd;
      font-size: 1.1rem;
    }
    .actions button { margin-right: .25rem; }
    .back-button { text-align: center; margin: 2rem 0; }
  </style>
</head>
<body>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="fa-solid fa-bed me-2"></i> Habitaciones</h3>
    <button id="btnNewRoom" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#roomModal">
      <i class="fa fa-plus"></i> Nueva Habitación
    </button>
  </div>

  <!-- GRID DE HABITACIONES -->
  <div id="roomsGrid" class="row g-4"></div>

  <!-- BOTÓN VOLVER -->
  <div class="back-button">
    <button class="btn btn-secondary" onclick="window.history.back();">⬅ Volver</button>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="roomModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="roomForm">
        <div class="modal-header">
          <h5 class="modal-title">Nueva Habitación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="habitacion_id" name="habitacion_id">
          <div class="mb-2">
            <label>Nombre</label>
            <input type="text" class="form-control" name="nombre" id="nombre" required>
          </div>
          <div class="row">
            <div class="col mb-2">
              <label>Tipo</label>
              <select class="form-select" name="tipo" id="tipo">
                <option>Estándar</option>
                <option>Suite</option>
                <option>Familiar</option>
                <option>Cabaña</option>
              </select>
            </div>
            <div class="col mb-2">
              <label>Camas</label>
              <input type="number" class="form-control" name="camas" id="camas" min="1" required>
            </div>
          </div>
          <div class="row">
            <div class="col mb-2">
              <label>Precio Camionero</label>
              <input type="number" class="form-control" name="precio_camionero" id="precio_camionero" min="0" required>
            </div>
            <div class="col mb-2">
              <label>Precio Común</label>
              <input type="number" class="form-control" name="precio_comun" id="precio_comun" min="0" required>
            </div>
          </div>
          <div class="mb-2">
            <label>Estado</label>
            <select class="form-select" name="estado" id="estado">
              <option>Disponible</option>
              <option>Ocupada</option>
              <option>Mantenimiento</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// CARGAR HABITACIONES EN CARDS
function loadRooms() {
  fetch("?action=list")
    .then(r => r.json())
    .then(data => {
      const grid = document.getElementById("roomsGrid");
      grid.innerHTML = "";
      data.forEach(r => {
        grid.innerHTML += `
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-room h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title mb-0">${r.nombre}</h5>
                  <span class="badge ${r.estado=='Disponible'?'bg-success':(r.estado=='Ocupada'?'bg-danger':'bg-warning text-dark')}">
                    ${r.estado}
                  </span>
                </div>
                <p class="text-muted mb-1"><i class="fa-solid fa-tag me-1"></i> ${r.tipo}</p>
                <p class="text-muted mb-1"><i class="fa-solid fa-bed me-1"></i> ${r.camas} camas</p>
                <p class="price mb-1"><i class="fa-solid fa-truck me-1"></i> Camionero: $${Number(r.precio_camionero).toLocaleString('es-CO')}</p>
                <p class="price mb-3"><i class="fa-solid fa-user me-1"></i> Común: $${Number(r.precio_comun).toLocaleString('es-CO')}</p>
                
                <div class="mt-auto d-flex justify-content-end actions">
                  <button class="btn btn-sm btn-primary" 
                    onclick="editRoom(${r.habitacion_id},'${r.nombre}','${r.tipo}',${r.camas},${r.precio_camionero},${r.precio_comun},'${r.estado}')">
                    <i class="fa fa-pen"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="deleteRoom(${r.habitacion_id})"><i class="fa fa-trash"></i></button>
                </div>
              </div>
            </div>
          </div>`;
      });
    });
}

// GUARDAR
document.getElementById("roomForm").addEventListener("submit", e => {
  e.preventDefault();
  const formData = new FormData(e.target);
  fetch("?action=save", {method:"POST", body:formData})
    .then(r=>r.json())
    .then(()=> {
      bootstrap.Modal.getInstance(document.getElementById("roomModal")).hide();
      loadRooms();
    });
});

// EDITAR
function editRoom(id,nombre,tipo,camas,precio_camionero,precio_comun,estado) {
  document.getElementById("habitacion_id").value=id;
  document.getElementById("nombre").value=nombre;
  document.getElementById("tipo").value=tipo;
  document.getElementById("camas").value=camas;
  document.getElementById("precio_camionero").value=precio_camionero;
  document.getElementById("precio_comun").value=precio_comun;
  document.getElementById("estado").value=estado;
  new bootstrap.Modal(document.getElementById("roomModal")).show();
}

// ELIMINAR
function deleteRoom(id) {
  if (!confirm("¿Seguro que deseas eliminar esta habitación?")) return;
  const fd = new FormData();
  fd.append("habitacion_id", id);
  fetch("?action=delete", {method:"POST", body:fd})
    .then(r=>r.json())
    .then(()=> loadRooms());
}

loadRooms();
</script>
</body>
</html>
