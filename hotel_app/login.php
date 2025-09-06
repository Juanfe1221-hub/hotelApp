<?php
// Conexión a la base de datos
require 'db/dbconeccion.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Buscar usuario por nombre
        $stmt = $pdo->prepare("SELECT usuario_id, nombres, password, rol FROM usuarios WHERE nombres = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $user['usuario_id'];
            $_SESSION['nombres']    = $user['nombres'];
            $_SESSION['rol']        = $user['rol'];
            $_SESSION['ultimo_acceso'] = time();

            header("Location: index.php");
            exit;
        } else {
            $error_message = "Usuario o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $error_message = "Error en la consulta: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Hotel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="icons/css/all.min.css">

    <style>
        body {
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;

            /* Imagen de fondo */
            background: url('images/logo.svg') no-repeat center center fixed;
            background-size: cover;

            /* Oscurecer ligeramente para mejorar visibilidad */
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Capa oscura para resaltar el formulario */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.92);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,.3);
            width: 100%;
            max-width: 400px;
            z-index: 1;
            backdrop-filter: blur(8px);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #243b55;
            font-weight: bold;
        }

        .form-control {
            border-radius: 12px;
            padding-left: 45px;
        }

        .form-group {
            position: relative;
        }

        .form-group i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #999;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .btn-login {
            width: 100%;
            border-radius: 12px;
            background-color: #243b55;
            border: none;
            color: #fff;
            font-weight: bold;
            transition: background .3s ease;
        }

        .btn-login:hover {
            background-color: #1c2b40;
        }

        .alert-danger {
            margin-top: -15px;
            margin-bottom: 20px;
            border-radius: 12px;
        }

        /* Pie de página */
        .footer {
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
            color: #fff;
            font-size: 14px;
            z-index: 1;
        }

        /* Ajustes responsive */
        @media screen and (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
                max-width: 90%;
            }

            .login-box h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST" autocomplete="off">
            <div class="mb-4 form-group">
                <i class="fas fa-user"></i>
                <input type="text" class="form-control" name="usuario" placeholder="Usuario" required>
            </div>
            <div class="mb-4 form-group">
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar sesión
            </button>
        </form>
    </div>

    <div class="footer">
        2025 © Jcode | Hecho con ❤️ <br>
        Orgullo caqueteño
    </div>

    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
        });
    </script>
</body>
</html>
