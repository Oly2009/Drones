<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <title>Eliminar parcela</title>
   <link rel="stylesheet" type="text/css" href="/Proyecto_Drones_v3-CSS/css/eliminarParcelas.css">
</head>
<body>
<?php
include '../../lib/functiones.php';
session_start();

if (isset($_SESSION['usuario'])) {
    if (isset($_REQUEST['eliminar'])) {
        $borrar = $_REQUEST['borrar'] ?? [];

        if (count($borrar) > 0) {
            $nfilas = count($borrar);

            for ($i = 0; $i < $nfilas; $i++) {
                $id = intval($borrar[$i]);
                $conexion = conectar();

                // Obtener info del archivo
                $instruccion = "SELECT * FROM parcelas WHERE id_parcela = $id";
                $consulta = mysqli_query($conexion, $instruccion) or die("Fallo en la consulta");
                $resultado = mysqli_fetch_array($consulta);

                $nombreArchivo = $resultado['fichero'];
                $rutaArchivo = realpath(__DIR__ . "/../agregar/parcelas/" . $nombreArchivo);

                echo "<h3>Parcela eliminada:</h3>";
                echo "<ul>";
                echo "<li>ID: " . $resultado['id_parcela'] . "</li>";
                echo "<li>Ubicación: " . $resultado['ubicacion'] . "</li>";
                echo "<li>Fichero: " . $nombreArchivo . "</li>";
                echo "</ul>";

                // Eliminar archivo del sistema si existe
                if ($rutaArchivo && file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                    echo "<p style='color:lime;'>Archivo <strong>$nombreArchivo</strong> eliminado correctamente.</p>";
                } else {
                    echo "<p style='color:orange;'>Archivo <strong>$nombreArchivo</strong> no encontrado o ruta inválida.</p>";
                }

                // Eliminar relaciones en otras tablas
                mysqli_query($conexion, "DELETE FROM ruta WHERE id_parcela = $id");
                mysqli_query($conexion, "DELETE FROM trabajos WHERE id_parcela = $id");
                mysqli_query($conexion, "UPDATE drones SET id_parcela = NULL WHERE id_parcela = $id");

                // Eliminar de la BD
                $eliminar = "DELETE FROM parcelas WHERE id_parcela = $id";
                mysqli_query($conexion, $eliminar) or die("Fallo en la eliminación");
            }

            echo "<p style='color:lime;'>Número total de parcelas eliminadas: $nfilas</p>";
        } else {
            echo "<p>No se ha seleccionado ninguna parcela para eliminar.</p>";
        }

        echo "<form action='eli_parcelas.php' method='post'>";
        echo "<input type='submit' value='Eliminar más parcelas'>";
        echo "</form>";
    } else {
        $conexion = conectar();
        $instruccion = "SELECT * FROM parcelas ORDER BY id_parcela ASC";
        $consulta = mysqli_query($conexion, $instruccion) or die("Fallo en la consulta");

        $nfilas = mysqli_num_rows($consulta);

        if ($nfilas > 0) {
            echo "<h2>Selecciona las parcelas a eliminar</h2>";
            echo "<form action='eli_parcelas.php' method='post'>";
            echo "<table border='1' cellspacing='0' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Ubicación</th><th>Fichero</th><th>Eliminar</th></tr>";

            while ($resultado = mysqli_fetch_array($consulta)) {
                echo "<tr>";
                echo "<td>{$resultado['id_parcela']}</td>";
                echo "<td>{$resultado['ubicacion']}</td>";
                echo "<td>{$resultado['fichero']}</td>";
                echo "<td><input type='checkbox' name='borrar[]' value='{$resultado['id_parcela']}'></td>";
                echo "</tr>";
            }

            echo "</table><br>";
            echo "<input type='submit' name='eliminar' value='Eliminar parcela'>";
            echo "</form>";
        } else {
            echo "<p>No hay parcelas disponibles para eliminar.</p>";
        }
    }

    echo "<form action='../parcelas.php' method='post' style='margin-top: 20px;'>";
    echo "<input type='submit' name='volverReg' value='Volver'>";
    echo "</form>";

} else {
    echo '<p>Acceso denegado</p>';
    echo '<a href="javascript:history.back()"><button>Volver</button></a>';
    session_destroy();
}
?>
</body>
</html>
