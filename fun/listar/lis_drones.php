<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>Acceso denegado</p>";
    echo '<a href="javascript:history.back()"><button>Volver</button></a>';
    session_destroy();
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

// Consulta
if ($esAdmin) {
    $sql = "SELECT d.id_dron, d.nombre, d.marca, d.estado, p.ubicacion
            FROM drones d
            LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
            ORDER BY d.id_dron ASC";
} else {
    $sql = "SELECT d.id_dron, d.nombre, d.marca, d.estado, p.ubicacion
            FROM drones d
            LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
            WHERE d.id_usr = '$idUsr'
            ORDER BY d.id_dron ASC";
}

$consulta = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Drones</title>
    <link rel="stylesheet" href="../../css/listarDrones.css">
</head>
<body>
    <h2>Listado de Drones</h2>

    <?php if (mysqli_num_rows($consulta) > 0): ?>
        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Estado</th>
                        <th>Parcela asignada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = mysqli_fetch_assoc($consulta)): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td><?= htmlspecialchars($fila['marca']) ?></td>
                            <td class="<?= $fila['estado'] === 'disponible' ? 'estado-disponible' : 'estado-estropeado' ?>">
                                <?= ucfirst($fila['estado']) ?>
                            </td>
                            <td><?= $fila['ubicacion'] ?? 'Sin asignar' ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center;">No hay drones disponibles.</p>
    <?php endif; ?>

    <div class="volver-contenedor">
        <form action="../drones.php" method="post">
            <input type="submit" name="volverReg" value="Volver">
        </form>
    </div>
</body>
</html>
    