<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// Credenciales de la base de datos
$host = 'localhost'; 
$dbname = 'hotel_florencia';
$user = 'admin'; 
$pass = 'B]TI-R5_zg*2jGq7'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    die();
}
?>