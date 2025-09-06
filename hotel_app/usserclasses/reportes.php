<?php
require("../db/dbconeccion.php");

// Carpeta donde se guardarán los archivos SQL
$reportesDir = __DIR__ . '/reportesSQL';
if(!is_dir($reportesDir)) mkdir($reportesDir, 0777, true);

// Subir archivo SQL
$msg = null;
if(isset($_POST['subirReporte']) && isset($_FILES['sqlFile'])){
    $file = $_FILES['sqlFile'];
    $filename = basename($file['name']);
    $targetPath = $reportesDir . '/' . $filename;

    if(move_uploaded_file($file['tmp_name'], $targetPath)){
        $msg = "Archivo subido correctamente.";
    } else {
        $msg = "Error al subir el archivo.";
    }
}

// Obtener lista de archivos SQL
$archivosSQL = array_diff(scandir($reportesDir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Cafetería</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            background-color: #f0f2f5; 
            padding-top: 70px; 
            position: relative; 
            min-height: 100vh;
        }
        .card-reporte { 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            margin-bottom: 1.5rem; 
            padding: 1.5rem; 
            background: #fff; 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-reporte:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .card-reporte h5 { 
            margin-bottom: 1rem; 
            font-weight: 600;
        }
        .btn-fab {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            position: fixed;
            bottom: 30px;
            right: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
        }
        .btn-fab i {
            margin-top: -2px;
        }
        .form-control-sm {
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
            font-size: .875rem;
        }
        /* Estilo mejorado para el título */
       .titulo-principal {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    background: linear-gradient(90deg, #217346, #34A853);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 1px 1px 5px rgba(0,0,0,0.2);
    margin-bottom: 50px;
}
        /* Botón volver */
        .btn-volver {
            position: absolute;
            top: 20px;
            left: 20px;
        }
    </style>
</head>
<body>

    <a href="cafeteria.php" class="btn btn-outline-secondary btn-volver">
        <i class="bi bi-arrow-left"></i> Volver
    </a>

    <main class="container py-5">
        <h2 class="titulo-principal">Generador de Reportes</h2>

        <?php if($msg): ?>
            <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach($archivosSQL as $archivo): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card-reporte">
                        <h5><?= htmlspecialchars($archivo) ?></h5>
                        <form method="POST" target="_blank" action="generarReporte.php">
                            <input type="hidden" name="archivoSQL" value="<?= htmlspecialchars($archivo) ?>">
                            <div class="row mb-3 gx-2">
                                <div class="col-6">
                                    <label class="form-label visually-hidden">Fecha Inicio</label>
                                    <input type="date" name="fechaInicio" class="form-control form-control-sm" required placeholder="Fecha Inicio">
                                </div>
                                <div class="col-6">
                                    <label class="form-label visually-hidden">Fecha Fin</label>
                                    <input type="date" name="fechaFin" class="form-control form-control-sm" required placeholder="Fecha Fin">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="exportCSV" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-filetype-csv"></i> CSV
                                </button>
                                <button type="submit" name="exportExcel" class="btn btn-outline-success">
                                    <i class="bi bi-filetype-xlsx"></i> Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <button class="btn btn-primary btn-fab" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-plus"></i>
    </button>

    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Subir Nuevo Reporte SQL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sqlFile" class="form-label">Seleccionar archivo SQL</label>
                            <input type="file" name="sqlFile" id="sqlFile" class="form-control" accept=".sql" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" name="subirReporte" class="btn btn-primary">Subir Reporte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
