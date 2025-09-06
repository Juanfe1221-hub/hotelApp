<?php
require '../db/dbconeccion.php';
session_start();
$rolUsuario = $_SESSION['rol'] ?? 'Invitado';

function calcularEstado($cantidad) {
    if ($cantidad <= 0) return "Agotado";
    if ($cantidad < 5) return "Bajo Stock";
    return "Disponible";
}

$action = $_GET['action'] ?? '';

// La bodega que manejara este archivo
$bodega = 2; 

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE sw_bodega = :bodega ORDER BY creado_en DESC");
    $stmt->execute([':bodega' => $bodega]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as &$p) {
        $estadoCalc = calcularEstado($p['cantidad']);
        if ($estadoCalc !== $p['estado']) {
            $upd = $pdo->prepare("UPDATE productos SET estado = :estado WHERE id = :id AND sw_bodega = :bodega");
            $upd->execute([':estado' => $estadoCalc, ':id' => $p['id'], ':bodega' => $bodega]);
            $p['estado'] = $estadoCalc;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($productos);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = calcularEstado($_POST['cantidad']);
    $inactivo = isset($_POST['inactivo']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO productos (nombre, cantidad, unidad, precio, valor_venta, estado, inactivo, sw_bodega, creado_por) 
                            VALUES (:nombre, :cantidad, :unidad, :precio, :valor_venta, :estado, :inactivo, :sw_bodega, 'admin')");
    $stmt->execute([
        ':nombre' => $_POST['nombre'],
        ':cantidad' => $_POST['cantidad'],
        ':unidad' => $_POST['unidad'],
        ':precio' => $_POST['precio'],
        ':valor_venta' => $_POST['valor_venta'],
        ':estado' => $estado,
        ':inactivo' => $inactivo,
        ':sw_bodega' => $bodega
    ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = calcularEstado($_POST['cantidad']);
    $inactivo = isset($_POST['inactivo']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE productos 
                            SET nombre=:nombre, cantidad=:cantidad, unidad=:unidad, precio=:precio, valor_venta=:valor_venta, estado=:estado, inactivo=:inactivo, actualizado_por='admin' 
                            WHERE id=:id AND sw_bodega = :sw_bodega");
    $stmt->execute([
        ':id' => $_POST['id'],
        ':nombre' => $_POST['nombre'],
        ':cantidad' => $_POST['cantidad'],
        ':unidad' => $_POST['unidad'],
        ':precio' => $_POST['precio'],
        ':valor_venta' => $_POST['valor_venta'],
        ':estado' => $estado,
        ':inactivo' => $inactivo,
        ':sw_bodega' => $bodega
    ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    $check = $pdo->prepare("SELECT COUNT(*) FROM despachos WHERE producto_id = :id");
    $check->execute([':id' => $id]);
    $tieneDespachos = $check->fetchColumn() > 0;

    if ($tieneDespachos) {
        echo json_encode(['success' => false, 'msg' => 'No se puede eliminar este producto porque tiene despachos asociados.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM productos WHERE id=:id AND sw_bodega = :bodega");
    $stmt->execute([':id' => $id, ':bodega' => $bodega]);
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Inventario de Productos - Bodega 2</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../icons/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../alerts/sweetalert2.min.css">
<script src="../alerts/sweetalert2.all.min.js"></script>

<style>
body { background:#f8f9fa; }
.card-producto { border-radius:12px; transition:transform .15s; }
.card-producto:hover{ transform:translateY(-4px); }
.estado-badge { font-size:0.85rem; }
.filter-btn.active { box-shadow: inset 0 -3px 0 rgba(0,0,0,0.08); }
@media (max-width:576px){
  .card-producto .card-body h5 { font-size:1rem; }
  .btn-group button {
    font-size: 0.85rem;
    padding: 0.35rem 0.5rem;
  }
}
</style>
</head>
<body class="container py-4">

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
  <div class="d-flex align-items-center gap-3">
    <h3 class="m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Inventario Bodega 2</h3>
    <div class="d-none d-sm-block text-muted"><?php echo htmlspecialchars($rolUsuario); ?></div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center w-100">
    <div class="btn-group flex-wrap w-100" role="group" aria-label="filtros">
      <button class="btn btn-outline-primary filter-btn active flex-grow-1" data-filter="all">Todos</button>
      <button class="btn btn-outline-success filter-btn flex-grow-1" data-filter="Disponible">Disponibles</button>
      <button class="btn btn-outline-warning filter-btn flex-grow-1" data-filter="Bajo Stock">Bajo Stock</button>
      <button class="btn btn-outline-danger filter-btn flex-grow-1" data-filter="Agotado">Agotados</button>
      <button class="btn btn-outline-secondary filter-btn flex-grow-1" data-filter="inactivo">Inactivos</button>
    </div>

    <div class="flex-grow-1" style="min-width:200px;">
      <input id="search" class="form-control" type="search" placeholder="🔎 Buscar producto..." />
    </div>

    <?php if ($rolUsuario === 'Administrador'): ?>
    <button class="btn btn-primary flex-shrink-0" onclick="abrirModal()">
      <i class="fa-solid fa-plus me-1"></i> Nuevo
    </button>
    <?php endif; ?>
  </div>
</div>

<div id="productos" class="row g-4"></div>

<div class="text-center mt-5">
  <a href="cafeteria.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Volver</a>
</div>

<div class="modal fade" id="productoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="productoForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-box"></i> Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="productoId" name="id">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input id="nombre" name="nombre" class="form-control" required>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Cantidad</label>
              <input id="cantidad" name="cantidad" type="number" step="1" min="0" class="form-control" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Unidad</label>
              <input id="unidad" name="unidad" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor Compra (COP)</label>
            <input id="precio" name="precio" type="number" step="1" min="0" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor Venta (COP)</label>
            <input id="valor_venta" name="valor_venta" type="number" step="1" min="0" class="form-control" required>
          </div>
          <div class="form-text"><i class="fa-solid fa-info-circle"></i> El estado se calcula automáticamente según la cantidad.</div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="inactivo" name="inactivo">
            <label class="form-check-label" for="inactivo">Inactivo</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success" type="submit"><i class="fa-solid fa-save me-1"></i> Guardar</button>
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../axios/axios.min.js"></script>
<script>
const esc = s => String(s ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
const nf = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0, minimumFractionDigits: 0 });

const productosDiv = document.getElementById('productos');
const modalEl = document.getElementById('productoModal');
const bsModal = new bootstrap.Modal(modalEl);
const form = document.getElementById('productoForm');
const searchInput = document.getElementById('search');
let productos = [];
let currentFilter = 'all';
let currentSearch = '';
const esAdmin = "<?php echo $rolUsuario; ?>" === "Administrador";

function cargarProductos() {
  axios.get('?action=list')
    .then(r => {
      productos = r.data || [];
      renderProductos();
    }).catch(err => {
      console.error(err);
      productosDiv.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error cargando productos</div></div>';
    });
}

function renderProductos() {
  const q = currentSearch.trim().toLowerCase();
  productosDiv.innerHTML = '';
  const filtrados = productos.filter(p => {
    const matchFilter = (currentFilter === 'all') || 
                        (currentFilter === 'inactivo' ? p.inactivo == 1 : p.estado === currentFilter);
    const matchSearch = !q || p.nombre.toLowerCase().includes(q);
    return matchFilter && matchSearch;
  });

  if (filtrados.length === 0) {
    productosDiv.innerHTML = '<div class="col-12 text-center text-muted py-4">No se encontraron productos.</div>';
    return;
  }

  filtrados.forEach(p => {
    const badgeClass = p.estado === 'Disponible' ? 'bg-success' : (p.estado === 'Bajo Stock' ? 'bg-warning text-dark' : 'bg-danger');
    const html = `
    <div class="col-lg-4 col-md-6 col-sm-12">
      <div class="card card-producto h-100 p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h5 class="mb-0"><i class="fa-solid fa-box me-2 text-primary"></i>${esc(p.nombre)}</h5>
          <span class="badge ${badgeClass} estado-badge">${esc(p.estado)}</span>
        </div>
        <p class="mb-1"><i class="fa-solid fa-layer-group me-2 text-secondary"></i> Cantidad: <strong>${nf.format(Number(p.cantidad))}</strong> ${esc(p.unidad)}</p>
        <p class="mb-1"><i class="fa-solid fa-dollar-sign me-2 text-secondary"></i> Valor Compra: <strong>$${nf.format(Number(p.precio))}</strong></p>
        <p class="mb-1"><i class="fa-solid fa-tags me-2 text-secondary"></i> Valor Venta: <strong>$${nf.format(Number(p.valor_venta ?? 0))}</strong></p>
        <div class="d-flex justify-content-end gap-2 mt-3">
          ${esAdmin ? `
          <button class="btn btn-sm btn-outline-primary edit-btn" 
                  data-id="${esc(p.id)}" data-nombre="${esc(p.nombre)}" data-cantidad="${esc(p.cantidad)}" 
                  data-unidad="${esc(p.unidad)}" data-precio="${esc(p.precio)}" 
                  data-valor_venta="${esc(p.valor_venta)}"
                  data-inactivo="${p.inactivo}">
            <i class="fa-solid fa-pen"></i>
          </button>` : ''}
          <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${esc(p.id)}">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
    </div>`;
    productosDiv.insertAdjacentHTML('beforeend', html);
  });
}

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    renderProductos();
  });
});

searchInput.addEventListener('input', e => {
  currentSearch = e.target.value;
  renderProductos();
});

function abrirModal() {
  form.reset();
  document.getElementById('productoId').value = '';
  bsModal.show();
}

document.addEventListener('click', e => {
  const editBtn = e.target.closest('.edit-btn');
  const delBtn = e.target.closest('.delete-btn');

  if (editBtn) {
    const id = editBtn.dataset.id;
    const nombre = editBtn.dataset.nombre;
    const cantidad = editBtn.dataset.cantidad;
    const unidad = editBtn.dataset.unidad;
    const precio = editBtn.dataset.precio;
    const valorVenta = editBtn.dataset.valor_venta;
    const inactivo = editBtn.dataset.inactivo;

    document.getElementById('productoId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('cantidad').value = cantidad;
    document.getElementById('unidad').value = unidad;
    document.getElementById('precio').value = precio;
    document.getElementById('valor_venta').value = valorVenta;
    document.getElementById('inactivo').checked = inactivo === "1";
    bsModal.show();
  }

  if (delBtn) {
    const id = delBtn.dataset.id;
    Swal.fire({
      title: '¿Eliminar este producto?',
      text: "Esta acción no se puede revertir",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        axios.post('?action=delete', fd)
          .then(res => {
            if(res.data.success){
              Swal.fire('Eliminado', 'El producto fue eliminado correctamente', 'success');
              cargarProductos();
            } else {
              Swal.fire('Error', res.data.msg, 'error');
            }
          });
      }
    });
  }
});

form.addEventListener('submit', e => {
  e.preventDefault();
  const fd = new FormData(form);
  const isUpdate = Boolean(fd.get('id'));
  const url = isUpdate ? '?action=update' : '?action=create';
  axios.post(url, fd)
    .then(() => {
      bsModal.hide();
      form.reset();
      cargarProductos();
    })
    .catch(err => {
      console.error(err);
      Swal.fire('Error', 'Ocurrió un error al guardar', 'error');
    });
});

cargarProductos();
</script>
</body>
</html>