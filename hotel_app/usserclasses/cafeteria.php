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
        $insumo = $_POST['insumo'];
        $cantidad = (int) $_POST['cantidad'];
        $observacion = $_POST['observacion'] ?? '';
        $despachado_por = "admin"; // 👈 Ajusta según tu login

        // 1. Verificar que exista y que haya stock
        $stmt = $pdo->prepare("SELECT id, nombre, cantidad, valor_venta FROM productos WHERE nombre = :nombre AND inactivo = 0");
        $stmt->execute([':nombre' => $insumo]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado o inactivo']);
            exit;
        }

        if ($producto['cantidad'] < $cantidad) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
            exit;
        }

        // 2. Restar del stock
        $stmt = $pdo->prepare("UPDATE productos 
                               SET cantidad = cantidad - :cantidad 
                               WHERE id = :id");
        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id' => $producto['id']
        ]);

        // 3. Registrar en despachos (ahora guardamos también el valor_venta y total)
        $total = $cantidad * $producto['valor_venta'];

        $stmt = $pdo->prepare("INSERT INTO despachos (producto_id, cantidad, valor_unitario, total, observacion, despachado_por) 
                               VALUES (:producto_id, :cantidad, :valor_unitario, :total, :observacion, :despachado_por)");
        $stmt->execute([
            ':producto_id'    => $producto['id'],
            ':cantidad'       => $cantidad,
            ':valor_unitario' => $producto['valor_venta'],
            ':total'          => $total,
            ':observacion'    => $observacion,
            ':despachado_por' => $despachado_por
        ]);

        echo json_encode(['success' => true, 'message' => 'Producto despachado con éxito']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==========================
// Traer productos para el select (solo activos)
// ==========================
$stmt = $pdo->query("SELECT id, nombre, valor_venta, cantidad, inactivo FROM productos WHERE inactivo = 0 ORDER BY nombre ASC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <title>Cafetería</title>
 <!-- Bootstrap local -->
 <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
 <link rel="stylesheet" href="../bootstrap/icons/bootstrap-icons.css">
 <!-- Estilos propios -->
 <link rel="stylesheet" href="../style/style.css">
 <link rel="stylesheet" href="../style/all.min.css">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">

 <!-- SweetAlert2 local -->
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
    <h3>Administrar Stock</h3>
   </div>
   <div class="modulo-card" onclick="window.location.href='reportes.php'">
    <i class="fas fa-chart-line"></i>
    <h3>Reportes</h3>
   </div>
  </div>
</main>

<!-- Modal -->
<div class="modal fade" id="addProductosModal" tabindex="-1" aria-labelledby="addProductosModalLabel" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title" id="addProductosModalLabel">Adicionar Productos</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">
    <form id="formDespacho">
     <div class="row g-3">
      <div class="col-12">
       <label for="insumo" class="form-label fw-bold">Seleccionar insumo</label>
       <select class="form-select shadow-sm rounded-3" id="insumo" name="insumo" onchange="actualizarInfoProducto()">
        <option value="">-- Selecciona un insumo --</option>
        <?php foreach($productos as $p): ?>
          <option value="<?= htmlspecialchars($p['nombre']) ?>" 
                  data-precio="<?= intval($p['valor_venta']) ?>" 
                  data-stock="<?= intval($p['cantidad']) ?>">
            <?= htmlspecialchars($p['nombre']) ?>
          </option>
        <?php endforeach; ?>
       </select>
      </div>

      <div class="col-6">
       <label class="form-label fw-bold">Stock disponible</label>
       <input type="text" id="stockDisponible" class="form-control shadow-sm rounded-3" readonly>
      </div>

      <div class="col-6">
       <label class="form-label fw-bold">Valor venta (COP)</label>
       <input type="text" id="precioProducto" class="form-control shadow-sm rounded-3" readonly>
      </div>

      <div class="col-12 col-md-6">
       <label for="cantidad" class="form-label fw-bold">Cantidad</label>
       <input type="number" class="form-control shadow-sm rounded-3" id="cantidad" name="cantidad" min="1" required>
      </div>
      <div class="col-12 col-md-6">
       <label for="observacion" class="form-label fw-bold">Observación</label>
       <input type="text" class="form-control shadow-sm rounded-3" id="observacion" name="observacion" placeholder="Ej:Habitacion 101">
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

<!-- Botón de volver al inicio -->
<a href="../index.php" class="btn btn-primary btn-volver-inicio">
    <i class="bi bi-arrow-left"></i> Volver al inicio
</a>

<!-- Bootstrap JS local -->
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarInfoProducto() {
    const select = document.getElementById("insumo");
    const selected = select.options[select.selectedIndex];

    let stock = selected.dataset.stock ? parseInt(selected.dataset.stock) : "";
    let precio = selected.dataset.precio ? parseInt(selected.dataset.precio) : "";

    document.getElementById("stockDisponible").value = stock;
    document.getElementById("precioProducto").value = precio ? "$ " + precio.toLocaleString("es-CO") : "";
}

function despacharInsumo() {
   let insumoFinal = document.getElementById("insumo").value;
   const cantidad = document.getElementById("cantidad").value;
   const observacion = document.getElementById("observacion").value;

   if (insumoFinal === "" || cantidad <= 0) {
    Swal.fire({
      icon: "warning",
      title: "Atención",
      text: "Selecciona un insumo y define una cantidad válida."
    });
    return;
   }

   let formData = new FormData();
   formData.append("insumo", insumoFinal);
   formData.append("cantidad", cantidad);
   formData.append("observacion", observacion);

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
         const modal = bootstrap.Modal.getInstance(document.getElementById("addProductosModal"));
         modal.hide();
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
       text: err
     });
   });
}
</script>
</body>
</html>
