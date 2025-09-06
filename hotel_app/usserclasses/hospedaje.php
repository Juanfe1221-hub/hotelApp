<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hospedaje</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS local -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/all.min.css"> 
    <link rel="stylesheet" href="../style/style.css"> 

    <style>
        body.bg-pagina {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding-top: 70px;
        }

        .modulo-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 2rem 1rem;
            margin: 1rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            width: 100%;
            max-width: 250px;
        }

        .modulo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .modulo-card i {
            color: #007bff;
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .modulo-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
        }

        @media (max-width: 576px) {
            .modulo-card {
                margin: 1rem auto;
                max-width: 90%;
            }
        }

     .titulo-modernizado h1 {
    font-size: 2.4rem;
    font-weight: 700;
    color: #007bff; /* mismo color que los íconos */
    margin-bottom: 2rem;
    display: inline-block;
}

.titulo-modernizado i {
    color: #007bff;
}
.modulo-card {
    background: white;
    border-radius: 15px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
    max-width: 280px;
    border: 2px solid transparent;
}

.modulo-card:hover {
    transform: translateY(-5px);
    border-color: #6f42c1;
    box-shadow: 0 12px 24px rgba(111, 66, 193, 0.2);
}

.modulo-card .icono {
    font-size: 2.8rem;
    color: #6f42c1;
    margin-bottom: 15px;
}

.modulo-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}
    </style>
</head>
<body class="bg-pagina">

  

    <div class="container">
     <div class="titulo-modernizado text-center mb-4">
  <h1><i class="fas fa-hotel me-2"></i>Gestión de Hospedaje</h1>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="modulo-card" onclick="window.location.href='administrar_habitaciones.php'">
                    <i class="fas fa-bed"></i>
                    <h3>Habitaciones</h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="modulo-card" onclick="window.location.href='reservas.php'">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Reservas</h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="modulo-card" onclick="window.location.href='adminHabi.php'">
                    <i class="fas fa-tools"></i>
                    <h3>Administrar Habitaciones</h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="modulo-card" onclick="window.location.href='piscina.php'">
                    <i class="fas fa-swimming-pool"></i>
                    <h3>Piscina</h3>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="modulo-card" onclick="window.location.href='parqueadero.php'">
                    <i class="fas fa-parking"></i>
                    <h3>Parqueadero</h3>
                </div>
            </div>
        </div>
                <div style="text-align: center; margin-top: 30px;">
                <a href="../index.php" style="
                    display: inline-block;
                    padding: 10px 25px;
                    background-color: #6c757d;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: bold;
                    transition: background-color 0.3s;
                " onmouseover="this.style.backgroundColor='#5a6268'" onmouseout="this.style.backgroundColor='#6c757d'">
                    ← Volver
                </a>
            </div>
    </div>

    <!-- JS local -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
