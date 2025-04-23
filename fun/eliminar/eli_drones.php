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
$rolCheck = mysqli_query($conexion, "select id_rol from usuarios_roles where id_usr = $idUsr");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
}

if (!$esAdmin) {
    $_SESSION['mensaje'] = "Acceso denegado. Solo los administradores pueden eliminar drones.";
    $_SESSION['tipo'] = "error";
    header("Location: ../menu.php");
    exit();
}

if (isset($_POST['eliminar_dron'])) {
    $id_dron = intval($_POST['id_dron']);
    $query = "update drones set id_parcela = null, id_tarea = null where id_dron = $id_dron";
    mysqli_query($conexion, $query);
    $delete = "delete from drones where id_dron = $id_dron";
    if (mysqli_query($conexion, $delete)) {
        $_SESSION['mensaje'] = "✅ Dron eliminado correctamente.";
        $_SESSION['tipo'] = "exito";
    } else {
        $_SESSION['mensaje'] = "❌ Error al eliminar el dron.";
        $_SESSION['tipo'] = "error";
    }
    header("Location: eliminar_drones.php");
    exit();
}

$drones = mysqli_query($conexion, "
    select d.*, p.ubicacion as nombre_parcela, t.nombre_tarea 
    from drones d 
    left join parcelas p on d.id_parcela = p.id_parcela
    left join tareas t on d.id_tarea = t.id_tarea
    where d.estado = 'fuera de servicio'
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Drones Fuera de Servicio</title>
    <link rel="stylesheet" href="../../css/listarUsuarios.css">
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
    <h1 class="titulo">Eliminar Drones </h1>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="modal <?= $_SESSION['tipo'] ?>">
            <?= $_SESSION['mensaje'] ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
    <?php endif; ?>

    <div class="contenedor">
        <div class="tabla-responsive">
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
                                    <button type="button" class="btn-eliminar" onclick="confirmarEliminacion(<?= $dron['id_dron'] ?>)">Eliminar</button>

                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="../../menu/drones.php" class="btn">🔙 Volver al menú</a>
    </div>

    <!-- Modal de confirmación -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 999;">
        <div class="modal-content" style="background-color: #ffffff; padding: 30px; border-radius: 15px; max-width: 400px; text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,0.3); border: 2px solid #e57373;">
            <p style="margin-bottom: 20px; font-weight: bold; color: #c62828;">¿Estás seguro de que deseas eliminar este dron?</p>
            <button id="confirmAceptar" class="btn">Aceptar</button>
            <button id="confirmCancelar" class="btn btn-secundario">Cancelar</button>
        </div>
    </div>
</body>
</html>
