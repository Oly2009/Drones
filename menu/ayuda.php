<?php
// Asegúrate de que la ruta a 'header.php' sea correcta
include '../componentes/header.php';

// Detectar la página de la que viene el usuario para ofrecer ayuda específica
// Usamos HTTP_REFERER para saber de dónde venimos
$paginaActual = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$referringPage = isset($_SERVER['HTTP_REFERER']) ? basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : '';

// Mapeo de nombres de páginas para un texto más amigable
$nombresPaginas = [
    'usuarios.php' => 'la página de Gestión de Usuarios',
    'registro.php' => 'la página de Registro de Usuarios',
    'modificar_usuarios.php' => 'la página de Modificación de Usuarios',
    'eli_usuarios.php' => 'la página de Eliminación de Usuarios',
    'lis_usuarios.php' => 'la página de Listado de Usuarios',
    'parcelas.php' => 'la página de Gestión de Parcelas',
    'mod_parcelas.php' => 'la página de Modificación de Parcelas',
    'agr_parcelas.php' => 'la página de Añadir Parcelas',
    'eli_parcelas.php' => 'la página de Eliminación de Parcelas',
    'lis_parcelas.php' => 'la página de Listado de Parcelas',
    'drones.php' => 'la página de Gestión de Drones',
    'mod_drones.php' => 'la página de Modificación de Drones',
    'agr_drones.php' => 'la página de Añadir Drones',
    'eli_drones.php' => 'la página de Eliminación de Drones',
    'lis_drones.php' => 'la página de Listado de Drones',
    'trabajos.php' => 'la página de Gestión de Trabajos',
    'agr_trabajos.php' => 'la página de Asignación de Trabajos', 
    'eli_trabajos.php' => 'la página de Eliminación de Trabajos',
    'eje_trabajos.php' => 'la página de Ejecución de Trabajos',
    'lis_trabajos.php' => 'la página de Listado de Trabajos ', 
    'menu.php' => 'el menú principal',
    'cuenta.php' => 'la página de Edición de Perfil',
    'ayuda.php' => 'esta página de Ayuda',
];

// Si la página actual es 'ayuda.php', el nombre de la página de la que venimos será el que se muestre en el título
$nombrePaginaAyuda = $nombresPaginas[$referringPage] ?? 'AgroSky';
$urlAnterior = $_SERVER['HTTP_REFERER'] ?? '../../menu/menu.php';
$nombrePaginaAnterior = $nombresPaginas[basename(parse_url($urlAnterior, PHP_URL_PATH))] ?? 'el menú principal';
?>

