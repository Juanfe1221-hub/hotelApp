<?php
// Verificar sesión
require_once __DIR__ . '/includes/check_session.php';
?>
<header class="main-header">
  <div class="logo">
    <a href="index.php" class="logo-text">HOTEL ECOTURÍSTICO <span>EL PARAÍSO</span></a>
  </div>
  <div class="acciones">
    <a href="index.php" title="Inicio" class="btn-header">
      <i class="fas fa-home"></i>
    </a>
    <a href="#" class="btn-header">
      <i class="fas fa-user"></i> <span><?php echo $_SESSION['nombres']; ?></span>
    </a>
    <a href="includes/cerrarSesion.php" title="Cerrar sesión" class="btn-header logout">
      <i class="fas fa-power-off"></i>
    </a>
  </div>
</header>

<style>
    /* VARIABLES */
    :root {
        --primary-color: #1e3a8a;    /* Azul profundo */
        --secondary-color: #ffffff;  /* Blanco */
        --accent-color: #ffdd57;     /* Amarillo */
        --hover-color: #2563eb;      /* Azul hover */
        --font-family: 'Segoe UI', Arial, sans-serif;
    }

    /* HEADER */
    .main-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--primary-color);
        padding: 12px 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        font-family: var(--font-family);
        color: var(--secondary-color);
        height: 70px;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    /* TEXTO DEL LOGO */
    .logo-text {
        font-size: 22px;
        font-weight: 700;
        color: var(--secondary-color);
        text-decoration: none;
        letter-spacing: 1px;
        transition: color 0.3s ease;
    }

    .logo-text span {
        color: var(--accent-color);
    }

    .logo-text:hover {
        color: var(--accent-color);
    }

    /* ACCIONES */
    .acciones {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* BOTONES HEADER */
    .btn-header {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        color: var(--secondary-color);
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease-in-out;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .btn-header i {
        font-size: 18px;
    }

    /* HOVER DE LOS BOTONES */
    .btn-header:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-3px);
        box-shadow: 0 5px 12px rgba(0, 0, 0, 0.3);
    }

    /* BOTÓN CERRAR SESIÓN DIFERENTE */
    .btn-header.logout {
        background: #dc2626;
    }

    .btn-header.logout:hover {
        background: #b91c1c;
        color: #fff;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .main-header {
            padding: 10px 15px;
            height: 60px;
        }

        .logo-text {
            font-size: 18px;
        }

        .acciones {
            gap: 0.5rem;
        }

        .btn-header {
            padding: 6px 10px;
            font-size: 13px;
        }

        .btn-header i {
            font-size: 16px;
        }

        .btn-header span {
            display: none;
        }
    }
</style>
