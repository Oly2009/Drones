<!DOCTYPE html>
<?php
    include '../lib/functiones.php';
    session_start();
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de parcelas - AgroSky</title>
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
<?php
if (isset($_SESSION['usuario'])) {
?>
    <h1>📏 MENÚ PARCELAS</h1>

    <div class="botonera">
        <a href="../fun/agregar/agr_parcelas.php" class="btn">➕ Añadir parcelas</a>
        <a href="../fun/modificar/mod_parcelas.php" class="btn">✏️ Modificar parcela</a>
        <a href="../fun/eliminar/eli_parcelas.php" class="btn">🗑️ Eliminar parcelas</a>
        <a href="../fun/listar/ver_parcelas.php" class="btn">📍 Agregar ruta</a>
        <a href="../menu/menu.php" class="btn">🔙 Volver al menú</a>
    </div>

    <div class="imagen-menu">
        <img src="../img/drones-agricultura.jpeg" alt="Drones en agricultura">
    </div>
<?php
} else {
    echo "<p class='mensaje-error'>⛔ Acceso denegado</p>";
    echo '<div class="botonera"><a href="../index.php" class="btn">🔙 Volver</a></div>';
    session_destroy();
}
?>
</body>
</html>
