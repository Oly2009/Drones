<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<div class='modal error'><p>⛔ Acceso denegado</p><a href='../../index.php' class='btn'>Volver</a></div>";
    session_destroy();
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

$filtro = '';
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $filtro = " AND (d.marca LIKE '%$busqueda%' OR d.modelo LIKE '%$busqueda%' OR u.nombre LIKE '%$busqueda%' OR u.apellidos LIKE '%$busqueda%' OR p.ubicacion LIKE '%$busqueda%')";
}

$sql = "SELECT 
            d.id_dron, d.marca, d.modelo, d.numero_serie, d.tipo, d.estado, d.numero_vuelos,
            p.ubicacion AS parcela,
            t.nombre_tarea AS tarea,
            u.nombre AS usuario_nombre, u.apellidos AS usuario_apellidos,
            tr.fecha, tr.hora, tr.estado_general
        FROM drones d
        LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
        LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
        LEFT JOIN usuarios u ON d.id_usr = u.id_usr
        LEFT JOIN trabajos tr ON d.id_dron = tr.id_dron";

$sql .= $esAdmin ? " WHERE 1" : " WHERE d.id_usr = '$idUsr'";
$sql .= $filtro;
$sql .= " ORDER BY d.id_dron ASC";

$consulta = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado Completo de Drones</title>
     <link rel="stylesheet" href="../../css/listarUsuarios.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>
    <h1 class="titulo">🛰️ Listado de Drones</h1>

    <div class="contenedor">
        <form method="get" class="busqueda-form">
            <input type="text" name="buscar" placeholder="🔍 Buscar por marca, modelo ..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
            <button type="submit">Buscar</button>
        </form>

        <?php if (mysqli_num_rows($consulta) > 0): ?>
            <div class="tabla-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>N.º Serie</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Vuelos</th>
                            <th>Parcela</th>
                            <th>Tarea asignada</th>
                            <th>Responsable</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado Trabajo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = mysqli_fetch_assoc($consulta)): ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['marca']) ?></td>
                                <td><?= htmlspecialchars($fila['modelo']) ?></td>
                                <td><?= htmlspecialchars($fila['numero_serie']) ?></td>
                                <td><?= ucfirst($fila['tipo']) ?></td>
                                <td class="estado <?= str_replace(' ', '-', strtolower($fila['estado'])) ?>">
                                    <?= ucfirst($fila['estado']) ?>
                                </td>
                                <td><?= $fila['numero_vuelos'] ?></td>
                                <td><?= htmlspecialchars($fila['parcela'] ?? 'Sin asignar') ?></td>
                                <td><?= htmlspecialchars($fila['tarea'] ?? 'Sin tarea') ?></td>
                                <td><?= htmlspecialchars($fila['usuario_nombre'] . ' ' . $fila['usuario_apellidos']) ?></td>
                                <td><?= $fila['fecha'] ?? '---' ?></td>
                                <td><?= $fila['hora'] ?? '---' ?></td>
                                <td><?= ucfirst($fila['estado_general'] ?? '---') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="mensaje-error">
                ❌ No se encontraron resultados.
            </div>
        <?php endif; ?>

        <a href="../../menu/drones.php" class="btn">⬅ Volver al menú</a>
    </div>
</body>
</html>
