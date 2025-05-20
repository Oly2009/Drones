<?php
session_start();
include '../../lib/functiones.php';

if (!isset($_SESSION['usuario'])) {
    echo "<div class='modal error'><p>⛔ Acceso denegado</p><a href='../../index.php' class='btn'>Volver</a></div>";
    session_destroy();
    exit();
}

$conexion = conectar();
$idUsr = $_SESSION['usuario']['id_usr'];

$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idUsr");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
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
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Usuarios</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="container flex-grow-1 py-5">
  <section>
    <h1 class="titulo-listado text-center">
      <i class="bi bi-person-lines-fill me-2" style="color: #6f42c1;"></i>Listado de Usuarios
    </h1>

      <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" id="buscarUsuario" class="form-control w-50 me-2" placeholder="🔍 Buscar por nombre, correo o teléfono">
    <button class="btn btn-success" type="button" onclick="filtrarUsuarios()">Buscar</button>
  </form>

    <?php if (mysqli_num_rows($resultado) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-success text-center">
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
        <div class="alert alert-warning text-center">❌ No se encontraron usuarios.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../../menu/usuarios.php" class="btn btn-danger w-100 w-sm-auto px-4">
      <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de usuarios
    </a>
    </div>
  </section>
</main>

<?php include '../../componentes/footer.php'; ?>
</div>
</body>
</html>
