<?php
require("../db/dbconeccion.php");

// Validaciones
if(!isset($_POST['archivoSQL'], $_POST['fechaInicio'], $_POST['fechaFin'])){
    die("Parámetros incompletos.");
}

$archivoSQL = basename($_POST['archivoSQL']); // seguridad
$fechaInicio = $_POST['fechaInicio'];
$fechaFin = $_POST['fechaFin'];
$sqlPath = __DIR__ . '/reportesSQL/' . $archivoSQL;

if(!file_exists($sqlPath)){
    die("Archivo SQL no encontrado.");
}

// Leer SQL
$sql = file_get_contents($sqlPath);

// Detectar si el SQL contiene los placeholders
$params = [];
if (strpos($sql, ':fechaInicio') !== false) $params[':fechaInicio'] = $fechaInicio;
if (strpos($sql, ':fechaFin') !== false) $params[':fechaFin'] = $fechaFin;

// Preparar y ejecutar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($resultado)){
    die("No hay datos para este rango de fechas.");
}

// Generar CSV
if(isset($_POST['exportCSV'])){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte.csv"');
    $output = fopen('php://output', 'w');

    // Cabeceras
    fputcsv($output, array_keys($resultado[0]));

    // Datos
    foreach($resultado as $row){
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Generar Excel (HTML que Excel abre)
if(isset($_POST['exportExcel'])){
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte.xls"');

    echo "<table border='1'>";
    // Cabeceras
    echo "<tr>";
    foreach(array_keys($resultado[0]) as $header){
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr>";

    // Datos
    foreach($resultado as $row){
        echo "<tr>";
        foreach($row as $cell){
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit;
}
?>
