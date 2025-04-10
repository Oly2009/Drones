<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú de parcelas</title>
    <link rel="stylesheet" type="text/css" href="../css/parcelas.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php
include '../lib/functiones.php';
session_start();

if (isset($_SESSION['usuario'])) {
?>
    <h1>🗺️ MENÚ PARCELAS</h1>

 

    <div class="botonera" id="menu">
        <a href="agregar/agr_parcelas.php" class="btn">➕ Añadir parcelas</a>
        <a href="modificar/mod_parcelas.php" class="btn">✏️ Modificar parcela</a>
        <a href="eliminar/eli_parcelas.php" class="btn">🗑️ Eliminar parcelas</a>
        <a href="listar/ver_parcelas.php" class="btn">📍 Agregar ruta</a>
        <a href="../menu.php" class="btn">🔙 Volver al menú</a>
    </div>

    <div class="imagen">
        <img src="../img/drones-agricultura.jpeg" alt="Drones en agricultura">
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("menu").classList.toggle("activo");
        }
    </script>

<?php
} else {
    echo '⛔ Acceso denegado';
    echo '<a href="login.php"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