<body class="d-flex flex-column min-vh-100">
<main class="container py-5 flex-grow-1">
    <section class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-info text-white d-flex align-items-center">
                    <i class="bi bi-life-preserver-fill fs-5 me-2"></i>
                    <h1 class="card-title fs-5 mb-0">Instrucciones para <?=$nombrePaginaAyuda?></h1>
                </div>
                <div class="card-body bg-light">
                    <?php
                    // Usar $referringPage para mostrar la ayuda específica de la página anterior
                    switch ($referringPage) {
                        case 'usuarios.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta es la sección de **Gestión de Usuarios**, donde los administradores pueden añadir, modificar o eliminar usuarios del sistema AgroSky.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-card-checklist me-2"></i> Opciones disponibles:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-person-plus-fill me-2 text-success"></i><strong>Añadir Usuario:</strong> Permite crear nuevas cuentas de usuario, asignando sus datos personales y rol (Administrador o Piloto).</li>
                                <li><i class="bi bi-pencil-square me-2 text-primary"></i><strong>Modificar Usuario:</strong> Accede al listado de usuarios para editar su información existente o cambiar su rol.</li>
                                <li><i class="bi bi-person-dash-fill me-2 text-danger"></i><strong>Eliminar Usuario:</strong> Accede al listado de usuarios para eliminar definitivamente una cuenta.</li>
                                <li><i class="bi bi-list-columns-reverse me-2 text-info"></i><strong>Listar Usuarios:</strong> Muestra una tabla con todos los usuarios registrados y sus detalles.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Seleccione la acción deseada para gestionar los usuarios de AgroSky.
                            </div>
                            <?php
                            break;
                        case 'registro.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página, los administradores pueden **registrar nuevos usuarios** en el sistema AgroSky. Asegúrese de completar todos los campos obligatorios.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-person-fill-add me-2"></i> Instrucciones de registro:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-asterisk me-2 text-danger"></i>Todos los campos marcados con un asterisco son obligatorios.</li>
                                <li><i class="bi bi-key-fill me-2 text-warning"></i>La contraseña debe ser segura (se recomienda usar combinaciones de letras, números y símbolos).</li>
                                <li><i class="bi bi-shield-lock-fill me-2 text-info"></i>Asigne el rol adecuado (Administrador o Piloto) al nuevo usuario.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Una vez rellenados los datos, haga clic en "Registrar" para crear la nueva cuenta.
                            </div>
                            <?php
                            break;
                        case 'modificar_usuarios.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Aquí puede **modificar los datos de un usuario existente**. Puede editar su nombre, apellidos, teléfono, correo electrónico y su rol dentro del sistema.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-pencil-square me-2"></i> Pasos para modificar:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-search me-2 text-info"></i>Desde el listado de usuarios, seleccione el usuario que desea modificar haciendo clic en el botón "Modificar" asociado.</li>
                                <li><i class="bi bi-pencil-fill me-2 text-primary"></i>Actualice los campos que necesite cambiar.</li>
                                <li><i class="bi bi-arrow-clockwise me-2 text-success"></i>Haga clic en "Actualizar" para guardar los cambios.</li>
                            </ul>
                            <div class="alert alert-warning d-flex align-items-center mt-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Tenga cuidado al modificar la información del usuario, especialmente el rol, ya que afecta a sus permisos en el sistema.
                            </div>
                            <?php
                            break;
                        case 'eli_usuarios.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta sección permite **eliminar usuarios** del sistema AgroSky. Tenga en cuenta que esta acción es irreversible.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-person-x-fill me-2"></i> Proceso de eliminación:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-list-ul me-2 text-info"></i>Acceda a esta página desde el listado de usuarios, haciendo clic en el botón "Eliminar" de la fila correspondiente.</li>
                                <li><i class="bi bi-question-circle-fill me-2 text-warning"></i>Se le pedirá una confirmación antes de eliminar el usuario.</li>
                                <li><i class="bi bi-trash-fill me-2 text-danger"></i>Una vez confirmado, el usuario y toda su información asociada serán eliminados del sistema.</li>
                            </ul>
                            <div class="alert alert-danger d-flex align-items-center mt-4">
                                <i class="bi bi-hazard me-2"></i>
                                ¡ATENCIÓN! La eliminación de un usuario es una acción permanente y no se puede deshacer.
                            </div>
                            <?php
                            break;
                        case 'lis_usuarios.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página se muestra una lista completa de todos los usuarios registrados en el sistema AgroSky.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-table me-2"></i> Información mostrada en la tabla:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-person-lines-fill me-2 text-success"></i><strong>Nombre y Apellidos:</strong> El nombre completo de cada usuario.</li>
                                <li><i class="bi bi-envelope-at-fill me-2 text-primary"></i><strong>Email:</strong> La dirección de correo electrónico de cada usuario.</li>
                                <li><i class="bi bi-telephone-fill me-2 text-warning"></i><strong>Teléfono:</strong> El número de teléfono de contacto de cada usuario.</li>
                                <li><i class="bi bi-person-rolodex me-2 text-info"></i><strong>Roles:</strong> Los roles asignados a cada usuario (Administrador, Piloto).</li>
                                <li><i class="bi bi-gear-fill me-2 text-secondary"></i><strong>Acciones:</strong> Botones para "Modificar" y "Eliminar" cada usuario.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Utilice los botones de acción para gestionar los usuarios o vuelva al menú principal.
                            </div>
                            <?php
                            break;
                        case 'parcelas.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta es la sección de **Gestión de Parcelas**, donde los administradores pueden añadir, modificar o eliminar parcelas agrícolas del sistema AgroSky.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-geo-alt-fill me-2"></i> Opciones disponibles:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-plus-square-fill me-2 text-success"></i><strong>Añadir Parcela:</strong> Permite registrar nuevas parcelas, definiendo su ubicación y características.</li>
                                <li><i class="bi bi-map-fill me-2 text-primary"></i><strong>Modificar Parcela:</strong> Accede al listado de parcelas para editar la información de una parcela existente.</li>
                                <li><i class="bi bi-trash-fill me-2 text-danger"></i><strong>Eliminar Parcela:</strong> Accede al listado de parcelas para eliminar una parcela del sistema.</li>
                                <li><i class="bi bi-list-ul me-2 text-info"></i><strong>Listar Parcelas:</strong> Muestra una tabla con todas las parcelas registradas.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Seleccione la acción deseada para gestionar sus parcelas.
                            </div>
                            <?php
                            break;
                        case 'mod_parcelas.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página puede **modificar los detalles de una parcela** previamente registrada. Esto incluye su nombre, ubicación, tipo de cultivo, área y observaciones.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-pencil-square me-2"></i> Pasos para modificar una parcela:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-cursor-fill me-2 text-info"></i>Seleccione la parcela a modificar desde el listado de parcelas.</li>
                                <li><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Actualice los campos de información necesarios, como la ubicación en el mapa, el tipo de cultivo o las observaciones.</li>
                                <li><i class="bi bi-save-fill me-2 text-success"></i>Haga clic en "Guardar cambios" para aplicar las modificaciones.</li>
                            </ul>
                            <div class="alert alert-warning d-flex align-items-center mt-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                La modificación de la ubicación puede afectar las rutas de vuelo existentes.
                            </div>
                            <?php
                            break;
                        case 'agr_parcelas.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Utilice esta página para **añadir nuevas parcelas agrícolas** al sistema AgroSky. Podrá definir su ubicación exacta, dimensiones y el tipo de cultivo.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-plus-circle-fill me-2"></i> Cómo añadir una parcela:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-map-fill me-2 text-primary"></i>Utilice el mapa interactivo para localizar la parcela. Puede buscar una ubicación o arrastrar el marcador.</li>
                                <li><i class="bi bi-rulers me-2 text-info"></i>Introduzca el nombre de la parcela, el tipo de cultivo y el área en metros cuadrados.</li>
                                <li><i class="bi bi-upload me-2 text-success"></i>Adjunte un archivo GeoJSON si dispone de un contorno preciso de la parcela.</li>
                                <li><i class="bi bi-check-all me-2 text-success"></i>Haga clic en "Insertar parcela" para guardarla en el sistema.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                Una vez creada, podrá asignar trabajos a esta parcela.
                            </div>
                            <?php
                            break;
                        case 'eli_parcelas.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta sección permite **eliminar parcelas** del sistema. Tenga en cuenta que al eliminar una parcela, se eliminarán también todas las rutas y trabajos asociados a ella.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-trash-fill me-2"></i> Proceso de eliminación de parcelas:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-list-ul me-2 text-info"></i>Acceda a esta página desde el listado de parcelas, haciendo clic en el botón "Eliminar" de la fila correspondiente.</li>
                                <li><i class="bi bi-exclamation-octagon-fill me-2 text-danger"></i>Se le pedirá una confirmación debido a la naturaleza irreversible de esta acción y sus implicaciones.</li>
                                <li><i class="bi bi-check-circle-fill me-2 text-success"></i>Confirme la eliminación para proceder.</li>
                            </ul>
                            <div class="alert alert-danger d-flex align-items-center mt-4">
                                <i class="bi bi-fire me-2"></i>
                                ¡ADVERTENCIA! La eliminación de una parcela es permanente y también eliminará sus trabajos y rutas asociadas.
                            </div>
                            <?php
                            break;
                        case 'lis_parcelas.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página encontrará un **listado de todas las parcelas agrícolas** registradas en AgroSky, con información detallada sobre cada una.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-table me-2"></i> Detalles de la tabla de parcelas:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-tag-fill me-2 text-primary"></i><strong>Nombre:</strong> El nombre identificativo de la parcela.</li>
                                <li><i class="bi bi-geo-alt-fill me-2 text-success"></i><strong>Ubicación:</strong> La dirección o descripción de la ubicación de la parcela.</li>
                                <li><i class="bi bi-seed-fill me-2 text-info"></i><strong>Tipo de Cultivo:</strong> El cultivo que se gestiona en esa parcela.</li>
                                <li><i class="bi bi-rulers me-2 text-warning"></i><strong>Área (m²):</strong> El tamaño de la parcela en metros cuadrados.</li>
                                <li><i class="bi bi-calendar-check-fill me-2 text-muted"></i><strong>Fecha de Registro:</strong> Cuándo se añadió la parcela al sistema.</li>
                                <li><i class="bi bi-pencil-square me-2 text-secondary"></i><strong>Acciones:</strong> Opciones para "Modificar" y "Eliminar" cada parcela.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-eye-fill me-2"></i>
                                Revise los detalles de sus parcelas o realice acciones de gestión.
                            </div>
                            <?php
                            break;
                        case 'drones.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta es la sección de **Gestión de Drones**, donde puede añadir, modificar o eliminar drones de su flota, y ver su estado actual.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-drone-fill me-2"></i> Opciones de gestión de drones:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-plus-square-fill me-2 text-success"></i><strong>Añadir Dron:</strong> Registre nuevos drones en el sistema, con sus especificaciones.</li>
                                <li><i class="bi bi-pencil-square me-2 text-primary"></i><strong>Modificar Dron:</strong> Acceda al listado de drones para editar la información de un dron existente.</li>
                                <li><i class="bi bi-trash-fill me-2 text-danger"></i><strong>Eliminar Dron:</strong> Acceda al listado de drones para dar de baja un dron de su flota.</li>
                                <li><i class="bi bi-list-ul me-2 text-info"></i><strong>Listar Drones:</strong> Muestra una tabla con todos los drones registrados y su información.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Gestiona la disponibilidad y los datos de tu flota de drones.
                            </div>
                            <?php
                            break;
                        case 'mod_drones.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página puede **modificar los detalles de un dron** registrado, como su marca, modelo, número de serie, tipo y estado.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-pencil-square me-2"></i> Cómo modificar un dron:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-cursor-fill me-2 text-info"></i>Seleccione el dron a modificar desde el listado de drones.</li>
                                <li><i class="bi bi-gear-fill me-2 text-primary"></i>Actualice los campos de información que desee cambiar.</li>
                                <li><i class="bi bi-save-fill me-2 text-success"></i>Haga clic en "Guardar cambios" para aplicar las modificaciones.</li>
                            </ul>
                            <div class="alert alert-warning d-flex align-items-center mt-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                El estado del dron es crucial para la asignación de trabajos.
                            </div>
                            <?php
                            break;
                        case 'agr_drones.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Utilice esta página para **añadir nuevos drones** a su flota en el sistema AgroSky. Ingrese los datos técnicos del dron y su estado inicial.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-plus-circle-fill me-2"></i> Cómo añadir un dron:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-robot me-2 text-primary"></i>Introduzca la marca, modelo y número de serie del dron.</li>
                                <li><i class="bi bi-battery-charging me-2 text-info"></i>Seleccione el tipo de energía del dron (eléctrico, gasolina, híbrido, otro).</li>
                                <li><i class="bi bi-check-all me-2 text-success"></i>Haga clic en "Añadir Dron" para registrarlo en el sistema.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                Los drones deben estar "disponibles" para ser asignados a trabajos.
                            </div>
                            <?php
                            break;
                        case 'eli_drones.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta sección permite **eliminar drones** de su flota en el sistema. Esta acción es irreversible.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-trash-fill me-2"></i> Proceso de eliminación de drones:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-list-ul me-2 text-info"></i>Acceda a esta página desde el listado de drones, haciendo clic en el botón "Eliminar" de la fila correspondiente.</li>
                                <li><i class="bi bi-exclamation-octagon-fill me-2 text-danger"></i>Se le pedirá una confirmación debido a la naturaleza irreversible de esta acción.</li>
                                <li><i class="bi bi-check-circle-fill me-2 text-success"></i>Confirme la eliminación para dar de baja el dron.</li>
                            </ul>
                            <div class="alert alert-danger d-flex align-items-center mt-4">
                                <i class="bi bi-fire me-2"></i>
                                ¡ADVERTENCIA! La eliminación de un dron es permanente.
                            </div>
                            <?php
                            break;
                        case 'lis_drones.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página se muestra un **listado completo de todos los drones** registrados en su flota AgroSky, con información detallada de cada uno.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-table me-2"></i> Información de la tabla de drones:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-tag-fill me-2 text-primary"></i><strong>Marca y Modelo:</strong> La marca y modelo del dron.</li>
                                <li><i class="bi bi-fingerprint me-2 text-success"></i><strong>Número de Serie:</strong> El identificador único del dron.</li>
                                <li><i class="bi bi-battery-half me-2 text-info"></i><strong>Tipo:</strong> El tipo de energía que utiliza el dron (eléctrico, gasolina, híbrido, otro).</li>
                                <li><i class="bi bi-check-circle-fill me-2 text-warning"></i><strong>Estado:</strong> El estado actual del dron (disponible, en uso, en reparación, fuera de servicio).</li>
                                <li><i class="bi bi-airplane-fill me-2 text-muted"></i><strong>Nº Vuelos:</strong> El número total de vuelos completados por el dron.</li>
                                <li><i class="bi bi-gear-fill me-2 text-secondary"></i><strong>Acciones:</strong> Botones para "Modificar" y "Eliminar" cada dron.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-eye-fill me-2"></i>
                                Revise el estado de su flota o realice acciones de mantenimiento.
                            </div>
                            <?php
                            break;
                        case 'trabajos.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta es la sección de **Gestión de Trabajos**, donde los administradores pueden asignar nuevas tareas a drones y pilotos, y supervisar el progreso de los trabajos.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-clipboard-check-fill me-2"></i> Opciones disponibles:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-plus-square-fill me-2 text-success"></i><strong>Asignar Trabajo:</strong> Cree un nuevo trabajo, seleccionando una parcela, un dron, un piloto y las tareas a realizar.</li>
                                <li><i class="bi bi-play-circle-fill me-2 text-primary"></i><strong>Ejecutar Trabajos:</strong> Accede a la lista de trabajos pendientes o en curso para iniciarlos o reanudarlos. (Para pilotos)</li>
                                <li><i class="bi bi-list-ul me-2 text-info"></i><strong>Listar Trabajos Finalizados:</strong> Muestra un histórico de todos los trabajos completados.</li>
                                <li><i class="bi bi-trash-fill me-2 text-danger"></i><strong>Eliminar Trabajo:</strong> Permite cancelar y eliminar trabajos (solo para administradores, en trabajos pendientes).</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Planifique y gestione las operaciones aéreas de su agricultura.
                            </div>
                            <?php
                            break;
                        case 'agr_trabajos.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En esta página puede **asignar nuevos trabajos** a su flota de drones y pilotos. Seleccione la parcela, el dron, el piloto y las tareas específicas para el trabajo.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-journal-plus-fill me-2"></i> Pasos para asignar un trabajo:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-map-fill me-2 text-primary"></i>Seleccione la parcela donde se realizará el trabajo.</li>
                                <li><i class="bi bi-drone-fill me-2 text-info"></i>Elija un dron disponible de su flota.</li>
                                <li><i class="bi bi-person-circle me-2 text-success"></i>Asigne el trabajo a un piloto registrado.</li>
                                <li><i class="bi bi-list-task me-2 text-warning"></i>Seleccione una o varias tareas que el dron realizará en esta operación (ej. Sembrar, Abonar, Fumigar).</li>
                                <li><i class="bi bi-check-all me-2 text-success"></i>Haga clic en "Asignar Trabajo" para programarlo.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                Los trabajos asignados aparecerán en la sección "Ejecutar Trabajos" para los pilotos.
                            </div>
                            <?php
                            break;
                        case 'eli_trabajos.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta sección permite **eliminar trabajos** del sistema. Esta acción suele ser realizada por administradores y solo para trabajos que aún no han comenzado (pendientes).</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-x-octagon-fill me-2"></i> Proceso de eliminación de trabajos:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-list-ul me-2 text-info"></i>Acceda a esta página desde el listado de trabajos (si hay una opción de eliminación directa para administradores), o desde la gestión de trabajos pendientes.</li>
                                <li><i class="bi bi-question-circle-fill me-2 text-warning"></i>Se le pedirá una confirmación antes de eliminar el trabajo.</li>
                                <li><i class="bi bi-trash-fill me-2 text-danger"></i>Una vez confirmado, el trabajo se cancelará y eliminará.</li>
                            </ul>
                            <div class="alert alert-danger d-flex align-items-center mt-4">
                                <i class="bi bi-fire me-2"></i>
                                ¡ADVERTENCIA! Eliminar un trabajo es una acción permanente y solo se recomienda para trabajos pendientes o cancelados.
                            </div>
                            <?php
                            break;
                        case 'eje_trabajos.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Esta página permite a los **pilotos ejecutar y supervisar los trabajos** asignados. Verá una lista de trabajos pendientes o en curso que puede iniciar.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-play-circle-fill me-2"></i> Cómo ejecutar un trabajo:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-list-task me-2 text-info"></i>Localice el trabajo que desea iniciar en la tabla.</li>
                                <li><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Haga clic en el botón "Ejecutar" para iniciar la simulación del vuelo.</li>
                                <li><i class="bi bi-map-fill me-2 text-success"></i>Durante la ejecución, se mostrará un mapa con la ruta simulada del dron y su progreso.</li>
                                <li><i class="bi bi-check-circle-fill me-2 text-warning"></i>Una vez completada la ruta, el trabajo se marcará automáticamente como "Finalizado" y el número de vuelos del dron se actualizará.</li>
                            </ul>
                            <div class="alert alert-info d-flex align-items-center mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                Si un trabajo está "en curso" y necesita reanudarse (por ejemplo, por una interrupción), puede volver a ejecutarlo desde esta misma página.
                            </div>
                            <?php
                            break;
                        case 'lis_trabajos.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Aquí se presenta un **listado de todos los trabajos finalizados** en el sistema AgroSky. Podrá consultar los detalles de las operaciones completadas.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-table me-2"></i> Información de la tabla de trabajos finalizados:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-calendar-check-fill me-2 text-primary"></i><strong>Fecha de Asignación:</strong> Cuándo se programó el trabajo.</li>
                                <li><i class="bi bi-calendar-check-fill me-2 text-success"></i><strong>Fecha de Ejecución:</strong> Cuándo se finalizó el trabajo en el campo.</li>
                                <li><i class="bi bi-map-fill me-2 text-info"></i><strong>Parcela:</strong> El nombre de la parcela donde se realizó el trabajo.</li>
                                <li><i class="bi bi-drone-fill me-2 text-warning"></i><strong>Dron:</strong> El dron utilizado para la operación.</li>
                                <li><i class="bi bi-person-fill me-2 text-muted"></i><strong>Piloto:</strong> El piloto que ejecutó el trabajo.</li>
                                <li><i class="bi bi-list-task me-2 text-secondary"></i><strong>Tarea(s):</strong> Las tareas realizadas durante el trabajo (ej. Sembrar, Fumigar).</li>
                                <li><i class="bi bi-bar-chart-line-fill me-2 text-success"></i><strong>Estado:</strong> Siempre será "Finalizado" en esta sección.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-graph-up me-2"></i>
                                Consulte el historial de sus operaciones agrícolas.
                            </div>
                            <?php
                            break;
                        case 'menu.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Este es el **menú principal** de AgroSky. Desde aquí, puede acceder a todas las funcionalidades del sistema según su rol (Administrador o Piloto).</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-grid-fill me-2"></i> Opciones del menú:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-person-fill-gear me-2 text-primary"></i><strong>Gestión de Usuarios:</strong> (Solo Administradores) Para añadir, modificar o listar usuarios.</li>
                                <li><i class="bi bi-map-fill me-2 text-success"></i><strong>Gestión de Parcelas:</strong> (Solo Administradores) Para añadir, modificar o listar parcelas.</li>
                                <li><i class="bi bi-drone-fill me-2 text-info"></i><strong>Gestión de Drones:</strong> (Solo Administradores) Para añadir, modificar o listar drones.</li>
                                <li><i class="bi bi-clipboard-check-fill me-2 text-warning"></i><strong>Gestión de Trabajos:</strong> (Ambos roles) Para asignar, ejecutar o listar trabajos.</li>
                                <li><i class="bi bi-person-circle me-2 text-muted"></i><strong>Mi Cuenta:</strong> Para editar su información de perfil.</li>
                                <li><i class="bi bi-question-circle-fill me-2 text-secondary"></i><strong>Ayuda:</strong> Accede a esta página de instrucciones.</li>
                                <li><i class="bi bi-box-arrow-right me-2 text-danger"></i><strong>Cerrar Sesión:</strong> Para salir de su cuenta.</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-hand-index-fill me-2"></i>
                                Navegue por el sistema para gestionar sus operaciones agrícolas.
                            </div>
                            <?php
                            break;
                        case 'cuenta.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> En la página de **Edición de Perfil**, puede ver y actualizar su información personal registrada en el sistema AgroSky, incluyendo su contraseña.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-person-gear me-2"></i> Cómo editar su perfil:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-pencil-fill me-2 text-primary"></i>Puede modificar su nombre, apellidos, teléfono y correo electrónico.</li>
                                <li><i class="bi bi-key-fill me-2 text-warning"></i>Para cambiar su contraseña, ingrese la nueva contraseña en los campos correspondientes.</li>
                                <li><i class="bi bi-save-fill me-2 text-success"></i>Haga clic en "Actualizar Perfil" para guardar los cambios.</li>
                            </ul>
                            <div class="alert alert-info d-flex align-items-center mt-4">
                                <i class="bi bi-shield-fill-check me-2"></i>
                                Es importante mantener su información de contacto actualizada.
                            </div>
                            <?php
                            break;
                        case 'ayuda.php':
                            ?>
                            <p class="lead"><i class="bi bi-info-circle-fill me-2"></i> Está usted en la **página de Ayuda**. Aquí encontrará instrucciones detalladas sobre el uso de cada sección de la plataforma AgroSky.</p>
                            <hr class="my-3">
                            <h5 class="mb-3"><i class="bi bi-lightbulb-fill me-2"></i> Cómo usar la ayuda:</h5>
                            <ul class="list-unstyled ps-3">
                                <li><i class="bi bi-arrow-return-left me-2 text-primary"></i>Esta página detecta desde qué sección de AgroSky ha llegado y le muestra la ayuda específica para esa función.</li>
                                <li><i class="bi bi-question-diamond-fill me-2 text-info"></i>Si tiene dudas sobre una sección en particular, navegue a esa sección y luego haga clic en el botón de "Ayuda" (normalmente en el menú o pie de página).</li>
                            </ul>
                            <div class="alert alert-secondary d-flex align-items-center mt-4">
                                <i class="bi bi-arrow-left-short me-2"></i>
                                Utilice el botón inferior para volver a la página desde la que accedió a la ayuda.
                            </div>
                            <?php
                            break;
                        default:
                            // Mensaje genérico si no se encuentra la página específica o si se accede directamente a ayuda.php sin referer claro
                            ?>
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                                <div>
                                    <h4 class="alert-heading">Información Adicional</h4>
                                    <p class="mb-0">Las instrucciones detalladas para esta página se añadirán próximamente o no se ha podido detectar la página de origen. Si tiene alguna duda, contacte con el administrador del sistema.</p>
                                </div>
                            </div>
                            <?php
                            break;
                    }
                    ?>

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
</html>