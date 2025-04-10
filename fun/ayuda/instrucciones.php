<?php
session_start();
$origen = $_GET['origen'] ?? '';
$titulo = "Instrucciones generales";
$contenido = "<p>No se ha identificado la página de origen correctamente.</p>";
$volver = "javascript:history.back()";

// Instrucciones según la página de origen
switch ($origen) {
    case 'ver_parcelas.php':
        $titulo = "📍 Instrucciones para Agregar / Modificar Ruta";
        $contenido = "
            <ol>
                <li>Selecciona una parcela del listado.</li>
                <li>Visualiza su forma en el mapa.</li>
                <li>Haz clic dentro de la parcela para agregar puntos de la ruta.</li>
                <li>Los puntos se enumeran automáticamente en el orden en que los marques.</li>
                <li>Para guardar la ruta, haz clic en <strong>Guardar nueva ruta</strong>.</li>
                <li>Si ya existe una ruta, se mostrará en color verde y será reemplazada si agregas una nueva.</li>
            </ol>
            <p>🔁 Puedes volver atrás para seleccionar otra parcela o salir al menú principal.</p>
        ";
        break;

    // Puedes agregar más casos para otras páginas:
    // case 'agr_parcelas.php':
    //     $titulo = "...";
    //     $contenido = "...";
    //     break;

    default:
        // Por defecto, instrucciones generales
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <link rel="stylesheet" type="text/css" href="../../css/instrucciones.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
   
</head>
<body>

<div class="contenedor">
    <h1><i class="bi bi-question-circle-fill"></i> <?= $titulo ?></h1>
    <?= $contenido ?>
    <a class="boton-volver" href="<?= $volver ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

</body>
</html>
