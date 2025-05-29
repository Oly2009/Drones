<?php
include '../lib/functiones.php';
session_start();
?>
<div class="d-flex flex-column min-vh-100">
  <?php include '../componentes/header.php'; ?>

  <main class="flex-grow-1 bg-light-green text-center py-4">
    <?php
    if (isset($_SESSION['usuario'])) {
      $conexion = conectar();
      $email = $_SESSION['usuario']['email'];
      $id_usr_query = mysqli_query($conexion, "SELECT id_usr FROM usuarios WHERE email = '$email'");
      $id_usr_row = mysqli_fetch_assoc($id_usr_query);
      $id_usr = $id_usr_row['id_usr'];

      $rolQuery = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");

      $esAdmin = false;
      $esPiloto = false;

      while ($rol = mysqli_fetch_assoc($rolQuery)) {
          if ($rol['id_rol'] == 1) $esAdmin = true;
          if ($rol['id_rol'] == 2) $esPiloto = true;
      }
    ?>

    <section class="container">
      <h1 class="fw-bold mb-5 fs-1" style="color: #2c3e50;">
        <i class="bi bi-clipboard2-check-fill me-2" style="color: #fd7e14;"></i> MENÚ TRABAJOS
      </h1>

      <div class="btn-container d-flex flex-wrap justify-content-center">
        <?php if ($esAdmin): ?>
          <a href="../fun/agregar/agr_trabajos.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-plus-circle-fill me-2 text-success"></i>Añadir
          </a>
        <?php endif; ?>

        <?php if ($esPiloto && !$esAdmin): ?>
          <a href="../fun/eliminar/eje_trabajos.php?id_usr=<?= $id_usr ?>" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-send-fill me-2 text-primary"></i>Ejecutar trabajos 
          </a>
          <a href="../fun/listar/lis_trabajos.php?id_usr=<?= $id_usr ?>" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-card-list me-2 text-info"></i>Ver trabajos 
          </a>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
          <a href="../fun/eliminar/eje_trabajos.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-send-fill me-2 text-primary"></i>Ejecutar
          </a>
          <a href="../fun/eliminar/eli_trabajos.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-trash-fill me-2 text-danger"></i>Eliminar
          </a>
          <a href="../fun/listar/lis_trabajos.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-card-list me-2 text-info"></i>Listar
          </a>
        <?php endif; ?>

        <a href="../menu/menu.php" class="btn btn-danger shadow rounded-pill px-4 py-2">
          <i class="bi bi-arrow-left-circle-fill me-2"></i>Volver
        </a>
      </div>

      <div class="image-container px-2">
        <img src="https://cdn.computerhoy.com/sites/navi.axelspringer.es/public/media/image/2015/07/111523-piloto-drones-profesion-futuro-cursos-licencia-normas-legislacion.jpg?tf=3840x"
             alt="Trabajos con drones" class="img-fluid rounded shadow">
      </div>
    </section>

    <?php
    } else {
      echo "<div class='alert alert-danger'>⛔ Acceso denegado</div>";
      echo "<div class='text-center mt-4'><a href='../index.php' class='btn btn-danger btn-lg rounded-pill px-4'>
            <i class='bi bi-arrow-left-circle me-2'></i>Volver</a></div>";
      session_destroy();
    }
    ?>
  </main>

  <?php include '../componentes/footer.php'; ?>
</div>
