<?php
include '../componentes/header.php'; 

// Detectar la página actual
$paginaActual = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));

// Mapeo de nombres
$nombresPaginas = [
    'usuarios.php' => 'la página de Usuarios',
    'registro.php' => 'la página de Registro de Usuarios',
    'modificar_usuarios.php' => 'la página de Modificación de Usuarios',
    'eli_usuarios.php' => 'la página de Eliminación de Usuarios',
    'lis_usuarios.php' => 'la página de Listado de Usuarios',
    'parcelas.php' => 'la página de Parcelas',
    'mod_parcelas.php' => 'la página de Modificación de Parcelas',
    'agr_parcelas.php' => 'la página de Añadir Parcelas',
    'eli_parcelas.php' => 'la página de Eliminación de Parcelas',
    'lis_parcelas.php' => 'la página de Listado de Parcelas',
    'drones.php' => 'la página de Drones',
    'mod_drones.php' => 'la página de Modificación de Drones',
    'agr_drones.php' => 'la página de Añadir Drones',
    'eli_drones.php' => 'la página de Eliminación de Drones',
    'lis_drones.php' => 'la página de Listado de Drones',
    'trabajos.php' => 'la página de Trabajos',
    'agr_trabajos.php' => 'la página de Añadir Trabajos',
    'eli_trabajos.php' => 'la página de Eliminación de Trabajos',
    'eje_trabajos.php' => 'la página de Ejecución de Trabajos',
    'lis_trabajos.php' => 'la página de Listado de Trabajos',
    'menu.php' => 'el menú principal',
    'cuenta.php' => 'la página de Edición de Perfil',
    'ayuda.php' => 'esta página',
];

$nombrePaginaActual = $nombresPaginas[$paginaActual] ?? 'esta página';
$urlAnterior = $_SERVER['HTTP_REFERER'] ?? '../../menu/menu.php';
$nombrePaginaAnterior = $nombresPaginas[basename(parse_url($urlAnterior, PHP_URL_PATH))] ?? 'la página anterior';
?>


<body class="d-flex flex-column min-vh-100">
<main class="container py-5">
    <section class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-info text-white d-flex align-items-center">
                    <i class="bi bi-life-preserver-fill fs-5 me-2"></i>
                    <h1 class="card-title fs-5 mb-0">Instrucciones para <?=$nombrePaginaActual?></h1>
                </div>
                <div class="card-body bg-light">
                    <!-- Instrucciones dinámicas aquí dentro -->
                    <?php if ($paginaActual === 'lis_usuarios.php'): ?>
                        <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página se muestra una lista completa de todos los usuarios registrados en el sistema AgroSky.</p>
                        <hr class="my-3">
                        <h5 class="mb-3"><i class="bi bi-table me-2"></i> Información mostrada en la tabla:</h5>
                        <ul class="list-unstyled ps-3">
                            <li><i class="bi bi-person-lines-fill me-2 text-success"></i><strong>Nombre y Apellidos:</strong> El nombre completo de cada usuario.</li>
                            <li><i class="bi bi-envelope-at-fill me-2 text-primary"></i><strong>Email:</strong> La dirección de correo electrónico de cada usuario.</li>
                            <li><i class="bi bi-telephone-fill me-2 text-warning"></i><strong>Teléfono:</strong> El número de teléfono de contacto de cada usuario.</li>
                            <li><i class="bi bi-gear-fill me-2 text-secondary"></i><strong>Acciones:</strong> Botones para "Modificar" y "Eliminar" cada usuario.</li>
                        </ul>
                        <div class="alert alert-secondary d-flex align-items-center mt-4">
                            <i class="bi bi-arrow-left-short me-2"></i>
                            Utilice los botones de acción para gestionar los usuarios o vuelva al menú principal.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                            <div>
                                <h4 class="alert-heading">Información Adicional</h4>
                                <p class="mb-0">Las instrucciones detalladas para esta página se añadirán próximamente. Si tiene alguna duda, contacte con el administrador del sistema.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botón volver -->
                    <hr class="my-4">
                    <div class="text-center">
                        <a href="<?=$urlAnterior?>" class="btn btn-danger btn-lg px-4 shadow-sm">
                            <i class="bi bi-arrow-left-circle me-2"></i>Volver a <?=$nombrePaginaAnterior?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include '../componentes/footer.php'; ?>
</body>
