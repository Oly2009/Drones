<?php
session_start();
include '../../lib/functiones.php';
?>
  <link rel="stylesheet" href="../../css/style.css">
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="flex-grow-1 container py-5">
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
        echo "<div class='alert alert-danger text-center'><h2>⛔ Acceso restringido</h2><p>No tienes permisos para ver esta sección.</p></div>";
        echo "<div class='text-center'><a href='../../menu/menu.php' class='btn btn-danger'><i class='bi bi-arrow-left'></i> Volver al menú</a></div>";
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

    <section>
        <h1 class="titulo-listado">
          <i class="bi bi-person-lines-fill me-2" style="color: #6f42c1;"></i>Listado de Usuarios
        </h1>

        <form method="post" class="busqueda-form d-flex">
            <input type="text" name="keywords" class="form-control me-2" placeholder="🔍 Buscar por nombre o apellidos" required>
            <button type="submit" name="buscar" class="btn btn-success"><i class="bi bi-search"></i> Buscar</button>
        </form>

        <?php if (mysqli_num_rows($resultado) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle">
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
            <div class="alert alert-warning text-center">No se encontraron usuarios.</div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="../../menu/usuarios.php" class="btn btn-danger rounded-pill px-4">
                <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de usuarios
            </a>
        </div>
    </section>

<?php
} else {
    echo "<div class='alert alert-danger text-center'>Acceso denegado. Inicia sesión primero.</div>";
    echo "<div class='text-center'><a href='../../index.php' class='btn btn-secondary'><i class='bi bi-box-arrow-left me-1'></i> Volver al login</a></div>";
    session_destroy();
}
?>
</main>

<?php include '../../componentes/footer.php'; ?>
</body>
</html>
