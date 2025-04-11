<!DOCTYPE html>
<?php
include '../lib/functiones.php';
session_start();
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👥 Administrar Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

<?php if (isset($_SESSION['usuario'])): ?>

<h1>👥 MENÚ USUARIOS</h1>

<div class="botonera">
    <a href="../fun/listar/lis_usuarios.php" class="btn">📋 Listar usuarios</a>
    <a href="../menu/registro.php" class="btn">➕ Dar de alta usuario</a>
    <a href="../fun/modificar/modificar_usuarios.php" class="btn">✏️ Modificar usuarios</a>
    <a href="../fun/eliminar/eli_usuarios.php" class="btn">🗑️ Borrar usuarios</a>
    <a href="../menu/menu.php" class="btn">🔙 Volver al menú</a>
</div>

<div class="imagen-menu">
    <img src="../img/usuario.jpg" alt="Trabajadores agrícolas utilizando drones">
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
