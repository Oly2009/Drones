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

if (isset($_POST['confirmar_modificacion'])) {
    $id_dron = intval($_POST['id_dron']);
    $estado = $_POST['estado'];
    $id_parcela = intval($_POST['parcela']);
    $id_tarea = intval($_POST['tarea']);

    $query = "UPDATE drones SET estado = '$estado', id_parcela = $id_parcela, id_tarea = $id_tarea WHERE id_dron = $id_dron";
    if (mysqli_query($conexion, $query)) {
        $_SESSION['mensaje'] = "✅ Dron actualizado correctamente.";
        $_SESSION['tipo'] = "exito";
    } else {
        $_SESSION['mensaje'] = "❌ Error al actualizar el dron.";
        $_SESSION['tipo'] = "error";
    }
    header("Location: mod_drones.php");
    exit();
}

$drones = mysqli_query($conexion, "SELECT * FROM drones");
$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas");
$tareas = mysqli_query($conexion, "SELECT * FROM tareas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Drones</title>
    <link rel="stylesheet" href="../../css/listarDrones.css">
    <script>
    function filtrarTabla() {
        let input = document.getElementById('buscar').value.toLowerCase();
        let filas = document.querySelectorAll('tbody tr');
        filas.forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(input) ? '' : 'none';
        });
    }

    let droneIdConfirmar = null;
    function confirmarActualizacion(id) {
        droneIdConfirmar = id;
        const modal = document.getElementById("confirmModal");
        modal.style.display = "flex";
    }

    window.onload = function () {
        document.getElementById("confirmAceptar").onclick = function () {
            document.getElementById('form_' + droneIdConfirmar).submit();
        };
        document.getElementById("confirmCancelar").onclick = function () {
            document.getElementById("confirmModal").style.display = "none";
            droneIdConfirmar = null;
        };
    }
    </script>
    
</head>
<body>
    <h2 class="titulo">Modificar Drones</h2>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="modal <?= $_SESSION['tipo'] ?>">
            <?= $_SESSION['mensaje'] ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
    <?php endif; ?>

    <div class="buscador-container">
        <input type="text" id="buscar" placeholder="Buscar por marca, modelo o serie..." onkeyup="filtrarTabla()">
        <button onclick="filtrarTabla()">Buscar</button>
    </div>

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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
                    <tr>
                        <form id="form_<?= $dron['id_dron'] ?>" method="post">
                            <input type="hidden" name="confirmar_modificacion" value="1">
                            <input type="hidden" name="id_dron" value="<?= $dron['id_dron'] ?>">
                            <td><?= htmlspecialchars($dron['marca']) ?></td>
                            <td><?= htmlspecialchars($dron['modelo']) ?></td>
                            <td><?= htmlspecialchars($dron['numero_serie']) ?></td>
                            <td>
                                <select name="estado">
                                    <option value="disponible" <?= $dron['estado'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                    <option value="en uso" <?= $dron['estado'] == 'en uso' ? 'selected' : '' ?>>En uso</option>
                                    <option value="en reparación" <?= $dron['estado'] == 'en reparación' ? 'selected' : '' ?>>En reparación</option>
                                    <option value="fuera de servicio" <?= $dron['estado'] == 'fuera de servicio' ? 'selected' : '' ?>>Fuera de servicio</option>
                                </select>
                            </td>
                            <td>
                                <select name="parcela">
                                    <?php mysqli_data_seek($parcelas, 0); ?>
                                    <?php while ($parcela = mysqli_fetch_assoc($parcelas)): ?>
                                        <option value="<?= $parcela['id_parcela'] ?>" <?= $parcela['id_parcela'] == $dron['id_parcela'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parcela['ubicacion']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <select name="tarea">
                                    <?php mysqli_data_seek($tareas, 0); ?>
                                    <?php while ($tarea = mysqli_fetch_assoc($tareas)): ?>
                                        <option value="<?= $tarea['id_tarea'] ?>" <?= $tarea['id_tarea'] == $dron['id_tarea'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tarea['nombre_tarea']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-secundario" onclick="confirmarActualizacion(<?= $dron['id_dron'] ?>)">Actualizar</button>
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

    <div id="confirmModal">
        <div class="modal-content">
            <p>¿Estás seguro de que quieres modificar este dron?</p>
            <button id="confirmAceptar" class="btn">Aceptar</button>
            <button id="confirmCancelar" class="btn btn-secundario">Cancelar</button>
        </div>
    </div>
</body>
</html>
