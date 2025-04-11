<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <title>🗑️ Eliminar Parcelas - AgroSky</title>
   <link rel="stylesheet" href="../../css/eliminarParcelas.css">
</head>
<body>

<?php
include '../../lib/functiones.php';
session_start();

if (isset($_SESSION['usuario'])) {
    echo "<h1>🗑️ Eliminar Parcelas</h1>";
    echo "<div class='formulario-cuenta'>";

    if (isset($_REQUEST['eliminar'])) {
        $borrar = $_REQUEST['borrar'] ?? [];

        if (count($borrar) > 0) {
            $nfilas = count($borrar);
            echo "<div class='mensaje-exito'>";
            for ($i = 0; $i < $nfilas; $i++) {
                $id = intval($borrar[$i]);
                $conexion = conectar();

                $instruccion = "SELECT * FROM parcelas WHERE id_parcela = $id";
                $consulta = mysqli_query($conexion, $instruccion) or die("Fallo en la consulta");
                $resultado = mysqli_fetch_array($consulta);

                $nombreArchivo = $resultado['fichero'];
                $rutaArchivo = realpath(__DIR__ . "/../agregar/parcelas/" . $nombreArchivo);

                echo "<strong>Parcela eliminada:</strong><ul>";
                echo "<li>ID: {$resultado['id_parcela']}</li>";
                echo "<li>Ubicación: {$resultado['ubicacion']}</li>";
                echo "<li>Fichero: $nombreArchivo</li></ul>";

                if ($rutaArchivo && file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                    echo "<p class='archivo-ok'>✅ Archivo <strong>$nombreArchivo</strong> eliminado correctamente.</p>";
                } else {
                    echo "<p class='archivo-alerta'>⚠️ Archivo <strong>$nombreArchivo</strong> no encontrado.</p>";
                }

                mysqli_query($conexion, "DELETE FROM ruta WHERE id_parcela = $id");
                mysqli_query($conexion, "DELETE FROM trabajos WHERE id_parcela = $id");
                mysqli_query($conexion, "UPDATE drones SET id_parcela = NULL WHERE id_parcela = $id");
                mysqli_query($conexion, "DELETE FROM parcelas WHERE id_parcela = $id") or die("Fallo al eliminar la parcela");
            }

            echo "<p class='total-eliminadas'>🧹 Parcelas eliminadas: <strong>$nfilas</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='mensaje-error'>⚠️ No se seleccionó ninguna parcela para eliminar.</div>";
        }

        echo "<form method='post' action='eli_parcelas.php'>";
        echo "<div class='acciones'>";
        echo "<button class='btn-accion'>Eliminar más parcelas</button>";
        echo "<a href='../../menu/parcelas.php' class='btn'>🔙 Volver al menú de parcelas</a>";
        echo "</div>";
        echo "</form>";

    } else {
        $conexion = conectar();
        $instruccion = "SELECT * FROM parcelas ORDER BY id_parcela ASC";
        $consulta = mysqli_query($conexion, $instruccion) or die("Fallo en la consulta");

        if (mysqli_num_rows($consulta) > 0) {
            echo "<form method='post' action='eli_parcelas.php'>";
            echo "<div class='tabla-responsive'><table>";
            echo "<thead><tr><th>ID</th><th>Ubicación</th><th>Fichero</th><th>Eliminar</th></tr></thead><tbody>";

            while ($resultado = mysqli_fetch_array($consulta)) {
                echo "<tr>";
                echo "<td>{$resultado['id_parcela']}</td>";
                echo "<td>{$resultado['ubicacion']}</td>";
                echo "<td>{$resultado['fichero']}</td>";
                echo "<td><input type='checkbox' name='borrar[]' value='{$resultado['id_parcela']}'></td>";
                echo "</tr>";
            }

            echo "</tbody></table></div>";
            echo "<div class='acciones'>";
            echo "<button type='submit' name='eliminar' class='btn-accion'>Eliminar parcela</button>";
            echo "<a href='../../menu/parcelas.php' class='btn'>🔙 Volver al menú de parcelas</a>";
            echo "</div>";
            echo "</form>";
        } else {
            echo "<p class='mensaje-error'>📭 No hay parcelas disponibles para eliminar.</p>";
            echo "<div class='acciones'><a href='../../menu/parcelas.php' class='btn'>🔙 Volver al menú de parcelas</a></div>";
        }
    }

    echo "</div>";
} else {
    echo "<div class='mensaje-error'>⛔ Acceso denegado</div>";
    echo "<div class='acciones'><a href='../../index.php' class='btn'>Volver al login</a></div>";
    session_destroy();
}
?>

</body>
</html>
