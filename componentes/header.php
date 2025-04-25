<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroSky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

       <link rel="stylesheet" href="../css/style.css">

</head>

<body>
  <header class="bg-success text-white py-2">
    <div class="container-fluid d-flex align-items-center justify-content-between px-3">
      <div class="d-flex align-items-center">
        <img src="../img/logo.png" alt="AgroSky Logo" width="40" height="40" class="me-2">
        <span class="fw-bold fs-5">AgroSky</span>
      </div>

      <h1 class="h6 text-center flex-grow-1 m-0 d-none d-md-block">
  <strong>AgroSky - Cultiva desde el cielo, gestiona con inteligencia.</strong>
</h1>


      <div class="dropdown">
  <button class="btn btn-success" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-gear-fill fs-4"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="../menu/cuenta.php"><i class="bi bi-pencil-square me-2"></i>Editar perfil</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="../index.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
  </ul>
</div>
    </div>
  </header>




