<?php
date_default_timezone_set('America/Bogota');
require '../db/dbconeccion.php';

$valorPorHoraDefault = 5000;

/**
 * Función para crear el campo valor_por_hora si no existe
 */
function verificarEstructuraTabla($pdo) {
    try {
        // Verificar si existe la columna valor_por_hora
        $stmt = $pdo->query("SHOW COLUMNS FROM parqueadero LIKE 'valor_por_hora'");
        if ($stmt->rowCount() === 0) {
            // Crear la columna valor_por_hora
            $pdo->exec("ALTER TABLE parqueadero ADD COLUMN valor_por_hora DECIMAL(10,2) DEFAULT 5000 AFTER is_stadia");
            
            // Migrar datos existentes: mover duration_hours a valor_por_hora y limpiar duration_hours
            $pdo->exec("UPDATE parqueadero SET valor_por_hora = duration_hours WHERE valor_por_hora IS NULL");
            $pdo->exec("UPDATE parqueadero SET duration_hours = NULL WHERE exit_time IS NULL");
        }
        return true;
    } catch (Exception $e) {
        error_log("Error verificando estructura: " . $e->getMessage());
        return false;
    }
}

// Verificar y actualizar estructura de la tabla
$estructuraOk = verificarEstructuraTabla($pdo);

/**
 * sanitize_digits:
 * Quita cualquier carácter no numérico y devuelve INT o null si no hay dígitos.
 */
function sanitize_digits($v) {
    if ($v === null) return null;
    $s = preg_replace('/\D+/', '', (string)$v);
    return $s === '' ? null : intval($s);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $placa = strtoupper(trim($_POST['placa'] ?? ''));
            $isEstadia = isset($_POST['is_stadia']) ? 1 : 0;
            $tipoVehiculo = $_POST['tipo_vehiculo'] ?? 'Automovil';

            // Obtener y procesar el valor por hora
            $valorRaw = $_POST['valor_por_hora'] ?? '';
            $valorIngresado = sanitize_digits($valorRaw);

            if ($isEstadia) {
                $valorPorHora = 0; // Estadía sin cobro
            } else {
                // Si no hay valor válido, usar el default
                if ($valorIngresado === null || $valorIngresado === 0) {
                    $valorPorHora = $valorPorHoraDefault;
                } else {
                    // Aplicar límites
                    if ($valorIngresado < 1000) {
                        $valorPorHora = 1000;
                    } elseif ($valorIngresado > 100000) {
                        $valorPorHora = 100000;
                    } else {
                        $valorPorHora = $valorIngresado;
                    }
                }
            }

            if ($placa !== '') {
                if ($estructuraOk) {
                    // Usar la nueva estructura con campo dedicado para valor_por_hora
                    $stmt = $pdo->prepare("INSERT INTO parqueadero (placa, entry_time, is_stadia, valor_por_hora, tipo_vehiculo) 
                                            VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$placa, date('Y-m-d H:i:s'), $isEstadia, $valorPorHora, $tipoVehiculo]);
                } else {
                    // Fallback: usar estructura antigua
                    $stmt = $pdo->prepare("INSERT INTO parqueadero (placa, entry_time, is_stadia, duration_hours, tipo_vehiculo) 
                                            VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$placa, date('Y-m-d H:i:s'), $isEstadia, $valorPorHora, $tipoVehiculo]);
                }
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'remove') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("SELECT * FROM parqueadero WHERE id = ?");
            $stmt->execute([$id]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($car) {
                $entry = new DateTime($car['entry_time']);
                $exit = new DateTime();
                $diffSecs = $exit->getTimestamp() - $entry->getTimestamp();
                $horasTranscurridas = $diffSecs / 3600; 
                
                // Obtener el valor por hora del campo correcto
                if ($estructuraOk && isset($car['valor_por_hora'])) {
                    $valorPorHora = floatval($car['valor_por_hora']);
                } else {
                    $valorPorHora = floatval($car['duration_hours']); // Fallback
                }
                
                $cost = ($car['is_stadia'] == 0) ? ceil($horasTranscurridas) * $valorPorHora : 0;

                // Actualizar con la hora de salida, el cobro y las horas transcurridas
                $stmt = $pdo->prepare("UPDATE parqueadero SET exit_time = ?, charge = ?, duration_hours = ? WHERE id = ?");
                $stmt->execute([date('Y-m-d H:i:s'), $cost, round($horasTranscurridas, 2), $id]);

                $msg = [
                    'icon' => 'success',
                    'title' => 'Vehículo retirado',
                    'html' =>
                        "Duración: <strong>" . gmdate("H:i:s", $diffSecs) . "</strong><br>" .
                        "Horas transcurridas: <strong>" . number_format($horasTranscurridas, 2) . " horas</strong><br>" .
                        ($car['is_stadia'] == 0
                            ? "Valor por hora: <strong>$" . number_format($valorPorHora, 0, ',', '.') . " COP</strong><br>" .
                              "Total a pagar: <strong>$" . number_format($cost, 0, ',', '.') . " COP</strong>"
                            : "Estadía sin cobro")
                ];

                header("Location: " . $_SERVER['PHP_SELF'] . "?swal=" . urlencode(json_encode($msg)));
                exit;
            }
        }

        // Acción para migrar estructura
        if ($action === 'migrate_structure') {
            if (verificarEstructuraTabla($pdo)) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?msg=estructura_migrada");
            } else {
                header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error_migracion");
            }
            exit;
        }
    }
}

