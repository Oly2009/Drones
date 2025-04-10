<?php
include '../../lib/functiones.php';
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar drones</title>
    <link rel="stylesheet" href="../../css/modificarDrones.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
    $idUsr = $_SESSION['usuario']['id_usr'];
    $mensaje = "";

    // Procesar actualización
    if (isset($_POST['actualizar'])) {
        foreach ($_POST['estado'] as $id_dron => $nuevoEstado) {
            $estadoFinal = $nuevoEstado === "reparado" ? "disponible" : "estropeado";
            $update = "UPDATE drones SET estado = '$estadoFinal' WHERE id_dron = $id_dron AND id_usr = $idUsr";
            mysqli_query($conexion, $update);
        }
        $mensaje = "✅ Estado(s) actualizado(s) correctamente.";
    }

    $consulta = mysqli_query($conexion, "SELECT * FROM drones WHERE id_usr = $idUsr ORDER BY id_dron ASC");

    if (mysqli_num_rows($consulta) > 0) {
        echo "<h2>Modificar Estado de los Drones</h2>";

        if (!empty($mensaje)) {
            echo "<div class='mensaje-exito'>$mensaje</div>";
        }

        echo "<div class='container-tabla'>";
        echo "<form method='post' action='mod_drones.php'>";
        echo "<table>";
        echo "<tr><th>Nombre</th><th>Marca</th><th>Estado actual</th><th>Cambiar estado</th></tr>";

        while ($fila = mysqli_fetch_array($consulta)) {
            $estadoClase = $fila['estado'] === "disponible" ? "estado-disponible" : "estado-estropeado";
            $selectedReparado = $fila['estado'] === "disponible" ? "selected" : "";
            $selectedEstropeado = $fila['estado'] === "estropeado" ? "selected" : "";

            echo "<tr>";
            echo "<td>{$fila['nombre']}</td>";
            echo "<td>{$fila['marca']}</td>";
            echo "<td class='$estadoClase'>" . ucfirst($fila['estado']) . "</td>";
            echo "<td>
                    <select name='estado[{$fila['id_dron']}]'>
                        <option value='reparado' $selectedReparado>Reparado</option>
                        <option value='estropeado' $selectedEstropeado>Sigue estropeado</option>
                    </select>
                  </td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>"; // cierre de container-tabla

        echo "<div class='botones-drones'>";
        echo "<input type='submit' name='actualizar' value='Actualizar estado'>";
        echo "</form>";

        echo "<form action='../drones.php' method='post'>";
        echo "<input type='submit' name='volverReg' value='Volver'>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<p>No tienes drones registrados.</p>";
    }
} else {
    echo "<p>Acceso denegado</p>";
    echo '<a href="javascript:history.back()"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
