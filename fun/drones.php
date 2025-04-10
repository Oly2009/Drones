<?php
include '../lib/functiones.php';
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Drones</title>
    <link rel="stylesheet" href="../css/drones.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
?>
    <h1>🛰️ <span class="titulo-azul">MENÚ DRONES</span></h1>

    <div class="botones-menu">
        <form action="agregar/agr_drones.php" method="post">
            <button type="submit">➕ Añadir drones</button>
        </form>
        <form action="eliminar/eli_drones.php" method="post">
            <button type="submit">🗑️ Eliminar drones</button>
        </form>
        <form action="listar/lis_drones.php" method="post">
            <button type="submit">📋 Listar drones</button>
        </form>
        <form action="modificar/mod_drones.php" method="post">
            <button type="submit">⚙️ Modificar drones</button>
        </form>
        <form action="../menu.php" method="post">
            <button type="submit">🔙 Volver al menú</button>
        </form>
    </div>

    <div class="imagen-dron">
        <img src="https://oesteyeste.com/wp-content/uploads/2019/10/dron-grabaciones-aereas.gif" alt="Dron animado">
    </div>

<?php
} else {
    echo "<p>⛔ Acceso denegado</p>";
    echo '<a href="../login.php"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
