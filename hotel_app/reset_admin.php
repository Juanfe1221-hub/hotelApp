<?php
// reset_admin.php  — ejecutar UNA vez y luego borrar por seguridad
require 'db/dbconeccion.php';

$username = 'admin';
$newPassword = 'admin123';

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE nombres = ?");
$stmt->execute([$hash, $username]);

echo "Contraseña de '{$username}' actualizada a: {$newPassword} (hash guardado).";
