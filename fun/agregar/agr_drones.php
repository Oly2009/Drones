<?php
include '../../lib/functiones.php';
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Dron</title>
    <link rel="stylesheet" href="../../css/agregarDrones.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
    $idUsr = $_SESSION['usuario']['id_usr'];

    // Verificar si es admin
    $rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idUsr");
    $esAdmin = false;
    while ($rol = mysqli_fetch_assoc($rolCheck)) {
        if ($rol['id_rol'] == 1) {
            $esAdmin = true;
            break;
        }
    }

    if (!$esAdmin) {
        echo "<div class='error'>⛔ Acceso denegado. Solo los administradores pueden agregar drones.</div>";
        echo '<div class="botones"><a href="../menu.php" class="btn">Volver al menú</a></div>';
        exit();
    }

    // Procesar formulario
    if (isset($_POST['anhadir'])) {
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $id_parcela = intval($_POST['parcela']);

        // Verificar duplicados por nombre o marca
        $existe = mysqli_query($conexion, "SELECT * FROM drones WHERE nombre='$nombre' OR marca='$marca'");
        if (mysqli_num_rows($existe) > 0) {
            $mensaje = "⚠ Ya existe un dron con ese nombre o marca.";
            $tipo = "error";
        } else {
            // Verificar si ya está asignado ese dron a esa parcela
            $yaAsignado = mysqli_query($conexion, "SELECT * FROM drones WHERE id_parcela = $id_parcela AND nombre = '$nombre'");
            if (mysqli_num_rows($yaAsignado) > 0) {
                $mensaje = "⚠ El dron ya está asignado a esa parcela.";
                $tipo = "error";
            } else {
                $insert = "INSERT INTO drones (nombre, marca, id_usr, estado, id_parcela)
                           VALUES ('$nombre', '$marca', $idUsr, 'disponible', $id_parcela)";
                if (mysqli_query($conexion, $insert)) {
                    $mensaje = "✅ Dron añadido correctamente y asignado a la parcela.";
                    $tipo = "exito";
                } else {
                    $mensaje = "❌ Error al insertar el dron.";
                    $tipo = "error";
                }
            }
        }

        echo "<div class='mensaje-$tipo'>$mensaje</div>";
        echo '<div class="botones"><a href="agr_drones.php" class="btn">Agregar otro</a> <a href="../drones.php" class="btn">Volver</a></div>';

    } else {
        // Obtener todas las parcelas
        $parcelas = mysqli_query($conexion, "SELECT * FROM parcelas");
?>

<div class="container">
    <h2>➕ Agregar Dron y Asignar Parcela</h2>

    <form action="agr_drones.php" method="post" class="form-agregar">
        <label>Nombre del dron:</label>
        <input type="text" name="nombre" required placeholder="Nombre del dron">

        <label>Marca del dron:</label>
        <input type="text" name="marca" required placeholder="Marca del dron">

        <label>Asignar a parcela:</label>
        <select name="parcela" required>
            <option value="">-- Selecciona una parcela --</option>
            <?php while ($fila = mysqli_fetch_array($parcelas)) {
                echo "<option value='{$fila['id_parcela']}'>{$fila['ubicacion']}</option>";
            } ?>
        </select>

        <div class="botones">
            <input type="submit" name="anhadir" value="Agregar" class="btn">
            <a href="../drones.php" class="btn">Volver</a>
        </div>
    </form>
</div>

<?php
    }

} else {
    echo "<p>⚠ Acceso denegado</p>";
    echo '<a href="../../login.php"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
