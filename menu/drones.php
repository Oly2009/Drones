<?php
include '../lib/functiones.php';
session_start();
?>
<div class="d-flex flex-column min-vh-100">
  <?php include '../componentes/header.php'; ?>

  <main class="flex-grow-1 bg-light-green text-center py-4">
    <?php if (isset($_SESSION['usuario'])): ?>
      <section class="container">
        <h1 class="fw-bold mb-5 fs-1" style="color: #2c3e50;">
          <i class="bi bi-airplane-engines-fill me-2" style="color: #0984e3;"></i> MENÚ DRONES
        </h1>

        <div class="btn-container d-flex flex-wrap justify-content-center">
          <a href="../fun/agregar/agr_drones.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-plus-circle-fill me-2 text-success"></i>Añadir
          </a>
          <a href="../fun/modificar/mod_drones.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-gear-fill me-2 text-warning"></i>Modificar
          </a>
          <a href="../fun/eliminar/eli_drones.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-trash-fill me-2 text-danger"></i>Eliminar
          </a>
          <a href="../fun/listar/lis_drones.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-card-list me-2 text-primary"></i>Listar
          </a>
          <a href="../menu/menu.php" class="btn btn-danger shadow rounded-pill px-4 py-2">
            <i class="bi bi-arrow-left-circle-fill me-2"></i>Volver
          </a>
        </div>

        <div class="image-container px-2">
          <img src="https://oesteyeste.com/wp-content/uploads/2019/10/dron-grabaciones-aereas.gif"
               alt="Dron animado" class="img-fluid rounded shadow">
        </div>
      </section>
    <?php else: ?>
      <div class="alert alert-danger">⛔ Acceso denegado</div>
      <div class="text-center mt-4">
        <a href='../index.php' class='btn btn-danger btn-lg rounded-pill px-4'>
          <i class="bi bi-arrow-left-circle me-2"></i>Volver
        </a>
      </div>
      <?php if (session_status() === PHP_SESSION_ACTIVE) session_destroy(); ?>
    <?php endif; ?>
  </main>

  <?php include '../componentes/footer.php'; ?>
</div>
