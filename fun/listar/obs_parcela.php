<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso denegado",
                text: "Debes iniciar sesión para acceder a esta página",
                icon: "error",
                confirmButtonText: "Volver",
                confirmButtonColor: "#dc3545"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../index.php";
                }
            });
        });
    </script>';
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../../menu/parcelas.php');
    exit;
}

$id_parcela = (int) $_GET['id'];
$conexion = conectar();

$parcela = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM parcelas WHERE id_parcela = $id_parcela"));

$usuarios = mysqli_query($conexion, "SELECT u.nombre, u.apellidos, u.email FROM usuarios u INNER JOIN parcelas_usuarios pu ON u.id_usr = pu.id_usr WHERE pu.id_parcela = $id_parcela");

$drones = mysqli_query($conexion, "SELECT d.marca, d.modelo, d.estado FROM drones d WHERE d.id_parcela = $id_parcela");

$trabajos = mysqli_query($conexion, "SELECT t.fecha, t.hora, u.nombre, d.marca, d.modelo, t.estado_general FROM trabajos t LEFT JOIN usuarios u ON t.id_usr = u.id_usr LEFT JOIN drones d ON t.id_dron = d.id_dron WHERE t.id_parcela = $id_parcela ORDER BY t.fecha DESC");

$tiene_ruta = mysqli_num_rows(mysqli_query($conexion, "SELECT id_ruta FROM ruta WHERE id_parcela = $id_parcela")) > 0;

include '../../componentes/header.php';
?>
<link rel="stylesheet" href="../../css/style.css">

<body class="d-flex flex-column min-vh-100">
<main class="container detalles-parcela">
  <h2><i class="bi bi-clipboard2-data"></i> Detalles de la Parcela</h2>
  <table class="tabla-detalle">
    <tr><th>Nombre:</th><td><?= htmlspecialchars($parcela['nombre']) ?></td></tr>
    <tr><th>Ubicación:</th><td><?= htmlspecialchars($parcela['ubicacion']) ?></td></tr>
    <tr><th>Tipo de cultivo:</th><td><?= htmlspecialchars($parcela['tipo_cultivo']) ?></td></tr>
    <tr><th>Área:</th><td><?= number_format($parcela['area_m2'], 2) ?> m²</td></tr>
    <tr><th>Estado:</th><td><?= htmlspecialchars($parcela['estado']) ?></td></tr>
    <tr><th>Fecha de registro:</th><td><?= htmlspecialchars($parcela['fecha_registro']) ?></td></tr>
    <tr><th>Observaciones:</th><td><?= nl2br(htmlspecialchars($parcela['observaciones'])) ?></td></tr>
    <tr>
      <th>Ruta asignada:</th>
      <td>
        <?php if ($tiene_ruta): ?>
          <span class="text-success"><i class="bi bi-check-circle-fill"></i> Sí</span>
        <?php else: ?>
          <span class="text-danger"><i class="bi bi-x-circle-fill"></i> No</span>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <div class="seccion-secundaria">
    <h4><i class="bi bi-person"></i> Usuarios asignados</h4>
    <?php if (mysqli_num_rows($usuarios) > 0): ?>
      <ul>
        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
          <li><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?> - <?= htmlspecialchars($u['email']) ?></li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>No hay usuarios asignados a esta parcela.</p>
    <?php endif; ?>
  </div>

  <div class="seccion-secundaria">
    <h4><i class="bi bi-drone"></i> Drones asignados</h4>
    <?php if (mysqli_num_rows($drones) > 0): ?>
      <ul>
        <?php while ($d = mysqli_fetch_assoc($drones)): ?>
          <li><?= htmlspecialchars($d['marca'] . ' ' . $d['modelo']) ?> (<?= $d['estado'] ?>)</li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>No hay drones asignados a esta parcela.</p>
    <?php endif; ?>
  </div>

  <div class="seccion-secundaria">
    <h4><i class="bi bi-journal-text"></i> Historial de trabajos</h4>
    <?php if (mysqli_num_rows($trabajos) > 0): ?>
      <ul>
        <?php while ($t = mysqli_fetch_assoc($trabajos)): ?>
          <li><?= $t['fecha'] ?> <?= $t['hora'] ?> - <?= htmlspecialchars($t['nombre']) ?> con <?= htmlspecialchars($t['marca'] . ' ' . $t['modelo']) ?> (<?= $t['estado_general'] ?>)</li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>No hay trabajos registrados para esta parcela.</p>
    <?php endif; ?>
  </div>

  <div class="boton-volver">
    <a href="lis_parcelas.php"><i class="bi bi-arrow-left-circle"></i> Volver</a>
  </div>
</main>
<?php include '../../componentes/footer.php'; ?>
</body>
