<?php
// ==========================
// Conexión a la BD
// ==========================
require '../db/dbconeccion.php';

// ==========================
// API AJAX
// ==========================
$action = $_GET['action'] ?? '';

if ($action === 'despachar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $insumo_id = $_POST['insumo_id'];
        $cantidad = (int) $_POST['cantidad'];
        $observacion = $_POST['observacion'] ?? '';
        $bodega_origen = (int) $_POST['bodega_origen'];
        $despachado_por = "admin"; // 👈 Ajusta según tu login

        // 1. Verificar que exista y que haya stock en la bodega de origen
        $stmt = $pdo->prepare("SELECT id, nombre, cantidad, valor_venta, sw_bodega FROM productos WHERE id = :id AND inactivo = 0 AND sw_bodega = :bodega_origen");
        $stmt->execute([':id' => $insumo_id, ':bodega_origen' => $bodega_origen]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado o inactivo en la bodega seleccionada.']);
            exit;
        }

        if ($producto['cantidad'] < $cantidad) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente en la bodega de origen.']);
            exit;
        }

        // 2. Restar del stock de la bodega de origen
        $stmt = $pdo->prepare("UPDATE productos 
                               SET cantidad = cantidad - :cantidad 
                               WHERE id = :id AND sw_bodega = :bodega_origen");
        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id' => $producto['id'],
            ':bodega_origen' => $bodega_origen
        ]);

        // 3. Registrar en despachos
        $total = $cantidad * $producto['valor_venta'];

        $stmt = $pdo->prepare("INSERT INTO despachos (producto_id, cantidad, valor_unitario, total, observacion, despachado_por, sw_bodega) 
                               VALUES (:producto_id, :cantidad, :valor_unitario, :total, :observacion, :despachado_por, :sw_bodega)");
        $stmt->execute([
            ':producto_id'      => $producto['id'],
            ':cantidad'         => $cantidad,
            ':valor_unitario'   => $producto['valor_venta'],
            ':total'            => $total,
            ':observacion'      => $observacion,
            ':despachado_por'   => $despachado_por,
            ':sw_bodega'        => $bodega_origen // Guarda la bodega de donde se despachó
        ]);

        echo json_encode(['success' => true, 'message' => 'Producto despachado con éxito desde la Bodega ' . $bodega_origen]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==========================
// Traer productos para el select (solo activos de la bodega específica)
// ==========================
if ($action === 'getProductos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $bodega_id = $_GET['bodega_id'] ?? '1';
    $stmt = $pdo->prepare("SELECT id, nombre, valor_venta, cantidad FROM productos WHERE inactivo = 0 AND sw_bodega = :bodega_id ORDER BY nombre ASC");
    $stmt->execute([':bodega_id' => $bodega_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($productos);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <title>Cafetería</title>
 <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
 <link rel="stylesheet" href="../bootstrap/icons/bootstrap-icons.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 <link rel="stylesheet" href="../style/style.css">
 <link rel="stylesheet" href="../style/all.min.css">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">

 <link rel="stylesheet" href="../alerts/sweetalert2.min.css">
 <script src="../alerts/sweetalert2.all.min.js"></script>
 
 <style>
   body, html {
   margin: 0;
   padding: 0;
 }
 main.container {
   padding-top: 0 !important;
 }
 .bg-pagina {background-color:#f0f2f5;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;min-height:100vh;}
 .header-dashboard {background:linear-gradient(90deg,#007bff,#0056b3);color:white;padding:2rem 1rem;text-align:center;border-radius:0 0 20px 20px;box-shadow:0 4px 12px rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:center;}
 .header-dashboard h1 {font-size:2rem;font-weight:bold;margin:0;}
 .modulo-card {background-color:#ffffff;border-radius:16px;padding:2rem 1rem;margin:1rem;text-align:center;transition:transform .3s ease,box-shadow .3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.1);cursor:pointer;min-width:200px;max-width:250px;}
 .modulo-card:hover {transform:translateY(-8px);box-shadow:0 10px 25px rgba(0,0,0,0.15);}
 .modulo-card i {color:#007bff;font-size:3rem;margin-bottom:1rem;}
 .modulo-card h3 {font-size:1.2rem;font-weight:600;color:#495057;}
 .grid-modulos {display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;justify-items:center;margin-top:2rem;}
 .btn-volver-inicio { 
     position: fixed; 
     bottom: 20px; 
     right: 20px; 
     border-radius: 50px; 
     padding: 0.75rem 1.5rem; 
     font-size: 1rem; 
     z-index: 1050;
     background-color: #007bff;
     color: white;
     border: none;
 }
 .btn-volver-inicio:hover {
     background-color: #0056b3;
     color: white;
 }
 .bodega-icon-btn {
    border: 2px solid #ccc;
    border-radius: 8px;
    transition: all 0.3s ease;
 }
 .bodega-icon-btn.active {
    border-color: #007bff;
    background-color: #eaf3ff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
 }
 </style>
</head>
<body class="bg-pagina">

<header class="header-dashboard">
  <h1><i class="fas fa-coffee me-2"></i>Gestión de Cafetería</h1>
</header>

<main class="container py-5">
  <div class="grid-modulos">
   <div class="modulo-card" data-bs-toggle="modal" data-bs-target="#addProductosModal">
    <i class="fas fa-plus-circle"></i>
    <h3>Despachar productos</h3>
   </div>
   <div class="modulo-card" onclick="window.location.href='stock.php'">
    <i class="fas fa-boxes"></i>
    <h3>Cafeteria 1 stock</h3>
   </div>
   <div class="modulo-card" onclick="window.location.href='stock2.php'">
    <i class="fas fa-boxes"></i>
    <h3>Cafeteria 2 stock</h3>
   </div>
   <div class="modulo-card" onclick="window.location.href='reportes.php'">
    <i class="fas fa-chart-line"></i>
    <h3>Reportes</h3>
   </div>
  </div>
</main>

<div class="modal fade" id="addProductosModal" tabindex="-1" aria-labelledby="addProductosModalLabel" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title" id="addProductosModalLabel">Despachar Productos</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">
    <form id="formDespacho">
     <div class="row g-3">
      <div class="col-12 text-center mb-3">
        <label class="form-label fw-bold">Selecciona la Bodega de Origen</label>
        <div class="d-flex justify-content-center gap-3 mt-2">
            <button type="button" class="btn bodega-icon-btn active" data-bodega-id="1">
                <i class="fa-solid fa-warehouse fa-2x d-block mb-1"></i> Bodega 1
            </button>
            <button type="button" class="btn bodega-icon-btn" data-bodega-id="2">
                <i class="fa-solid fa-warehouse fa-2x d-block mb-1"></i> Bodega 2
            </button>
        </div>
        <input type="hidden" id="bodega_origen" name="bodega_origen" value="1">
      </div>

      <div class="col-12">
       <label for="insumo_id" class="form-label fw-bold">Seleccionar Insumo</label>
       <select class="form-select shadow-sm rounded-3" id="insumo_id" name="insumo_id" onchange="actualizarInfoProducto()">
        <option value="">-- Selecciona un insumo --</option>
       </select>
      </div>

      <div class="col-6">
       <label class="form-label fw-bold">Stock Disponible</label>
       <input type="text" id="stockDisponible" class="form-control shadow-sm rounded-3" readonly>
      </div>

      <div class="col-6">
       <label class="form-label fw-bold">Valor Venta (COP)</label>
       <input type="text" id="precioProducto" class="form-control shadow-sm rounded-3" readonly>
      </div>

      <div class="col-12 col-md-6">
       <label for="cantidad" class="form-label fw-bold">Cantidad</label>
       <input type="number" class="form-control shadow-sm rounded-3" id="cantidad" name="cantidad" min="1" required>
      </div>
      <div class="col-12 col-md-6">
       <label for="observacion" class="form-label fw-bold">Observación</label>
       <input type="text" class="form-control shadow-sm rounded-3" id="observacion" name="observacion" placeholder="Ej: Habitacion 101">
      </div>
     </div>
    </form>
   </div>
   <div class="modal-footer d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
     <i class="bi bi-x-circle"></i> Cerrar
    </button>
    <button type="button" class="btn btn-primary rounded-pill px-4" onclick="despacharInsumo()">
     <i class="bi bi-box-arrow-up"></i> Despachar
    </button>
   </div>
  </div>
 </div>
</div>

<a href="../index.php" class="btn btn-primary btn-volver-inicio">
    <i class="bi bi-arrow-left"></i> Volver al inicio
</a>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
const insumoSelect = document.getElementById("insumo_id");
const stockInput = document.getElementById("stockDisponible");
const precioInput = document.getElementById("precioProducto");
const bodegaOrigenInput = document.getElementById("bodega_origen");
const bodegaBtns = document.querySelectorAll(".bodega-icon-btn");
const modalEl = document.getElementById("addProductosModal");

function actualizarInfoProducto() {
    const selected = insumoSelect.options[insumoSelect.selectedIndex];
    if (selected.value === "") {
        stockInput.value = "";
        precioInput.value = "";
        return;
    }
    const stock = selected.dataset.stock ? parseInt(selected.dataset.stock) : "";
    const precio = selected.dataset.precio ? parseInt(selected.dataset.precio) : "";

    stockInput.value = stock;
    precioInput.value = precio ? "$ " + precio.toLocaleString("es-CO") : "";
}

function cargarProductos(bodegaId) {
    fetch(`?action=getProductos&bodega_id=${bodegaId}`)
        .then(response => response.json())
        .then(productos => {
            insumoSelect.innerHTML = '<option value="">-- Selecciona un insumo --</option>';
            productos.forEach(p => {
                const option = document.createElement("option");
                option.value = p.id;
                option.textContent = p.nombre;
                option.dataset.precio = p.valor_venta;
                option.dataset.stock = p.cantidad;
                insumoSelect.appendChild(option);
            });
            actualizarInfoProducto();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire("Error", "No se pudieron cargar los productos.", "error");
        });
}

function despacharInsumo() {
    const insumoId = insumoSelect.value;
    const cantidad = document.getElementById("cantidad").value;
    const observacion = document.getElementById("observacion").value;
    const bodegaOrigen = bodegaOrigenInput.value;

    if (insumoId === "" || cantidad <= 0) {
        Swal.fire({
            icon: "warning",
            title: "Atención",
            text: "Selecciona un insumo y define una cantidad válida."
        });
        return;
    }

    const formData = new FormData();
    formData.append("insumo_id", insumoId);
    formData.append("cantidad", cantidad);
    formData.append("observacion", observacion);
    formData.append("bodega_origen", bodegaOrigen);

    fetch("?action=despachar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(r => {
        if (r.success) {
            Swal.fire({
                icon: "success",
                title: "Éxito",
                text: r.message,
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // Recargar productos de la bodega actual después del despacho
                cargarProductos(bodegaOrigen);
                // Si la página se recarga, asegúrate de que el estado de los botones se mantenga
                location.reload(); 
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: r.message
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: "error",
            title: "Error inesperado",
            text: "Ha ocurrido un error en la comunicación con el servidor."
        });
    });
}

// Event listeners para los botones de bodega
bodegaBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        bodegaBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const bodegaId = btn.dataset.bodegaId;
        bodegaOrigenInput.value = bodegaId;
        cargarProductos(bodegaId);
    });
});

// Cargar productos de la bodega 1 al abrir el modal
modalEl.addEventListener('show.bs.modal', () => {
    document.getElementById("formDespacho").reset();
    bodegaBtns.forEach(b => b.classList.remove('active'));
    document.querySelector('[data-bodega-id="1"]').classList.add('active');
    bodegaOrigenInput.value = '1';
    cargarProductos(1);
});
</script>
</body>
</html>