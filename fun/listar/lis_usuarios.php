<?php
session_start();
include '../../lib/functiones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>👨‍🌾 Listado de Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../../css/listarUsuarios.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
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
        echo "<div class='mensaje-error'><h2>⛔ Acceso restringido</h2><p>No tienes permisos para ver esta sección.</p></div>";
        echo "<div class='volver-form'><a href='../../menu.php'><button>Volver al menú</button></a></div>";
        exit;
    }

    $whereBusqueda = "";
    if (isset($_POST['buscar'])) {
        $keywords = mysqli_real_escape_string($conexion, trim($_POST['keywords']));
        $whereBusqueda = "WHERE u.nombre LIKE '$keywords%' OR u.apellidos LIKE '$keywords%'";
    }

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
    ?>

    <h1 class="titulo">👨‍🌾 Listado de Usuarios</h1>

    <div class="contenedor">
        <form method="post" class="busqueda-form">
            <input type="text" name="keywords" placeholder="🔍 Buscar por nombre o apellidos" required>
            <button type="submit" name="buscar">Buscar</button>
        </form>

        <?php if (mysqli_num_rows($resultado) > 0): ?>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Parcelas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td><?= htmlspecialchars($fila['apellidos']) ?></td>
                            <td><?= htmlspecialchars($fila['email']) ?></td>
                            <td><?= $fila['telefono'] ?? '—' ?></td>
                            <td><?= $fila['nombre_rol'] ?? 'Sin rol' ?></td>
                            <td><?= $fila['parcelas'] ?? 'Ninguna' ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="sin-resultados">No se encontraron usuarios.</p>
        <?php endif; ?>

        <a href="../../menu/usuarios.php" class="btn">🔙 Volver al menú de usuarios</a>
    </div>

<?php
} else {
    echo "<p class='mensaje-error'>Acceso denegado. Inicia sesión primero.</p>";
    echo "<div class='volver-form'><a href='../../index.php'><button>Volver al login</button></a></div>";
    session_destroy();
}
?>
</body>
</html>
