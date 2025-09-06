<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Servicios</title>
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="style/all.min.css">
  <link rel="stylesheet" href="style/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;

      /* Imagen de fondo */
      background: url('images/logo.svg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      min-height: 100vh;
    }

    /* Capa oscura para dar contraste */
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 0;
    }

    /* Título principal */
    .titulo {
      text-align: center;
      margin: 30px 20px 20px 20px;
      font-size: 32px;
      font-weight: 700;
      color: #fff;
      position: relative;
      z-index: 1;
    }

    /* Animación del icono */
    .titulo i {
      color: #00d4ff;
      margin-right: 10px;
      display: inline-block;
      animation: bounce 1.5s infinite;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-8px); }
      60% { transform: translateY(-4px); }
    }

    main {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .container {
      display: flex;
      flex-wrap: wrap;
      gap: 25px;
      justify-content: center;
      margin-bottom: 50px;
    }

    /* Tarjetas de selección de sede */
    .card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 20px;
      width: 180px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      border-top: 5px solid #007bff;
      backdrop-filter: blur(6px);
    }

    .card img {
      width: 80px;
      margin-bottom: 15px;
      transition: transform 0.3s ease;
    }

    .card h2 {
      font-size: 20px;
      color: #007bff;
      margin-top: 0;
      font-weight: 600;
    }

    .card:hover {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 20px 35px rgba(0,0,0,0.3);
    }

    /* User Panel */
    .user-panel {
      position: fixed;
      top: 0;
      left: -300px;
      width: 300px;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.97);
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
      transition: left 0.3s ease;
      z-index: 1000;
      padding: 20px;
    }

    .user-panel.show {
      left: 0;
    }

    .user-panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #ccc;
      margin-bottom: 15px;
    }

    .user-panel-header h3 {
      margin: 0;
      font-size: 18px;
      color: #007bff;
    }

    .user-panel-header button {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
    }

    .user-panel-body p {
      margin: 10px 0;
      font-size: 14px;
      color: #2c3e50;
    }

    /* Ajustes responsive */
    @media (max-width: 576px) {
      .titulo {
        font-size: 26px;
        margin: 20px 10px 15px 10px;
      }

      .card {
        width: 140px;
        padding: 15px;
      }

      .card img {
        width: 60px;
        margin-bottom: 10px;
      }

      .card h2 {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>

<?php include("header.php"); ?>

<h1 class="titulo">
  <i class="fas fa-map-marker-alt"></i>
  BIENVENIDO <br> SELECCIONE SU SEDE
</h1>

<main>
  <div class="container">
    <div class="card" onclick="window.location.href='usserclasses/neiva.php'">
      <img src="images/hotelF.png" alt="Ir a Neiva">
      <h2>FLORENCIA</h2>
    </div>
    <!-- Aquí puedes agregar más tarjetas de sedes -->
  </div>
</main>

<!-- Panel de usuario -->
<div id="userPanel" class="user-panel">
  <div class="user-panel-header">
    <h3>Información del Usuario</h3>
    <button onclick="cerrarPanel()">✕</button>
  </div>
  <div class="user-panel-body">
    <p><strong>Nombre:</strong> Juan Moreno</p>
    <p><strong>Correo:</strong> juan@example.com</p>
    <p><strong>Rol:</strong> Administrador</p>
  </div>
</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/javascript.js"></script>
</body>
</html>
