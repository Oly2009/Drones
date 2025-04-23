<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar();
$idAdmin = $_SESSION['usuario']['id_usr'];

$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idAdmin");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
}
if (!$esAdmin) {
    echo "<h2 class='mensaje-error'>⛔ Acceso restringido</h2><p class='mensaje-error'>Solo administradores pueden acceder.</p>";
    exit();
}

$mensaje = "";
$tipo = "";
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
        $tipo = "exito";
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje = "❌ Error al modificar: " . $e->getMessage();
        $tipo = "error";
    }
}

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

$rolesArray = mysqli_query($conexion, "SELECT * FROM roles WHERE id_rol != 1");
$parcelasArray = mysqli_query($conexion, "SELECT * FROM parcelas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>👤 Modificar Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../../css/modificarUsuarios.css">
</head>
<body>

<h1>👤 Modificar Usuarios</h1>

<div class="formulario-cuenta">
    <form class="busqueda-form" onsubmit="event.preventDefault();">
        <input type="text" placeholder="🔍 Buscar por nombre o correo" id="buscarUsuario">
        <button type="submit">Buscar</button>
    </form>

    <div class="tabla-responsive">
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
                                    <?php
                                    mysqli_data_seek($rolesArray, 0);
                                    while ($r = mysqli_fetch_assoc($rolesArray)): ?>
                                        <option value="<?= $r['id_rol'] ?>" <?= $u['id_rol'] == $r['id_rol'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['nombre_rol']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <select name="parcela" required>
                                    <option value="">-- Selecciona --</option>
                                    <?php
                                    mysqli_data_seek($parcelasArray, 0);
                                    while ($p = mysqli_fetch_assoc($parcelasArray)): ?>
                                        <option value="<?= $p['id_parcela'] ?>" <?= $u['id_parcela'] == $p['id_parcela'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['ubicacion']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="usuario" value="<?= $u['id_usr'] ?>">
                                <button type="submit" class="btn-accion">Modificar</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="../../menu/usuarios.php" class="btn">🔙 Volver al menú de usuarios</a>
</div>

<!-- Modal de mensaje -->
<div id="alertModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div id="alertBox" style="background:white; padding:30px 40px; border-radius:15px; text-align:center; max-width:500px; box-shadow:0 0 15px rgba(0,0,0,0.3); border:2px solid;">
        <p id="alertText" style="font-weight:bold; font-size:1rem; margin-bottom:20px;"></p>
        <button id="alertCerrar" class="btn">Cerrar</button>
    </div>
</div>

<script>
document.getElementById('buscarUsuario')?.addEventListener('input', function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll('.tabla-responsive table tbody tr');
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
});

// Mostrar mensaje si hay
window.onload = function () {
    const msg = <?= json_encode($mensaje) ?>;
    const tipo = <?= json_encode($tipo) ?>;
    if (msg) {
        const alertBox = document.getElementById("alertModal");
        const alertText = document.getElementById("alertText");
        const alertInner = document.getElementById("alertBox");

        alertText.textContent = msg;

        if (tipo === 'exito') {
            alertInner.style.borderColor = "#66bb6a";
            alertText.style.color = "#2e7d32";
        } else {
            alertInner.style.borderColor = "#e57373";
            alertText.style.color = "#c62828";
        }

        alertBox.style.display = "flex";
    }

    document.getElementById("alertCerrar").onclick = function () {
        document.getElementById("alertModal").style.display = "none";
    };
};
</script>

</body>
</html>
