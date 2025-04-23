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
    $_SESSION['mensaje'] = "Acceso denegado. Solo los administradores pueden modificar drones.";
    $_SESSION['tipo'] = "error";
    header("Location: ../menu.php");
    exit();
}

$mensaje = '';
$tipo = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo = $_SESSION['tipo'];
    unset($_SESSION['mensaje'], $_SESSION['tipo']);
}

if (isset($_POST['confirmar_modificacion'])) {
    $id_dron = intval($_POST['id_dron']);

    $res = mysqli_query($conexion, "select estado from drones where id_dron = $id_dron");
    $actual = mysqli_fetch_assoc($res);
    $estado_actual = $actual['estado'];

    if ($estado_actual == 'en uso') {
        $_SESSION['mensaje'] = "🚫 No se puede modificar un dron que está en uso actualmente.";
        $_SESSION['tipo'] = "error";
    } elseif ($estado_actual == 'fuera de servicio') {
        $_SESSION['mensaje'] = "⚠ Este dron está fuera de servicio y no se puede modificar.";
        $_SESSION['tipo'] = "error";
    } else {
        $estado = $_POST['estado'];
        $id_parcela = intval($_POST['parcela']);
        $id_tarea = intval($_POST['tarea']);

        $query = "update drones set estado = '$estado', id_parcela = $id_parcela, id_tarea = $id_tarea where id_dron = $id_dron";
        if (mysqli_query($conexion, $query)) {
            $_SESSION['mensaje'] = "✅ Dron actualizado correctamente.";
            $_SESSION['tipo'] = "exito";
        } else {
            $_SESSION['mensaje'] = "❌ Error al actualizar el dron.";
            $_SESSION['tipo'] = "error";
        }
    }

    header("Location: mod_drones.php");
    exit();
}

$drones = mysqli_query($conexion, "select * from drones");
$parcelas = mysqli_query($conexion, "select * from parcelas");
$tareas = mysqli_query($conexion, "select * from tareas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Drones</title>
    <link rel="stylesheet" href="../../css/listarUsuarios.css">
    <script>
        let droneEstados = {};

        function filtrarTabla() {
            let input = document.getElementById('buscar').value.toLowerCase();
            let filas = document.querySelectorAll('tbody tr');
            filas.forEach(fila => {
                fila.style.display = fila.textContent.toLowerCase().includes(input) ? '' : 'none';
            });
        }

        function confirmarActualizacion(id) {
            let estado = droneEstados[id];
            const modal = document.getElementById("confirmModal");
            const modalMsg = document.getElementById("confirmText");

            if (estado === 'en uso') {
                modalMsg.textContent = "🚫 No se puede modificar un dron que está en uso actualmente.";
                document.getElementById("confirmAceptar").style.display = "none";
            } else if (estado === 'fuera de servicio') {
                modalMsg.textContent = "⚠ Este dron está fuera de servicio y no se puede modificar.";
                document.getElementById("confirmAceptar").style.display = "none";
            } else {
                modalMsg.textContent = "¿Estás seguro de que quieres modificar este dron?";
                document.getElementById("confirmAceptar").style.display = "inline-block";
                document.getElementById("confirmAceptar").onclick = function () {
                    document.getElementById('form_' + id).submit();
                };
            }

            modal.style.display = "flex";
        }

        window.onload = function () {
            document.getElementById("confirmCancelar").onclick = function () {
                document.getElementById("confirmModal").style.display = "none";
            };

            // Mostrar modal de mensaje si existe
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
</head>
<body>
    <h1 class="titulo">Modificar Drones</h1>

    <div class="tabla-container contenedor">
        <form class="busqueda-form" onsubmit="event.preventDefault(); filtrarTabla();">
            <input type="text" id="buscar" placeholder="Buscar por marca, modelo o serie...">
            <button type="submit" class="btn">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>N.º Serie</th>
                    <th>Estado</th>
                    <th>Parcela</th>
                    <th>Tarea</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
                    <?php
                        $id = $dron['id_dron'];
                        $estado = $dron['estado'];
                        $esEnUso = $estado === 'en uso';
                        $esFueraServicio = $estado === 'fuera de servicio';
                        $disabled = ($esEnUso || $esFueraServicio) ? 'disabled' : '';
                    ?>
                    <script>droneEstados[<?= $id ?>] = '<?= $estado ?>';</script>
                    <tr>
                        <form id="form_<?= $id ?>" method="post">
                            <input type="hidden" name="confirmar_modificacion" value="1">
                            <input type="hidden" name="id_dron" value="<?= $id ?>">
                            <td><?= htmlspecialchars($dron['marca']) ?></td>
                            <td><?= htmlspecialchars($dron['modelo']) ?></td>
                            <td><?= htmlspecialchars($dron['numero_serie']) ?></td>
                            <td>
                                <?php if ($esEnUso || $esFueraServicio): ?>
                                    <span class="estado-fijo"><?= ucfirst($estado) ?></span>
                                    <input type="hidden" name="estado" value="<?= $estado ?>">
                                <?php else: ?>
                                    <select name="estado">
                                        <option value="disponible" <?= $estado == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                        <option value="en reparación" <?= $estado == 'en reparación' ? 'selected' : '' ?>>En reparación</option>
                                        <option value="fuera de servicio" <?= $estado == 'fuera de servicio' ? 'selected' : '' ?>>Fuera de servicio</option>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="parcela" <?= $disabled ?>>
                                    <?php mysqli_data_seek($parcelas, 0); ?>
                                    <?php while ($parcela = mysqli_fetch_assoc($parcelas)): ?>
                                        <option value="<?= $parcela['id_parcela'] ?>" <?= $parcela['id_parcela'] == $dron['id_parcela'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parcela['ubicacion']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <select name="tarea" <?= $disabled ?>>
                                    <?php mysqli_data_seek($tareas, 0); ?>
                                    <?php while ($tarea = mysqli_fetch_assoc($tareas)): ?>
                                        <option value="<?= $tarea['id_tarea'] ?>" <?= $tarea['id_tarea'] == $dron['id_tarea'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tarea['nombre_tarea']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-secundario" onclick="confirmarActualizacion(<?= $id ?>)">Actualizar</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="volver-contenedor">
        <a href="../../menu/drones.php" class="btn btn-secundario">Volver</a>
    </div>

    <!-- Modal Confirmación -->
    <div id="confirmModal">
        <div class="modal-content">
            <p id="confirmText">¿Estás seguro de que quieres modificar este dron?</p>
            <button id="confirmAceptar" class="btn">Aceptar</button>
            <button id="confirmCancelar" class="btn btn-secundario">Cancelar</button>
        </div>
    </div>

    <!-- Modal de Mensajes -->
    <div id="alertModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
        <div id="alertBox" style="background:white; padding:30px 40px; border-radius:15px; text-align:center; max-width:500px; box-shadow:0 0 15px rgba(0,0,0,0.3); border:2px solid;">
            <p id="alertText" style="font-weight:bold; font-size:1rem; margin-bottom:20px;"></p>
            <button id="alertCerrar" class="btn">Cerrar</button>
        </div>
    </div>
</body>
</html>
