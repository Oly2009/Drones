<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    $_SESSION['mensaje'] = "Acceso denegado";
    $_SESSION['tipo'] = "error";
    header("Location: ../../index.php");
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

if (!$esAdmin) {
    $_SESSION['mensaje'] = "Acceso denegado. Solo los administradores pueden modificar drones.";
    $_SESSION['tipo'] = "error";
    header("Location: ../menu.php");
    exit();
}

if (isset($_POST['eliminar_dron'])) {
    $id_dron = intval($_POST['id_dron']);
    $query = "UPDATE drones SET id_parcela = NULL, id_tarea = NULL WHERE id_dron = $id_dron";
    mysqli_query($conexion, $query);
    $delete = "DELETE FROM drones WHERE id_dron = $id_dron";
    if (mysqli_query($conexion, $delete)) {
        $_SESSION['mensaje'] = "✅ Dron eliminado correctamente.";
        $_SESSION['tipo'] = "exito";
    } else {
        $_SESSION['mensaje'] = "❌ Error al eliminar el dron.";
        $_SESSION['tipo'] = "error";
    }
    header("Location: mod_drones.php");
    exit();
}

$drones = mysqli_query($conexion, "
    SELECT d.*, p.ubicacion AS nombre_parcela, t.nombre_tarea 
    FROM drones d 
    LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
    LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
    WHERE d.estado = 'fuera de servicio'
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Drones Fuera de Servicio</title>
    <link rel="stylesheet" href="../../css/listarDrones.css">
    <script>
    function confirmarEliminacion(id) {
        const modal = document.getElementById("confirmModal");
        const aceptar = document.getElementById("confirmAceptar");
        const cancelar = document.getElementById("confirmCancelar");
        modal.style.display = "flex";

        aceptar.onclick = () => {
            document.getElementById('form_eliminar_' + id).submit();
        };
        cancelar.onclick = () => {
            modal.style.display = "none";
        };
    }
    </script>
    
</head>
<body>
    <h2 class="titulo">Eliminar Drones Fuera de Servicio</h2>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="modal <?= $_SESSION['tipo'] ?>">
            <?= $_SESSION['mensaje'] ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
    <?php endif; ?>

    <div class="tabla-container">
        <table>
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>N.º Serie</th>
                    <th>Estado</th>
                    <th>Parcela</th>
                    <th>Tarea</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
                    <tr>
                        <td><?= htmlspecialchars($dron['marca']) ?></td>
                        <td><?= htmlspecialchars($dron['modelo']) ?></td>
                        <td><?= htmlspecialchars($dron['numero_serie']) ?></td>
                        <td class="estado-fuera-de-servicio">Fuera de servicio</td>
                        <td><?= htmlspecialchars($dron['nombre_parcela'] ?? 'Ninguna') ?></td>
                        <td><?= htmlspecialchars($dron['nombre_tarea'] ?? 'Ninguna') ?></td>
                        <td>
                            <form id="form_eliminar_<?= $dron['id_dron'] ?>" method="post">
                                <input type="hidden" name="eliminar_dron" value="1">
                                <input type="hidden" name="id_dron" value="<?= $dron['id_dron'] ?>">
                                <button type="button" class="btn" style="background-color:#e57373;color:white;" onclick="confirmarEliminacion(<?= $dron['id_dron'] ?>)">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="volver-contenedor">
        <a href="../../menu/drones.php" class="btn btn-secundario">Volver</a>
    </div>

    <div id="confirmModal">
        <div class="modal-content">
            <p>¿Estás seguro de que deseas eliminar este dron?</p>
            <button id="confirmAceptar" class="btn">Aceptar</button>
            <button id="confirmCancelar" class="btn btn-secundario">Cancelar</button>
        </div>
    </div>
</body>
</html>