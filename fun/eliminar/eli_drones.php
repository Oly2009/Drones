<?php
include '../../lib/functiones.php';
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar drones</title>
    <link rel="stylesheet" href="../../css/eliminarDrones.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
    if (isset($_REQUEST['eliminar'])) {
        $borrar = $_REQUEST['borrar'] ?? [];

        if (count($borrar) > 0) {
            $nfilas = count($borrar);

            for ($i = 0; $i < $nfilas; $i++) {
                $id = intval($borrar[$i]);

                $sql = "SELECT d.*, p.ubicacion 
                        FROM drones d 
                        LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela 
                        WHERE d.id_dron = $id";
                $consulta = mysqli_query(conectar(), $sql);
                $resultado = mysqli_fetch_array($consulta);

                echo "<h3>Dron eliminado:</h3>";
                echo "<ul>";
                echo "<li>ID: " . $resultado['id_dron'] . "</li>";
                echo "<li>Nombre: " . $resultado['nombre'] . "</li>";
                echo "<li>Marca: " . $resultado['marca'] . "</li>";
                echo "<li>Parcela asignada: " . ($resultado['ubicacion'] ?? 'Sin asignar') . "</li>";
                echo "</ul>";

                $eliminar = "DELETE FROM drones WHERE id_dron = $id";
                mysqli_query(conectar(), $eliminar);
            }

            echo "<p class='mensaje-exito'>✅ Número total de drones eliminados: $nfilas</p>";
        } else {
            echo "<p class='mensaje-error'>⚠️ No se ha seleccionado ningún dron para eliminar.</p>";
        }

        echo "<div class='botonera'>";
        echo "<form action='eli_drones.php' method='post'>";
        echo "<button type='submit' class='boton'>Eliminar más drones</button>";
        echo "</form>";

        echo "<form action='../drones.php' method='post'>";
        echo "<button type='submit' class='boton'>Volver</button>";
        echo "</form>";
        echo "</div>";

    } else {
        $idUsr = $_SESSION['usuario']['id_usr'];
        $sql = "SELECT d.id_dron, d.nombre, d.marca, p.ubicacion
                FROM drones d
                LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
                WHERE d.id_usr = '$idUsr'
                ORDER BY d.id_dron ASC";

        $consulta = mysqli_query(conectar(), $sql);
        $nfilas = mysqli_num_rows($consulta);

        if ($nfilas > 0) {
            echo "<h2 class='titulo'>Eliminar Drones</h2>";
            echo "<div class='tabla-container'>";
            echo "<form action='eli_drones.php' method='post'>";
            echo "<table>";
            echo "<tr><th>Nombre</th><th>Marca</th><th>Parcela</th><th>Eliminar</th></tr>";

            while ($resultado = mysqli_fetch_array($consulta)) {
                echo "<tr>";
                echo "<td>{$resultado['nombre']}</td>";
                echo "<td>{$resultado['marca']}</td>";
                echo "<td>" . ($resultado['ubicacion'] ?? 'Sin asignar') . "</td>";
                echo "<td><input type='checkbox' name='borrar[]' value='{$resultado['id_dron']}'></td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "<div class='botonera'>";
            echo "<button type='submit' name='eliminar' class='boton'>Eliminar seleccionados</button>";
            echo "</form>";

            echo "<form action='../drones.php' method='post'>";
            echo "<button type='submit' class='boton'>Volver</button>";
            echo "</form>";
            echo "</div>"; // cierre botonera
            echo "</div>"; // cierre tabla-container
        } else {
            echo "<p>No hay drones disponibles para eliminar.</p>";
        }
    }
} else {
    echo "<p>Acceso denegado</p>";
    echo '<a href="javascript:history.back()"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
