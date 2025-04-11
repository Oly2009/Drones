<!DOCTYPE html>
<?php
include '../lib/functiones.php';
session_start();
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Trabajo - AgroSky</title>
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
<?php
if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
    $email = $_SESSION['usuario']['email'];
    $id_usr_query = mysqli_query($conexion, "SELECT id_usr FROM usuarios WHERE email = '$email'");
    $id_usr_row = mysqli_fetch_assoc($id_usr_query);
    $id_usr = $id_usr_row['id_usr'];

    $rolQuery = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");

    $esAdmin = false;
    $esPiloto = false;

    while ($rol = mysqli_fetch_assoc($rolQuery)) {
        if ($rol['id_rol'] == 1) $esAdmin = true;
        if ($rol['id_rol'] == 2) $esPiloto = true;
    }
?>

<h1>📋 MENÚ TRABAJO</h1>

<div class="botonera">
    <?php if ($esAdmin): ?>
        <a href="../fun/agregar/agr_trabajos.php" class="btn">➕ Añadir trabajo</a>
    <?php endif; ?>

    <?php if ($esAdmin || $esPiloto): ?>
        <a href="../fun/eliminar/eje_trabajos.php" class="btn">🚁 Ejecutar trabajo</a>
    <?php endif; ?>

    <?php if ($esAdmin): ?>
        <a href="../fun/eliminar/eli_trabajos.php" class="btn">🗑️ Eliminar trabajo</a>
        <a href="../fun/listar/lis_trabajos.php" class="btn">📑 Listar trabajos</a>
    <?php endif; ?>

    <a href="../menu/menu.php" class="btn">🔙 Volver al menú</a>
</div>

<div class="imagen-menu">
    <img src="https://cdn.computerhoy.com/sites/navi.axelspringer.es/public/media/image/2015/07/111523-piloto-drones-profesion-futuro-cursos-licencia-normas-legislacion.jpg?tf=3840x" alt="Trabajos con drones">
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
