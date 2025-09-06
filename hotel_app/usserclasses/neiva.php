<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #1e3a8a;   /* Azul principal */
            --secondary-color: #ffffff; /* Blanco */
            --accent-color: #ffb703;    /* Amarillo dorado */
            --hover-color: #2563eb;     /* Azul hover */
            --text-color: #f8fafc;      /* Texto claro */
            --card-bg: rgba(255, 255, 255, 0.9);
            --font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        /* FONDO CON IMAGEN Y OVERLAY */
        body {
            margin: 0;
            font-family: var(--font-family);
            background: url("../images/logo.svg") no-repeat center center/cover;
            position: relative;
            min-height: 100vh;
        }

        /* Overlay oscuro para contraste */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
            z-index: -1;
        }

        /* TÍTULO PRINCIPAL */
        .titulo-servicios-moderno {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 25px 0 1.5rem 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
        }

        .titulo-servicios-moderno i {
            color: var(--accent-color);
            margin-right: 10px;
            animation: bounce 1.5s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-8px); }
            60% { transform: translateY(-4px); }
        }

        /* CONTENEDOR PRINCIPAL */
        .main-content-area {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 20px;
            min-height: 100vh;
        }

        /* TARJETAS DE SERVICIOS */
        .servicios-card-moderno {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2rem 1rem;
            margin: 1rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
            cursor: pointer;
            min-width: 180px;
            max-width: 280px;
            word-wrap: break-word;
            border-top: 5px solid var(--primary-color);
        }

        .servicios-card-moderno:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        /* ÍCONOS */
        .servicios-card-moderno i {
            color: var(--primary-color);
            font-size: 4.5rem;
            margin-bottom: 1rem;
            display: inline-block;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.3);
            transition: color 0.3s ease;
        }

        .servicios-card-moderno:hover i {
            color: var(--accent-color);
        }

        .servicios-card-moderno h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-top: 0.5rem;
            line-height: 1.4;
            white-space: normal;
        }

        /* BOTÓN VOLVER */
        .btn-volver-inicio {
            position: fixed;
            bottom: 20px;
            right: 20px;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            z-index: 1050;
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
        }

        .btn-volver-inicio:hover {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }

        /* RESPONSIVE */
        @media (max-width: 576px) {
            .titulo-servicios-moderno {
                font-size: 2.2rem;
                margin-bottom: 1rem;
            }

            .servicios-card-moderno {
                min-width: 140px;
                max-width: 200px;
                padding: 1.5rem 1rem;
            }

            .servicios-card-moderno i {
                font-size: 3.5rem;
            }

            .servicios-card-moderno h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>
    <main class="main-content-area">
        <h1 class="titulo-servicios-moderno">
            <i class="fas fa-concierge-bell"></i> Servicios
        </h1>

        <div class="row justify-content-center mt-4">
            <div class="col-auto">
                <div class="servicios-card-moderno" onclick="window.location.href='cafeteria.php'">
                    <i class="fas fa-coffee"></i>
                    <h2>CAFETERÍA</h2>
                </div>
            </div>
            <div class="col-auto">
                <div class="servicios-card-moderno" onclick="window.location.href='hospedaje.php'">
                    <i class="fas fa-bed"></i>
                    <h2>HOSPEDAJE</h2>
                </div>
            </div>
        </div>
    </main>

    <!-- Botón Volver al inicio -->
    <button class="btn-volver-inicio" onclick="window.location.href='../index.php'">
        <i class="fas fa-arrow-left"></i> Inicio
    </button>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
