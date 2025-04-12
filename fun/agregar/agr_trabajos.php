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

// Verificar si es admin
$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idUsr");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
}

if (!$esAdmin) {
    $_SESSION['mensaje'] = "Acceso denegado. Solo los administradores pueden añadir trabajos.";
    $_SESSION['tipo'] = "error";
    header("Location: ../menu.php");
    exit();
}

if (isset($_POST['insertar'])) {
    $id_parcela = intval($_POST['parcela']);
    $id_dron = intval($_POST['dron']);
    $id_usuario = intval($_POST['usuario']);

    // Obtener la tarea asignada al dron
    $tareaQuery = mysqli_query($conexion, "SELECT id_tarea FROM drones WHERE id_dron = $id_dron");
    $row = mysqli_fetch_assoc($tareaQuery);
    $id_tarea = intval($row['id_tarea']);

    $insert = "INSERT INTO trabajos (id_parcela, id_dron, id_tarea, id_usr, estado_general, fecha, hora)
               VALUES ($id_parcela, $id_dron, $id_tarea, $id_usuario, 'pendiente', CURDATE(), CURTIME())";

    if (mysqli_query($conexion, $insert)) {
        $_SESSION['mensaje'] = "✅ Trabajo añadido correctamente.";
        $_SESSION['tipo'] = "exito";
    } else {
        $_SESSION['mensaje'] = "❌ Error al añadir el trabajo.";
        $_SESSION['tipo'] = "error";
    }
    header("Location: agr_trabajo.php");
    exit();
}

$drones = mysqli_query($conexion, "
    SELECT d.id_dron, d.marca, d.modelo, d.numero_serie, d.id_tarea, d.id_parcela, d.estado, t.nombre_tarea, p.ubicacion
    FROM drones d
    JOIN tareas t ON d.id_tarea = t.id_tarea
    JOIN parcelas p ON d.id_parcela = p.id_parcela
");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Trabajo</title>
    <link rel="stylesheet" href="../../css/listarDrones.css">
</head>
<body>
<h2 class="titulo">Añadir Trabajo</h2>

<?php if (isset($_SESSION['mensaje'])): ?>
    <div class="modal <?= $_SESSION['tipo'] ?>">
        <?= $_SESSION['mensaje'] ?>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
<?php endif; ?>

<div class="tabla-container">
    <form method="post">
        <table>
            <thead>
                <tr>
                    <th>Dron</th>
                    <th>Tarea</th>
                    <th>Parcela Asignada</th>
                    <th>Estado</th>
                    <th>Asignar a Piloto</th>
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
                    <input type="hidden" name="dron" value="<?= $dron['id_dron'] ?>">
                    <input type="hidden" name="parcela" value="<?= $dron['id_parcela'] ?>">
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
                            <span style="color: red; font-weight: bold">Sin pilotos asignados</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($dron['estado'] == 'disponible' && mysqli_num_rows($pilotos) > 0): ?>
                            <button type="button" class="btn btn-secundario">Agregar</button>
                        <?php else: ?>
                            ---
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </form>
</div>

<div class="volver-contenedor">
    <a href="../../menu/trabajos.php" class="btn btn-secundario">Volver</a>
</div>
</body>
</html>
