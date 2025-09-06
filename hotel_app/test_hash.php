<?php
$hash = '$2y$10$ZJcn8DdXDEQoxR.7jZrb7O9PfyXwXvAXF5LrOQucdQpGg0vDl4C6K'; // copia el de tu BD
$password = "12345"; // la clave que quieres probar

if (password_verify($password, $hash)) {
    echo "✅ La contraseña coincide";
} else {
    echo "❌ La contraseña NO coincide";
}