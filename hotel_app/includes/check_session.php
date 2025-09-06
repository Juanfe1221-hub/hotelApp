<?php
// ⚡ Configuración de seguridad de la sesión ANTES de iniciarla
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Inicia la sesión
session_start();

// Detecta automáticamente la ruta base del proyecto
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($baseUrl, '/\\') . '/';

// Si no hay sesión, redirigir al login dentro de hotel_app
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['nombres'])) {
    header("Location: {$baseUrl}login.php");
    exit();
}

// Tiempo máximo de inactividad (ej: 15 min)
$tiempo_inactividad = 900;
if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > $tiempo_inactividad) {
    session_unset();
    session_destroy();
    header("Location: {$baseUrl}login.php?timeout=1");
    exit();
}
$_SESSION['ultimo_acceso'] = time();

// 🚨 Opcional: Control de roles
// if ($_SESSION['rol'] !== 'admin') {
//     header("Location: {$baseUrl}sin_permiso.php");
//     exit();
// }
