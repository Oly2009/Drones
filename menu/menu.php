<?php
include '../lib/functiones.php';
session_start();
?>
<body>
<div class="d-flex flex-column min-vh-100">
  <?php include '../componentes/header.php'; ?>

  <main class="flex-grow-1 text-center py-4">
    <?php if (isset($_SESSION['usuario'])):
      $conexion = conectar();
      $id_usr = $_SESSION['usuario']['id_usr'];
      $nombre = strtoupper($_SESSION['usuario']['nombre']);
      $administrador = false;
      $piloto = false;
      $roles = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");
      while ($rol = mysqli_fetch_assoc($roles)) {
          if ($rol['id_rol'] == 1) $administrador = true;
          if ($rol['id_rol'] == 2) $piloto = true;
      }
    ?>

    <section class="container d-flex flex-column justify-content-between align-items-center">
      <h1 class="fw-bold welcome-title" style="margin-bottom: 5rem; color: #2c3e50;">
        <i class="bi bi-globe2 me-2 text-success"></i> Bienvenido <span class="text-dark"><?php echo $nombre; ?></span>
      </h1>

      <div class="btn-container d-flex flex-wrap justify-content-center mb-4 w-100 px-3">
        <?php if ($administrador): ?>
          <a href="usuarios.php" class="btn custom-btn shadow rounded-pill px-4 py-2 w-sm-95">
            <i class="bi bi-people-fill me-2 text-primary"></i>Usuarios
          </a>
          <a href="parcelas.php" class="btn custom-btn shadow rounded-pill px-4 py-2 w-sm-95">
            <i class="bi bi-tree-fill me-2 text-info"></i>Parcelas
          </a>
        <?php endif; ?>

        <?php if ($administrador || $piloto): ?>
          <a href="trabajos.php" class="btn custom-btn shadow rounded-pill px-4 py-2 w-sm-95">
            <i class="bi bi-clipboard2-check-fill me-2 text-warning"></i>Trabajos
          </a>
          <a href="drones.php" class="btn custom-btn shadow rounded-pill px-4 py-2 w-sm-95">
            <i class="bi bi-airplane-engines-fill me-2 text-purple"></i>Drones
          </a>
        <?php endif; ?>
      </div>

      <div class="image-container px-2">
        <img src="https://static.wixstatic.com/media/c8d297_b388072a9c334587b543a4928584cb33~mv2.gif"
             alt="Dron en vuelo" class="img-fluid rounded shadow">
      </div>
    </section>

    <?php else: ?>
      <div class="alert alert-danger">⛔ Acceso denegado</div>
      <div class="text-center">
        <a href="../index.php" class="btn btn-danger">🔙 Volver</a>
      </div>
      <?php session_destroy(); ?>
    <?php endif; ?>
  </main>

  <?php include '../componentes/footer.php'; ?>
</div>