// Obtener vehículos activos con la consulta correcta según la estructura
if ($estructuraOk) {
    $cars = $pdo->query("SELECT *, valor_por_hora FROM parqueadero WHERE exit_time IS NULL ORDER BY entry_time DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cars = $pdo->query("SELECT * FROM parqueadero WHERE exit_time IS NULL ORDER BY entry_time DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Parqueadero</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../alerts/sweetalert2.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="../alerts/sweetalert2.all.min.js"></script>
    <style>
        html, body { 
    font-family:'Segoe UI', Arial, sans-serif; 
    background:#f0f4f8; 
    display:flex; 
    justify-content:center; 
    /* align-items:center; */ 
    padding-top: 40px;        
}
        body { font-family:'Segoe UI', Arial, sans-serif; background:#f0f4f8; display:flex; justify-content:center; align-items:center; }
        .container { max-width:700px; width:95%; background:#fff; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); padding:30px; }
        h1{ text-align:center; margin-bottom:25px; font-size:1.8rem; }
        label{ font-weight:600; display:block; margin-bottom:6px; font-size:.95rem; }
        input[type="text"], select { width:100%; padding:10px; margin-bottom:12px; border:1px solid #ccc; border-radius:6px; font-size:1rem; }
        .checkbox-group{ display:flex; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
        .checkbox-group input{ margin-right:8px; }
        button{ background:#5d34a8; color:#fff; border:0; border-radius:6px; padding:10px 20px; cursor:pointer; font-size:1rem; }
        button:hover{ background:#4b2880; }
        .car-item{ display:flex; justify-content:space-between; align-items:flex-start; background:#f8f9fa; padding:15px; margin-bottom:10px; border-radius:10px; flex-wrap:wrap; }
        .car-details{ flex:1 1 200px; min-width:200px; }
        .car-details p{ margin:3px 0; font-size:.9rem; }
        .remove-btn{ background:#dc3545; padding:8px 14px; border:0; border-radius:6px; color:#fff; cursor:pointer; margin-top:10px; }
        .remove-btn:hover{ background:#b02a37; }
        .error { color: #a94442; font-size: .9rem; margin-top: -8px; margin-bottom: 10px; display:none; }
        a.back-btn{ display:inline-block; padding:10px 25px; background:#6c757d; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold; margin-top:20px;}
        .valor-preview { background: #e8f5e8; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 0.9rem; }
        .tiempo-info { background: #f0f9ff; padding: 8px; border-radius: 4px; margin-top: 5px; font-size: 0.85rem; border-left: 3px solid #0ea5e9; }
        .costo-info { background: #fefce8; padding: 8px; border-radius: 4px; margin-top: 5px; font-size: 0.85rem; border-left: 3px solid #eab308; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-warning { background: #fef3cd; border: 1px solid #faebcc; color: #8a6d3b; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        @media (max-width:600px){ .container{padding:20px;} .car-item{flex-direction:column; align-items:flex-start;} .remove-btn{width:100%;} }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-parking"></i> Gestión de Parqueadero</h1>

    <?php if (!$estructuraOk): ?>
        <div class="alert alert-warning">
            <strong>Atención:</strong> La estructura de la base de datos necesita actualización para funcionar correctamente.
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="action" value="migrate_structure" />
                <button type="submit" style="background:#f59e0b;">
                    <i class="fas fa-database"></i> Actualizar estructura de BD
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Base de datos actualizada y funcionando correctamente.
        </div>
    <?php endif; ?>

    <!-- FORMULARIO DE ALTA -->
    <form id="form-add" method="POST" novalidate>
        <input type="hidden" name="action" value="add" />
        
        <label for="placa">Placa del vehículo:</label>
        <input type="text" name="placa" id="placa" required maxlength="10" placeholder="Ej: ABC123" />

        <label for="tipo_vehiculo">Tipo de vehículo:</label>
        <select name="tipo_vehiculo" id="tipo_vehiculo" required>
            <option value="Automovil">Automóvil</option>
            <option value="Moto">Moto</option>
            <option value="Camion">Camión</option>
            <option value="Bus">Bus</option>
        </select>

        <div class="checkbox-group">
            <input type="checkbox" id="is-estadia" name="is_stadia" />
            <label for="is-estadia">Parqueadero por estadía (sin cobro por hora)</label>
        </div>

        <label for="valor_por_hora">Valor por hora en pesos colombianos (COP):</label>
        <input
            type="text"
            name="valor_por_hora"
            id="valor_por_hora"
            inputmode="numeric"
            placeholder="Ej: 5000"
            value="<?= $valorPorHoraDefault ?>"
            autocomplete="off"
        />
        <div class="valor-preview" id="valor-preview">
            Valor por hora: <strong>$<span id="valor-formatted">5.000</span> COP</strong>
        </div>
        <div id="valor-error" class="error">Ingrese solo números (mínimo $1.000, máximo $100.000).</div>

        <button type="submit" id="btn-add">
            <i class="fas fa-plus-circle"></i> Registrar vehículo
        </button>
    </form>

    <hr style="margin: 25px 0; border: none; border-top: 1px solid #eee;">

    <h3>Vehículos en el parqueadero (<?= count($cars) ?>)</h3>

    <?php if (count($cars) === 0): ?>
        <p style="text-align:center; color:#666; margin:30px 0;">No hay vehículos registrados actualmente.</p>
    <?php else: ?>
        <?php foreach ($cars as $car): ?>
            <?php 
                $entryTimestamp = (new DateTime($car['entry_time']))->getTimestamp(); 
                
                // Obtener el valor por hora del campo correcto
                if ($estructuraOk && isset($car['valor_por_hora'])) {
                    $valorPorHora = (int)$car['valor_por_hora'];
                } else {
                    $valorPorHora = (int)$car['duration_hours'];
                }
            ?>
            <div class="car-item" data-entry="<?= $entryTimestamp ?>" data-valor="<?= $valorPorHora ?>" id="car-<?= $car['id'] ?>">
                <div class="car-details">
                    <p><strong>Placa:</strong> <?= htmlspecialchars($car['placa']) ?></p>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($car['tipo_vehiculo']) ?></p>
                    <p><strong>Hora de entrada:</strong> <?= date('d/m/Y H:i:s', $entryTimestamp) ?></p>
                    <p><strong>Tiempo transcurrido:</strong> <span class="tiempo-transcurrido">Calculando...</span></p>
                    
                    <?php if ($car['is_stadia'] == 0): ?>
                        <p><strong>Valor por hora:</strong> $<?= number_format($valorPorHora, 0, ',', '.') ?> COP</p>
                        <div class="tiempo-info">
                            <i class="fas fa-clock"></i> <strong>Horas:</strong> <span class="horas-transcurridas">0.00</span> h
                        </div>
                        <div class="costo-info">
                            <i class="fas fa-calculator"></i> <strong>Costo actual:</strong> $<span class="costo-actual">0</span> COP
                        </div>
                    <?php else: ?>
                        <div style="background:#e8f5e8; padding:8px; border-radius:4px; margin-top:5px;">
                            <i class="fas fa-parking"></i> <strong>Estadía gratuita</strong>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="remove" />
                    <input type="hidden" name="id" value="<?= $car['id'] ?>" />
                    <button type="submit" class="remove-btn">
                        <i class="fas fa-sign-out-alt"></i> Retirar
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="text-align:center;">
        <a href="hospedaje.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<script>
const formAdd = document.getElementById('form-add');
const valorInput = document.getElementById('valor_por_hora');
const isEstadia = document.getElementById('is-estadia');
const valorError = document.getElementById('valor-error');
const valorPreview = document.getElementById('valor-preview');
const valorFormatted = document.getElementById('valor-formatted');

// Función para limpiar y dejar solo números
function onlyDigits(str) {
    return (str || '').replace(/\D+/g, '');
}

// Función para formatear números con puntos de miles
function formatCurrency(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Actualizar la vista previa del valor
function updateValuePreview() {
    const value = parseInt(valorInput.value) || 0;
    valorFormatted.textContent = formatCurrency(value);
    
    if (isEstadia.checked) {
        valorPreview.style.display = 'none';
    } else {
        valorPreview.style.display = 'block';
    }
}

// Eventos del input de valor
valorInput.addEventListener('input', (e) => {
    const cleaned = onlyDigits(e.target.value);
    e.target.value = cleaned;
    valorError.style.display = 'none';
    updateValuePreview();
});

valorInput.addEventListener('paste', (ev) => {
    ev.preventDefault();
    const text = (ev.clipboardData || window.clipboardData).getData('text');
    valorInput.value = onlyDigits(text);
    updateValuePreview();
});

// Evento del checkbox de estadía
isEstadia.addEventListener('change', () => {
    if (isEstadia.checked) {
        valorInput.value = '';
        valorInput.disabled = true;
        valorError.style.display = 'none';
        valorPreview.style.display = 'none';
    } else {
        valorInput.disabled = false;
        if (!valorInput.value) {
            valorInput.value = '<?= $valorPorHoraDefault ?>';
        }
        updateValuePreview();
    }
});

// Validación al enviar el formulario
formAdd.addEventListener('submit', (e) => {
    if (isEstadia.checked) return; // Permitir envío para estadía
    
    const v = valorInput.value.trim();
    if (!/^\d+$/.test(v)) {
        valorError.textContent = 'Ingrese solo números.';
        valorError.style.display = 'block';
        valorInput.focus();
        e.preventDefault();
        return;
    }
    
    const n = parseInt(v, 10);
    if (n < 1000 || n > 100000) {
        valorError.textContent = 'El valor debe estar entre $1.000 y $100.000 COP.';
        valorError.style.display = 'block';
        valorInput.focus();
        e.preventDefault();
        return;
    }
});

// Función para actualizar tiempos y costos en tiempo real
function actualizarTiempos() {
    const carItems = document.querySelectorAll('.car-item');
    const now = Math.floor(Date.now() / 1000);
    
    carItems.forEach(item => {
        const entryTime = parseInt(item.getAttribute('data-entry'));
        const valorPorHora = parseInt(item.getAttribute('data-valor')) || 0;
        const spanTiempo = item.querySelector('.tiempo-transcurrido');
        const spanHoras = item.querySelector('.horas-transcurridas');
        const spanCosto = item.querySelector('.costo-actual');
        
        if (!spanTiempo) return;
        
        const elapsed = now - entryTime;
        const hours = Math.floor(elapsed / 3600);
        const minutes = Math.floor((elapsed % 3600) / 60);
        const seconds = elapsed % 60;
        
        spanTiempo.textContent = `${hours}h ${minutes}m ${seconds}s`;
        
        // Actualizar horas y costo si no es estadía
        if (spanHoras && spanCosto && valorPorHora > 0) {
            const horasDecimal = elapsed / 3600;
            const costoActual = Math.ceil(horasDecimal) * valorPorHora; // Redondear hacia arriba las horas
            
            spanHoras.textContent = horasDecimal.toFixed(2);
            spanCosto.textContent = formatCurrency(costoActual);
        }
    });
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    updateValuePreview();
    actualizarTiempos();
    setInterval(actualizarTiempos, 1000);
    
    // Mostrar alertas si vienen en la URL
    const params = new URLSearchParams(location.search);
    const swalStr = params.get('swal');
    const msg = params.get('msg');
    
    if (swalStr) {
        try {
            const opt = JSON.parse(decodeURIComponent(swalStr));
            Swal.fire(opt);
            history.replaceState({}, '', location.pathname);
        } catch (e) {
            console.error('Error parsing swal data:', e);
        }
    }
    
    if (msg === 'estructura_migrada') {
        Swal.fire({
            icon: 'success',
            title: 'Base de datos actualizada',
            text: 'La estructura ha sido migrada correctamente. Los datos ahora se guardan en el campo correcto.',
            timer: 3000
        });
        history.replaceState({}, '', location.pathname);
    } else if (msg === 'error_migracion') {
        Swal.fire({
            icon: 'error',
            title: 'Error en migración',
            text: 'No se pudo actualizar la estructura. Contacte al administrador.',
        });
        history.replaceState({}, '', location.pathname);
    }
});
</script>
</body>
</html>