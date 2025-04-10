<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de usuarios</title>
    <link rel="stylesheet" href="../../css/listarUsuarios.css">
</head>
<body>

<?php
include '../../lib/functiones.php';
session_start();

if (isset($_SESSION['usuario'])) {
    $conexion = conectar();

    // Verificar si el usuario actual es admin
    $idUsr = $_SESSION['usuario']['id_usr'];
    $esAdmin = false;
    $rolConsulta = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idUsr");
    while ($rol = mysqli_fetch_assoc($rolConsulta)) {
        if ($rol['id_rol'] == 1) {
            $esAdmin = true;
            break;
        }
    }

    if (!$esAdmin) {
        echo "<div class='mensaje-error'><h2>Acceso restringido</h2><p>No tienes permisos para ver esta sección.</p></div>";
        echo "<div class='volver-form'><a href='../../menu.php'><button>Volver</button></a></div>";
        exit;
    }

    // Filtro de búsqueda (opcional)
    $whereBusqueda = "";
    if (isset($_POST['buscar'])) {
        $keywords = trim($_POST['keywords']);
        $keywords = mysqli_real_escape_string($conexion, $keywords);
        $whereBusqueda = "WHERE u.nombre LIKE '$keywords%' OR u.apellidos LIKE '$keywords%'";
    }

    // Consulta usuarios excluyendo admins
    $sql = "
        SELECT u.nombre, u.apellidos, u.email, u.telefono, r.nombre_rol,
               GROUP_CONCAT(p.ubicacion SEPARATOR ', ') AS parcelas
        FROM usuarios u
        JOIN usuarios_roles ur ON u.id_usr = ur.id_usr AND ur.id_rol != 1
        LEFT JOIN roles r ON ur.id_rol = r.id_rol
        LEFT JOIN parcelas_usuarios pu ON u.id_usr = pu.id_usr
        LEFT JOIN parcelas p ON pu.id_parcela = p.id_parcela
        $whereBusqueda
        GROUP BY u.id_usr
    ";

    $resultado = mysqli_query($conexion, $sql);

    echo "<h2>Listado de usuarios</h2>";

    // Formulario búsqueda
    echo "<form method='post' action='lis_usuarios.php' class='busqueda-form'>
            <input type='text' name='keywords' placeholder='🔎 Buscar por nombre o apellidos' required>
            <input type='submit' name='buscar' value='Buscar'>
          </form>";

    // Mostrar resultados
    if (mysqli_num_rows($resultado) > 0) {
        echo "<div class='tabla-container'>";
        echo "<table>";
        echo "<tr>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Parcelas</th>
              </tr>";

        while ($fila = mysqli_fetch_array($resultado)) {
            $rol = $fila['nombre_rol'] ?? 'Sin asignar';
            $parcelas = $fila['parcelas'] ?? 'Ninguna';
            $telefono = $fila['telefono'] ?? '—';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($fila['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($fila['email']) . "</td>";
            echo "<td>$telefono</td>";
            echo "<td>$rol</td>";
            echo "<td>$parcelas</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    } else {
        echo "<p style='text-align: center;'>No se encontraron usuarios.</p>";
    }

    echo "<form action='../usuarios.php' method='post' class='volver-form'>
            <input type='submit' name='volverReg' value='Volver'>
          </form>";

} else {
    echo "<p>Acceso denegado</p>";
    echo '<a href="../../login.php"><button>Volver</button></a>';
    session_destroy();
}
?>

</body>
</html>
