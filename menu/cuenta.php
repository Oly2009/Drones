<?php
include '../lib/functiones.php';
session_start();
$conexion = conectar();

if (!isset($_SESSION['usuario'])) {
    echo '⛔ Acceso denegado';
    session_destroy();
    exit();
}

$idUsuario = $_SESSION['usuario']['id_usr'];

$rolQuery = "SELECT r.nombre_rol FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = '$idUsuario'";
$rolResult = mysqli_query($conexion, $rolQuery);
$rol = mysqli_fetch_assoc($rolResult)['nombre_rol'];

// Solicitud AJAX para actualizar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuarioId'])) {
    header('Content-Type: application/json');

    $usuarioId = mysqli_real_escape_string($conexion, $_POST['usuarioId']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nuevoNombre']);
    $apellidos = mysqli_real_escape_string($conexion, $_POST['nuevoApellidos']);
    $telefono = mysqli_real_escape_string($conexion, $_POST['nuevoTelefono']);
    $email = mysqli_real_escape_string($conexion, $_POST['nuevoEmail']);
    $contraNueva = $_POST['conNuev'];
    $confirmacion = $_POST['confirmCon'];

    $errores = [];

    if (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $errores[] = "El teléfono debe tener exactamente 9 números.";
    }

    if ($rol === 'Admin' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Formato de email inválido.";
    }

    if (!empty($contraNueva) && $contraNueva !== $confirmacion) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (!empty($errores)) {
        echo json_encode(['mensaje' => implode(" ", $errores)]);
        exit();
    }

    $campos = [
        "nombre='$nombre'",
        "apellidos='$apellidos'",
        "telefono='$telefono'"
    ];

    if ($rol === 'Admin') {
        $campos[] = "email='$email'";
    }

    if (!empty($contraNueva) && $contraNueva === $confirmacion) {
        $hashNueva = base64_encode(hash('sha256', $contraNueva, true));
        $campos[] = "contrasena='$hashNueva'";
    }

    $consulta = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id_usr='$usuarioId'";

    if (mysqli_query($conexion, $consulta)) {
        echo json_encode(['mensaje' => '✅ Datos actualizados correctamente']);
    } else {
        echo json_encode(['mensaje' => '❌ Error al actualizar los datos: ' . mysqli_error($conexion)]);
    }
    exit();
}

// Consultar usuarios
$termino = $_POST['buscador'] ?? '';
$usuariosQuery = $rol === 'Admin' ? 
    ($termino ? "SELECT * FROM usuarios WHERE nombre LIKE '%$termino%' OR email LIKE '%$termino%'" : "SELECT * FROM usuarios") : 
    "SELECT * FROM usuarios WHERE id_usr = '$idUsuario'";
$usuariosResult = mysqli_query($conexion, $usuariosQuery);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🔐 Configuración de Cuenta - AgroSky</title>
    <link rel="stylesheet" href="../css/cuenta.css">
</head>
<body>
    <h1>🔐 GESTIÓN DE CUENTA</h1>

    <div class="formulario-cuenta wide">
        <?php if ($rol === 'Admin'): ?>
            <form method="post" class="buscador">
                <input type="text" id="buscador" name="buscador" placeholder="Buscar usuario">
                <button type="submit">Buscar</button>
            </form>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Nueva Contraseña</th>
                    <th>Confirmar Contraseña</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="usuariosTabla">
                <?php while ($row = mysqli_fetch_assoc($usuariosResult)): ?>
                    <tr>
                        <td><input type="text" value="<?= htmlspecialchars($row['nombre']) ?>" name="nuevoNombre"></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['apellidos']) ?>" name="nuevoApellidos"></td>
                        <td><input type="email" value="<?= htmlspecialchars($row['email']) ?>" <?= $rol !== 'Admin' ? 'readonly' : '' ?> name="nuevoEmail"></td>
                        <td><input type="tel" maxlength="9" value="<?= htmlspecialchars($row['telefono']) ?>" name="nuevoTelefono"></td>
                        <td><input type="password" name="conNuev"></td>
                        <td><input type="password" name="confirmCon"></td>
                        <td>
                            <button class="btn-actualizar" onclick="actualizarUsuario(this, <?= $row['id_usr'] ?>)">Actualizar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../menu/menu.php" class="btn">🔙 Volver al menú</a>
    </div>

    <div id="modal" class="modal" style="display:none;">
        <div class="modal-content">
            <p id="modalMensaje"></p>
            <button class="close-modal" onclick="cerrarModal()">Cerrar</button>
        </div>
    </div>

<script>
function actualizarUsuario(btn, usuarioId) {
    event.preventDefault();
    let fila = btn.closest('tr');
    let datos = new FormData();
    datos.append('usuarioId', usuarioId);
    datos.append('nuevoNombre', fila.querySelector('[name=nuevoNombre]').value);
    datos.append('nuevoApellidos', fila.querySelector('[name=nuevoApellidos]').value);
    datos.append('nuevoEmail', fila.querySelector('[name=nuevoEmail]').value);
    datos.append('nuevoTelefono', fila.querySelector('[name=nuevoTelefono]').value);
    datos.append('conNuev', fila.querySelector('[name=conNuev]').value);
    datos.append('confirmCon', fila.querySelector('[name=confirmCon]').value);

    fetch('', { 
        method: 'POST',
        body: datos
    })
    .then(resp => resp.json())
    .then(res => mostrarModal(res.mensaje))
    .catch(err => mostrarModal('Error inesperado'));
}

function mostrarModal(mensaje) {
    document.getElementById('modalMensaje').textContent = mensaje;
    document.getElementById('modal').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal').style.display = 'none';
}
</script>
</body>
</html>
