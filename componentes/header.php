<?php
// Ruta base según ubicación
$rutaBase = str_contains($_SERVER['PHP_SELF'], '/fun/') ? '../../' : (str_contains($_SERVER['PHP_SELF'], '/menu/') ? '../' : './');

// Mapeo de archivos a secciones
$mapaSecciones = [
    'usuarios.php'             => 'Usuarios',
    'registro.php'             => 'Usuarios',
    'modificar_usuarios.php'    => 'Usuarios',
    'eli_usuarios.php'         => 'Usuarios',
    'lis_usuarios.php'         => 'Usuarios',
    'parcelas.php'             => 'Parcelas',
    'mod_parcelas.php'         => 'Parcelas',
    'agr_parcelas.php'         => 'Parcelas',
    'eli_parcelas.php'         => 'Parcelas',
    'lis_parcelas.php'         => 'Parcelas',
    'drones.php'               => 'Drones',
    'mod_drones.php'           => 'Drones',
    'agr_drones.php'           => 'Drones',
    'eli_drones.php'           => 'Drones',
    'lis_drones.php'           => 'Drones',
    'trabajos.php'             => 'Trabajos',
    'agr_trabajos.php'         => 'Trabajos',
    'eli_trabajos.php'         => 'Trabajos',
    'eje_trabajos.php'         => 'Trabajos',
    'lis_trabajos.php'         => 'Trabajos',
];

// Detectar archivo actual
$archivoActual = basename($_SERVER['PHP_SELF']);
$seccion = '';
$pagina = '';

// Asignar sección
if (isset($mapaSecciones[$archivoActual])) {
    $seccion = $mapaSecciones[$archivoActual];
}

// Asignar tipo de página
switch (true) {
    case str_starts_with($archivoActual, 'agr_'):
        $pagina = 'Añadir';
        break;
    case str_starts_with($archivoActual, 'mod_'):
        $pagina = 'Modificar';
        break;
    case str_starts_with($archivoActual, 'eli_'):
        $pagina = 'Eliminar';
        break;
    case str_starts_with($archivoActual, 'lis_'):
        $pagina = 'Listar';
        break;
    case str_starts_with($archivoActual, 'eje_'):
        $pagina = 'Ejecutar';
        break;
    case str_starts_with($archivoActual, 'ver_'):
        $pagina = 'Ver';
        break;
    case $archivoActual === 'registro.php':
        $pagina = 'Alta';
        break;
    case $archivoActual === 'menu.php':
        $pagina = 'Inicio';
        break;
    default:
        $pagina = ucfirst(str_replace(['_', '.php'], [' ', ''], $archivoActual));
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgroSky</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <link rel="stylesheet" href="<?= $rutaBase ?>css/style.css">
</head>

<body>
<header class="bg-success text-white py-2">
    <div class="container-fluid d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center">
            <img src="<?= $rutaBase ?>img/logo.png" alt="AgroSky Logo" width="40" height="40" class="me-2">
            <span class="fw-bold fs-5">AgroSky</span>
        </div>

        <h1 class="h6 text-center flex-grow-1 m-0 d-none d-md-block">
            <strong>AgroSky - Cultiva desde el cielo, gestiona con inteligencia.</strong>
        </h1>

        <div class="d-flex align-items-center">
            <a href="<?= $rutaBase ?>menu/ayuda.php" class="text-white me-2" title="Ayuda">
                <i class="bi bi-question-circle-fill fs-4"></i>
            </a>
            <div class="dropdown">
                <button class="btn btn-success" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear-fill fs-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= $rutaBase ?>menu/cuenta.php"><i class="bi bi-pencil-square me-2"></i>Editar perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= $rutaBase ?>index.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<?php if ($pagina !== 'Inicio'): ?>
    <nav aria-label="breadcrumb" class="bg-light border-bottom py-2">
        <div class="container d-flex justify-content-center">
            <ol class="breadcrumb mb-0 text-center">
                <li class="breadcrumb-item"><a href="<?= $rutaBase ?>menu/menu.php" class="text-success">Inicio</a></li>
                <?php if ($seccion): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= $rutaBase ?>menu/<?= strtolower($seccion) ?>.php" class="text-success"><?= $seccion ?></a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active fw-semibold text-success" aria-current="page"><?= $pagina ?></li>
            </ol>
        </div>
    </nav>
<?php endif; ?>

