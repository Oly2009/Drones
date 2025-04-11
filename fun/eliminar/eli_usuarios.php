<?php 
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index.php');
    exit();
}

function eliminarUsuario($id_usuario) {
    $conexion = conectar();
    mysqli_begin_transaction($conexion);

    try {
        mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $id_usuario");
        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $id_usuario");

        $drones = [];
        $consultaDrones = mysqli_query($conexion, "SELECT id_dron FROM drones WHERE id_usr = $id_usuario");
        while ($row = mysqli_fetch_assoc($consultaDrones)) {
            $drones[] = $row['id_dron'];
        }

        if (!empty($drones)) {
            $idsDrones = implode(",", $drones);
            mysqli_query($conexion, "
                DELETE tt FROM trabajos_tareas tt 
                INNER JOIN trabajos t ON tt.id_trabajo = t.id_trabajo 
                WHERE t.id_dron IN ($idsDrones)
            ");
            mysqli_query($conexion, "DELETE FROM trabajos WHERE id_dron IN ($idsDrones)");
        }

        mysqli_query($conexion, "UPDATE drones SET id_usr = NULL WHERE id_usr = $id_usuario");
        mysqli_query($conexion, "DELETE FROM usuarios WHERE id_usr = $id_usuario");

        mysqli_commit($conexion);
        return ['success' => true, 'mensaje' => '✅ Usuario eliminado correctamente. Drones desvinculados.'];

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        return ['success' => false, 'mensaje' => '❌ Error al eliminar usuario: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $resultado = eliminarUsuario($id_usuario);

    $_SESSION['mensaje'] = $resultado['mensaje'];
    $_SESSION['mensaje_tipo'] = $resultado['success'] ? 'exito' : 'error';
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$conexion = conectar();
$usuarios = [];
$consulta = mysqli_query($conexion, "
    SELECT 
        u.id_usr, 
        u.nombre, 
        u.apellidos,
        u.email, 
        u.telefono, 
        (
            SELECT GROUP_CONCAT(r.nombre_rol SEPARATOR ', ')
            FROM usuarios_roles ur
            JOIN roles r ON ur.id_rol = r.id_rol
            WHERE ur.id_usr = u.id_usr AND ur.id_rol != 1
        ) AS rol,
        (
            SELECT GROUP_CONCAT(p.ubicacion SEPARATOR ', ')
            FROM parcelas_usuarios pu
            JOIN parcelas p ON pu.id_parcela = p.id_parcela
            WHERE pu.id_usr = u.id_usr
        ) AS parcelas
    FROM usuarios u
    WHERE u.id_usr NOT IN (
        SELECT id_usr FROM usuarios_roles WHERE id_rol = 1
    )
");
if ($consulta) {
    while ($usuario = mysqli_fetch_assoc($consulta)) {
        $usuarios[] = $usuario;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>👤 Eliminar Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../../css/eliminarUsuarios.css">
</head>
<body>

<h1>👤 Eliminar Usuarios</h1>

<?php 
if (isset($_SESSION['mensaje'])) {
    $tipo_clase = $_SESSION['mensaje_tipo'] === 'exito' ? 'mensaje-exito' : 'mensaje-error';
    echo "<div class='mensaje {$tipo_clase}'>" . htmlspecialchars($_SESSION['mensaje']) . "</div>";
    unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
}
?>

<div class="formulario-cuenta">
    <form class="busqueda-form" onsubmit="event.preventDefault();">
        <input type="text" placeholder="🔍 Buscar por nombre o correo" id="buscarUsuario">
        <button type="submit">Buscar</button>
    </form>

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
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['apellidos']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><?= htmlspecialchars($usuario['telefono']) ?></td>
                        <td><?= htmlspecialchars($usuario['rol'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($usuario['parcelas'] ?? 'Ninguna') ?></td>
                        <td>
                            <form method="post" class="form-eliminar">
                                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usr'] ?>">
                                <button type="button" class="btn-accion" onclick="confirmarEliminacion(this)">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay usuarios disponibles para eliminar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="../../menu/usuarios.php" class="btn">🔙 Volver al menú de usuarios</a>
</div>

<!-- Modal -->
<div id="modalConfirmacion" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de que deseas eliminar este usuario?</p>
        <div class="modal-botones">
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-accion" id="btnConfirmarEliminacion">Eliminar</button>
        </div>
    </div>
</div>

<script>
let formularioParaEliminar = null;

function confirmarEliminacion(boton) {
    formularioParaEliminar = boton.closest('form');
    document.getElementById('modalConfirmacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalConfirmacion').style.display = 'none';
    formularioParaEliminar = null;
}

document.getElementById('btnConfirmarEliminacion').addEventListener('click', function () {
    if (formularioParaEliminar) {
        formularioParaEliminar.submit();
    }
});

document.getElementById('buscarUsuario').addEventListener('input', function () {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll('.tabla-responsive tbody tr').forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
    });
});
</script>

</body>
</html>
