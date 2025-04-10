<?php 
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

// Función de eliminación completa (sin borrar drones)
function eliminarUsuario($id_usuario) {
    $conexion = conectar();
    mysqli_begin_transaction($conexion);

    try {
        // 1. Eliminar roles
        mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $id_usuario");

        // 2. Eliminar relaciones con parcelas
        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $id_usuario");

        // 3. Obtener drones asignados al usuario
        $drones = [];
        $consultaDrones = mysqli_query($conexion, "SELECT id_dron FROM drones WHERE id_usr = $id_usuario");
        while ($row = mysqli_fetch_assoc($consultaDrones)) {
            $drones[] = $row['id_dron'];
        }

        // 4. Eliminar trabajos y tareas asociados a esos drones
        if (!empty($drones)) {
            $idsDrones = implode(",", $drones);

            mysqli_query($conexion, "
                DELETE tt FROM trabajos_tareas tt 
                INNER JOIN trabajos t ON tt.id_trabajo = t.id_trabajo 
                WHERE t.id_dron IN ($idsDrones)
            ");
            mysqli_query($conexion, "DELETE FROM trabajos WHERE id_dron IN ($idsDrones)");
        }

        // 5. Desvincular drones
        mysqli_query($conexion, "UPDATE drones SET id_usr = NULL WHERE id_usr = $id_usuario");

        // 6. Eliminar usuario
        mysqli_query($conexion, "DELETE FROM usuarios WHERE id_usr = $id_usuario");

        mysqli_commit($conexion);
        return [
            'success' => true,
            'mensaje' => 'Usuario eliminado correctamente. Drones desvinculados.'
        ];

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        return [
            'success' => false,
            'mensaje' => 'Error al eliminar usuario: ' . $e->getMessage()
        ];
    }
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $resultado = eliminarUsuario($id_usuario);

    $_SESSION['mensaje'] = $resultado['mensaje'];
    $_SESSION['mensaje_tipo'] = $resultado['success'] ? 'exito' : 'error';
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener usuarios que NO sean admin
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Usuarios - AgroSky</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/eliminarUsuarios.css">
</head>
<body>
    <div class="container-eliminar-usuario">
        <?php 
        if (isset($_SESSION['mensaje'])) {
            $tipo_clase = $_SESSION['mensaje_tipo'] === 'exito' ? 'mensaje-exito' : 'mensaje-error';
            echo "<div class='mensaje {$tipo_clase}'>" . htmlspecialchars($_SESSION['mensaje']) . "</div>";
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
        }
        ?>

        <h2 class="titulo-eliminacion">Eliminar Usuarios</h2>
        
        <div class="buscador">
            <input type="text" placeholder="Buscar usuario" id="buscarUsuario">
            <button>Buscar</button>
        </div>

        <div class="tabla-usuarios">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Parcelas</th>
                        <th></th>
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
                                <form method="post" action="">
                                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usr'] ?>">
                                    <button type="button" class="btn-eliminar" onclick="confirmarEliminacion(this)">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No hay usuarios disponibles para eliminar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="botones-acciones">
            <a href="../usuarios.php" class="btn-volver">Volver</a>
        </div>
    </div>

    <!-- Modal Confirmación -->
    <div id="modalConfirmacion" class="modal-confirmacion" style="display:none;">
        <div class="modal-contenido">
            <h3>Confirmar Eliminación</h3>
            <p>¿Estás seguro de que deseas eliminar este usuario?</p>
            <div class="modal-botones">
                <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-eliminar" id="btnConfirmarEliminacion">Eliminar</button>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(boton) {
        const modal = document.getElementById('modalConfirmacion');
        const formulario = boton.closest('form');
        document.getElementById('btnConfirmarEliminacion').onclick = function () {
            formulario.submit();
        };
        modal.style.display = 'flex';
    }

    function cerrarModal() {
        document.getElementById('modalConfirmacion').style.display = 'none';
    }

    document.getElementById('buscarUsuario').addEventListener('input', function () {
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('.tabla-usuarios tbody tr').forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
