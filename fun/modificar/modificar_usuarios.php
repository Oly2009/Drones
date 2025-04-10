<?php
include '../../lib/functiones.php';
session_start();

// Verificación de sesión y rol
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$conexion = conectar();
$idAdmin = $_SESSION['usuario']['id_usr'];

// Comprobar si es administrador
$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idAdmin");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
}
if (!$esAdmin) {
    echo "<h2>Acceso restringido</h2><p>Solo administradores pueden acceder.</p>";
    exit();
}

// Procesar actualización
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuario"], $_POST["rol"], $_POST["parcela"])) {
    $idUsuario = intval($_POST["usuario"]);
    $nuevoRol = intval($_POST["rol"]);
    $nuevaParcela = intval($_POST["parcela"]);

    try {
        mysqli_begin_transaction($conexion);

        mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $idUsuario");
        mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($idUsuario, $nuevoRol)");

        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $idUsuario");
        mysqli_query($conexion, "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($idUsuario, $nuevaParcela)");

        mysqli_commit($conexion);
        $mensaje = "✅ Usuario actualizado correctamente.";
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje = "❌ Error al modificar: " . $e->getMessage();
    }
}

// Obtener usuarios
$usuarios = mysqli_query($conexion, "
    SELECT u.id_usr, u.nombre, u.apellidos, u.email,
        (SELECT r.nombre_rol FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = u.id_usr LIMIT 1) AS rol,
        (SELECT r.id_rol FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = u.id_usr LIMIT 1) AS id_rol,
        (SELECT p.ubicacion FROM parcelas_usuarios pu JOIN parcelas p ON pu.id_parcela = p.id_parcela WHERE pu.id_usr = u.id_usr LIMIT 1) AS parcela,
        (SELECT p.id_parcela FROM parcelas_usuarios pu JOIN parcelas p ON pu.id_parcela = p.id_parcela WHERE pu.id_usr = u.id_usr LIMIT 1) AS id_parcela
    FROM usuarios u
    JOIN usuarios_roles ur ON u.id_usr = ur.id_usr
    WHERE ur.id_rol != 1
");

$rolesArray = [];
$roles = mysqli_query($conexion, "SELECT * FROM roles WHERE id_rol != 1");
while ($r = mysqli_fetch_assoc($roles)) {
    $rolesArray[] = $r;
}

$parcelasArray = [];
$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas");
while ($p = mysqli_fetch_assoc($parcelas)) {
    $parcelasArray[] = $p;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../../css/modificarUsuarios.css">
</head>
<body>
    <div class="container-eliminar-usuario">
        <h2 class="titulo-eliminacion">Modificar Usuarios</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-exito"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <!-- Buscador -->
        <div class="buscador">
            <input type="text" placeholder="Buscar usuario" id="buscarUsuario">
            <button type="button">Buscar</button>
        </div>

        <!-- Tabla -->
        <div class="tabla-usuarios">
            <table>
                <thead>
                    <tr>
                        <th>Nombre completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Parcela</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
                        <tr>
                            <form method="post" action="modificar_usuarios.php">
                                <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <select name="rol" required>
                                        <option value="">-- Selecciona --</option>
                                        <?php foreach ($rolesArray as $r): ?>
                                            <option value="<?= $r['id_rol'] ?>" <?= $u['id_rol'] == $r['id_rol'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($r['nombre_rol']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="parcela" required>
                                        <option value="">-- Selecciona --</option>
                                        <?php foreach ($parcelasArray as $p): ?>
                                            <option value="<?= $p['id_parcela'] ?>" <?= $u['id_parcela'] == $p['id_parcela'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['ubicacion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="usuario" value="<?= $u['id_usr'] ?>">
                                    <button type="submit" class="btn-eliminar">Modificar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón volver -->
        <div class="botones-acciones" style="text-align: center; margin-top: 30px;">
            <a href="../usuarios.php" class="btn-volver">Volver</a>
        </div>
    </div>

    <!-- Script buscador -->
    <script>
        document.getElementById('buscarUsuario')?.addEventListener('input', function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('.tabla-usuarios tbody tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

