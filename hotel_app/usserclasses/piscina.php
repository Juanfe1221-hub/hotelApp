<?php
// piscina.php

require '../db/dbconeccion.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_count') {
        $tipo = $_POST['tipo_piscina'] ?? 'adultos';

        $stmt = $pdo->prepare("
            SELECT SUM(CASE WHEN accion='entrada' THEN 1 ELSE -1 END) AS conteo 
            FROM piscina 
            WHERE fecha = CURDATE() AND tipo_piscina = ?
        ");
        $stmt->execute([$tipo]);
        $row = $stmt->fetch();
        echo json_encode(['count' => $row['conteo'] ?? 0]);
        exit;
    }

    if ($action === 'add') {
        $tipo = $_POST['tipo_piscina'] ?? 'adultos';

        $stmt = $pdo->prepare("
            SELECT SUM(CASE WHEN accion='entrada' THEN 1 ELSE -1 END) AS conteo 
            FROM piscina 
            WHERE fecha = CURDATE() AND tipo_piscina = ?
        ");
        $stmt->execute([$tipo]);
        $row = $stmt->fetch();
        $currentCount = $row['conteo'] ?? 0;

        $maxCapacity = 50;
        if ($currentCount >= $maxCapacity) {
            echo json_encode(['status' => 'error', 'message' => 'La piscina ha alcanzado su capacidad máxima']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO piscina(fecha,hora,accion,conteo,tipo_piscina) VALUES(CURDATE(),CURTIME(),'entrada',?,?)");
        $stmt->execute([$currentCount + 1, $tipo]);

        echo json_encode(['status' => 'ok', 'count' => $currentCount + 1]);
        exit;
    }

    if ($action === 'remove') {
        $tipo = $_POST['tipo_piscina'] ?? 'adultos';

        $stmt = $pdo->prepare("
            SELECT SUM(CASE WHEN accion='entrada' THEN 1 ELSE -1 END) AS conteo 
            FROM piscina 
            WHERE fecha = CURDATE() AND tipo_piscina = ?
        ");
        $stmt->execute([$tipo]);
        $row = $stmt->fetch();
        $currentCount = $row['conteo'] ?? 0;

        if ($currentCount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'No hay personas en la piscina']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO piscina(fecha,hora,accion,conteo,tipo_piscina) VALUES(CURDATE(),CURTIME(),'salida',?,?)");
        $stmt->execute([$currentCount - 1, $tipo]);

        echo json_encode(['status' => 'ok', 'count' => $currentCount - 1]);
        exit;
    }

    if ($action === 'export') {
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $fechaFin = $_POST['fecha_fin'] ?? '';

        if (!$fechaInicio || !$fechaFin) {
            echo json_encode(['status' => 'error', 'message' => 'Selecciona un rango de fechas']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM piscina WHERE fecha BETWEEN ? AND ? ORDER BY fecha,hora ASC");
        $stmt->execute([$fechaInicio, $fechaFin]);
        $data = $stmt->fetchAll();

        echo json_encode(['status' => 'ok', 'data' => $data]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Administrar Piscina</title>

    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../style/all.min.css" />

    <style>
        body {
            background: #f7f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-container {
            max-width: 480px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .pool-icon { font-size: 3.5rem; color: #0d6efd; margin-bottom: 0.8rem; }
        .title { font-weight: 700; font-size: 2rem; margin-bottom: 1.2rem; color: #343a40; }
        .status-section { margin-bottom: 1.8rem; }
        #current-count { font-size: 4.5rem; font-weight: 900; color: #198754; margin: 0; line-height: 1; }
        .capacity-info { font-size: 1rem; color: #6c757d; margin-top: 0.3rem; font-weight: 600; }
        .control-buttons { display: flex; justify-content: center; gap: 1rem; margin-bottom: 2rem; }
        .btn-add, .btn-remove { min-width: 140px; font-weight: 600; font-size: 1.05rem; transition: transform 0.2s ease; }
        .btn-add:hover { background-color: #157347; transform: scale(1.05); }
        .btn-remove:hover { background-color: #b02a37; transform: scale(1.05); }
        hr { border-top: 1px solid #dee2e6; margin-bottom: 2rem; }
        .report-section { text-align: center; }
        .report-section h5 { font-weight: 600; margin-bottom: 1rem; color: #495057; }
        .form-label { font-weight: 600; color: #495057; }
        #export-excel { font-weight: 600; transition: background-color 0.3s ease; }
        #export-excel:hover { background-color: #0b5ed7; color: white; }
        .back-button-container { margin: 1.5rem auto 2rem; text-align: center; }
        .back-button-container a { background-color: #6c757d; border: none; color: white; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: background-color 0.3s ease; }
        .back-button-container a:hover { background-color: #5a6268; }
        @media (max-width: 576px) {
            .card-container { margin: 1rem 0.5rem; padding: 2rem 1rem; }
            #current-count { font-size: 3.5rem; }
            .control-buttons { flex-direction: column; gap: 0.8rem; }
            .btn-add, .btn-remove { min-width: 100%; }
            .report-section .row > div { margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

<div class="card-container">
    <i class="fas fa-swimming-pool pool-icon"></i>
    <h1 class="title">Administrar Piscina</h1>

    <div class="status-section">
        <p id="current-count">0</p>
        <p class="capacity-info">
            <span id="capacity-text">Aforo Actual</span> / <span id="max-capacity">50</span>
        </p>
    </div>

    <!-- Nuevo selector -->
    <div class="mb-3">
        <label class="form-label d-block">Selecciona piscina:</label>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tipoPiscina" id="adultos" value="adultos" checked>
            <label class="form-check-label" for="adultos">Adultos</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tipoPiscina" id="ninos" value="niños">
            <label class="form-check-label" for="ninos">Niños</label>
        </div>
    </div>

    <div class="control-buttons">
        <button id="add-person" class="btn btn-success btn-add"><i class="fas fa-plus"></i> Añadir Persona</button>
        <button id="remove-person" class="btn btn-danger btn-remove"><i class="fas fa-minus"></i> Eliminar Persona</button>
    </div>

    <hr />
</div>

<div class="back-button-container">
    <a onclick="window.history.back()" ><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentCountDisplay = document.getElementById('current-count');
    const addPersonBtn = document.getElementById('add-person');
    const removePersonBtn = document.getElementById('remove-person');

    function getTipoPiscina() {
        return document.querySelector('input[name="tipoPiscina"]:checked').value;
    }

    async function getCount() {
        try {
            const formData = new FormData();
            formData.append('tipo_piscina', getTipoPiscina());

            const res = await fetch('piscina.php?action=get_count', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            currentCountDisplay.textContent = data.count || 0;
        } catch (e) {
            console.error('Error fetching count:', e);
            currentCountDisplay.textContent = '?';
        }
    }

    async function addPerson() {
        try {
            const formData = new FormData();
            formData.append('tipo_piscina', getTipoPiscina());

            const res = await fetch('piscina.php?action=add', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                currentCountDisplay.textContent = data.count;
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Error al añadir persona');
            console.error(e);
        }
    }

    async function removePerson() {
        try {
            const formData = new FormData();
            formData.append('tipo_piscina', getTipoPiscina());

            const res = await fetch('piscina.php?action=remove', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                currentCountDisplay.textContent = data.count;
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Error al eliminar persona');
            console.error(e);
        }
    }

    // Eventos
    addPersonBtn.addEventListener('click', addPerson);
    removePersonBtn.addEventListener('click', removePerson);

    // Refrescar cada 5 segundos
    setInterval(getCount, 5000);

    // Cambiar conteo cuando seleccionan otro tipo de piscina
    document.querySelectorAll('input[name="tipoPiscina"]').forEach(radio => {
        radio.addEventListener('change', getCount);
    });

    // Primera carga
    getCount();
});
</script>
<script src="../style/all.min.js"></script>
</body>
</html>
