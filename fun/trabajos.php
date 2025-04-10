<?php
include '../lib/functiones.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú de Trabajo - AgroSky</title>
    <link rel="stylesheet" type="text/css" href="../css/trabajos.css">
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
    $esAgricultor = false;
    $esPiloto = false;

    while ($rol = mysqli_fetch_assoc($rolQuery)) {
        if ($rol['id_rol'] == 1) $esAdmin = true;
        if ($rol['id_rol'] == 2) $esPiloto = true;
        if ($rol['id_rol'] == 3) $esAgricultor = true;
    }
?>

    <h1>📋 MENÚ TRABAJO</h1>

    <div class="botonera">
        <?php if ($esAdmin || $esAgricultor || $esPiloto): ?>
            <a href="agregar/agr_trabajos.php" class="btn">➕ Añadir trabajo</a>
            <a href="eliminar/eje_trabajos.php" class="btn">🚁 Ejecutar trabajo</a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
            <a href="eliminar/eli_trabajos.php" class="btn">🗑️ Eliminar trabajo</a>
            <a href="listar/lis_trabajos.php" class="btn">📑 Listar trabajos</a>
        <?php endif; ?>

        <a href="../menu.php" class="btn">🔙 Volver al menú</a>
    </div>

    <div class="imagen">
        <img src="https://cdn.computerhoy.com/sites/navi.axelspringer.es/public/media/image/2015/07/111523-piloto-drones-profesion-futuro-cursos-licencia-normas-legislacion.jpg?tf=3840x" alt="Trabajos con drones">
    </div>

<?php
} else {
    echo "<p>⛔ Acceso denegado</p>";
    echo '<a href="../login.php"><button>Volver</button></a>';
    session_destroy();
}
?>