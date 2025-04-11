<!DOCTYPE html>
<?php
include '../lib/functiones.php';
session_start();
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Drones - AgroSky</title>
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
<?php if (isset($_SESSION['usuario'])): ?>
    <h1>🚁 MENÚ DRONES</h1>

    <div class="botonera">
        <a href="../fun/agregar/agr_drones.php" class="btn">➕ Añadir drones</a>
        <a href="../fun/modificar/mod_drones.php" class="btn">⚙️ Modificar drones</a>
        <a href="../fun/eliminar/eli_drones.php" class="btn">🗑️ Eliminar drones</a>
        <a href="../fun/listar/lis_drones.php" class="btn">📋 Listar drones</a>
        <a href="../menu/menu.php" class="btn">🔙 Volver al menú</a>
    </div>

    <div class="imagen-dron">
        <img src="https://oesteyeste.com/wp-content/uploads/2019/10/dron-grabaciones-aereas.gif" alt="Dron animado">
    </div>

<?php else: ?>
    <p class="mensaje-error">⛔ Acceso denegado</p>
    <div class="botonera">
        <a href="../index.php" class="btn">🔙 Volver</a>
    </div>
    <?php session_destroy(); ?>
<?php endif; ?>

</body>
</html>
