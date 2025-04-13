<?php
include '../../lib/functiones.php';
session_start();

$mensaje = null;
$tipo = null;

if (!isset($_SESSION['usuario'])) {
    $mensaje = "Acceso denegado";
    $tipo = "error";
} else {
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

    if (!$esAdmin) {
        $mensaje = "Acceso denegado. Solo los administradores pueden añadir trabajos.";
        $tipo = "error";
    }

    if ($esAdmin && isset($_POST['insertar'])) {
        $id_parcela = intval($_POST['parcela']);
        $id_dron = intval($_POST['dron']);
        $id_usuario = isset($_POST['usuario']) ? intval($_POST['usuario']) : null;
        $id_tarea = intval($_POST['tarea']);

        if (is_null($id_usuario)) {
            $mensaje = "⚠ No se ha seleccionado ningún piloto.";
            $tipo = "error";
        } else {
            $dronInfo = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_tarea, estado FROM drones WHERE id_dron = $id_dron"));
            $tarea_dron = $dronInfo['id_tarea'];
            $estado_dron = $dronInfo['estado'];

            if ($estado_dron == 'en reparación' || $estado_dron == 'fuera de servicio') {
                $mensaje = "🚫 El dron no está disponible para nuevos trabajos.";
                $tipo = "error";
            } elseif ($id_tarea != $tarea_dron) {
                $mensaje = "⚠ La tarea seleccionada no coincide con la tarea asignada al dron.";
                $tipo = "error";
            } else {
                $insert = "INSERT INTO trabajos (id_parcela, id_dron, id_usr, estado_general, fecha, hora)
                           VALUES ($id_parcela, $id_dron, $id_usuario, 'pendiente', CURDATE(), CURTIME())";

                if (mysqli_query($conexion, $insert)) {
                    $id_trabajo = mysqli_insert_id($conexion);
                    mysqli_query($conexion, "INSERT INTO trabajos_tareas (id_trabajo, id_tarea) VALUES ($id_trabajo, $id_tarea)");

                    if ($estado_dron == 'disponible') {
                        mysqli_query($conexion, "UPDATE drones SET estado = 'en uso' WHERE id_dron = $id_dron");
                    }

                    $mensaje = "✅ Trabajo asignado correctamente. El dron ha pasado a estado 'en uso'.";
                    $tipo = "exito";
                } else {
                    $mensaje = "❌ Error al asignar el trabajo.";
                    $tipo = "error";
                }
            }
        }
    }

    $drones = mysqli_query($conexion, "
        SELECT d.id_dron, d.marca, d.modelo, d.numero_serie, d.id_tarea, d.id_parcela, d.estado, t.nombre_tarea, p.ubicacion,
               (SELECT COUNT(*) FROM trabajos WHERE id_dron = d.id_dron AND estado_general != 'finalizado') AS trabajos_activos
        FROM drones d
        JOIN tareas t ON d.id_tarea = t.id_tarea
        JOIN parcelas p ON d.id_parcela = p.id_parcela
    ");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Trabajo</title>
    <link rel="stylesheet" href="../../css/listarDrones.css">
    <script>
        window.onload = function() {
            const modal = document.querySelector('.modal');
            if (modal) {
                modal.style.display = 'flex';
            }

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const estado = form.closest('tr').querySelector('td:nth-child(4)').innerText.trim().toLowerCase();
                    const piloto = form.querySelector('select[name="usuario"]');

                    if (!piloto || piloto.disabled || piloto.value === '') {
                        e.preventDefault();
                        mostrarModal("⚠ Por favor, selecciona un piloto.", 'error');
                        return;
                    }

                    if (estado === 'en reparación' || estado === 'fuera de servicio') {
                        e.preventDefault();
                        mostrarModal("🚫 El dron no puede ser utilizado debido a su estado actual.", 'error');
                    }
                });
            });
        };

        function mostrarModal(mensaje, tipo) {
            const modal = document.createElement('div');
            modal.className = 'modal ' + tipo;
            modal.innerHTML = `
                <div class="modal-content">
                    ${mensaje}<br><br>
                    <button class="btn btn-secundario" onclick="this.closest('.modal').remove()">Cerrar</button>
                </div>`;
            document.body.appendChild(modal);
        }
    </script>
</head>
<body>
<h2 class="titulo">➕ Añadir Trabajo</h2>

<?php if ($mensaje): ?>
    <div class="modal <?= $tipo ?>">
        <div class="modal-content">
            <?= $mensaje ?><br><br>
            <button class="btn btn-secundario" onclick="this.closest('.modal').style.display='none'">Cerrar</button>
        </div>
    </div>
<?php endif; ?>

<?php if ($esAdmin): ?>
<div class="tabla-container">
    <table>
        <thead>
            <tr>
                <th>Dron</th>
                <th>Tarea</th>
                <th>Parcela</th>
                <th>Estado</th>
                <th>Piloto</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
            <?php
                $pilotos = mysqli_query($conexion, "
                    SELECT u.id_usr, u.nombre, u.apellidos
                    FROM usuarios u
                    JOIN usuarios_roles ur ON u.id_usr = ur.id_usr AND ur.id_rol = 2
                    JOIN parcelas_usuarios pu ON pu.id_usr = u.id_usr
                    WHERE pu.id_parcela = " . $dron['id_parcela'] . "
                ");
            ?>
            <tr>
                <form method="post">
                    <input type="hidden" name="dron" value="<?= $dron['id_dron'] ?>">
                    <input type="hidden" name="parcela" value="<?= $dron['id_parcela'] ?>">
                    <input type="hidden" name="tarea" value="<?= $dron['id_tarea'] ?>">

                    <td><?= htmlspecialchars($dron['marca'] . ' ' . $dron['modelo']) ?></td>
                    <td><?= htmlspecialchars($dron['nombre_tarea']) ?></td>
                    <td><?= htmlspecialchars($dron['ubicacion']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($dron['estado'])) ?></td>

                    <td>
                        <?php if (mysqli_num_rows($pilotos) > 0): ?>
                            <select name="usuario" required>
                                <option value="">Seleccionar piloto</option>
                                <?php while ($piloto = mysqli_fetch_assoc($pilotos)): ?>
                                    <option value="<?= $piloto['id_usr'] ?>">
                                        <?= $piloto['nombre'] ?> <?= $piloto['apellidos'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="usuario" value="">
                            <span style="color: red; font-weight: bold">Sin pilotos</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <button type="submit" name="insertar" class="btn btn-secundario"
                        <?= ($dron['estado'] == 'en reparación' || $dron['estado'] == 'fuera de servicio') ? 'disabled' : '' ?>>Agregar</button>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="volver-contenedor">
    <a href="../../menu/trabajos.php" class="btn btn-secundario">Volver</a>
</div>
</body>
</html>
