<?php
session_start();
include '../../lib/functiones.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Drones</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="container flex-grow-1 py-5">
  <section>
    <h1 class="titulo-listado text-center">
      <i class="bi bi-hdd-rack-fill me-2" style="color: #6f42c1;"></i>Listado de Drones
    </h1>

      <form method="get" class="d-flex justify-content-center mb-4">
        <input type="text" name="buscar" class="form-control w-50 me-2" placeholder="🔍 Buscar por marca, modelo, usuario..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
        <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Buscar</button>
    </form>
       

    <?php if (mysqli_num_rows($consulta) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-success text-center">
                <tr>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>N.º Serie</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Vuelos</th>
                    <th>Parcela</th>
                    <th>Tarea</th>
                    <th>Responsable</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Trabajo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($consulta)): ?>
                <tr>
                    <td><?= htmlspecialchars($fila['marca']) ?></td>
                    <td><?= htmlspecialchars($fila['modelo']) ?></td>
                    <td><?= htmlspecialchars($fila['numero_serie']) ?></td>
                    <td><?= htmlspecialchars($fila['tipo'] ?? '---') ?></td>
                    <td><?= ucfirst($fila['estado']) ?></td>
                    <td><?= $fila['numero_vuelos'] ?></td>
                    <td><?= htmlspecialchars($fila['parcela'] ?? 'Sin asignar') ?></td>
                    <td><?= htmlspecialchars($fila['tarea'] ?? 'Sin tarea') ?></td>
                    <td><?= htmlspecialchars(trim($fila['usuario_nombre'] . ' ' . $fila['usuario_apellidos'])) ?></td>
                    <td><?= $fila['fecha'] ?? '---' ?></td>
                    <td><?= $fila['hora'] ?? '---' ?></td>
                    <td><?= ucfirst($fila['estado_general'] ?? '---') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">❌ No se encontraron resultados.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../../menu/drones.php" class="btn btn-danger rounded-pill px-4">
            <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de drones
        </a>
    </div>
  </section>
</main>

<?php include '../../componentes/footer.php'; ?>
</div>
</body>
</html>
