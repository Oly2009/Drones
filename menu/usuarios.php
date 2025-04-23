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
          <i class="bi bi-people-fill me-2" style="color: #6c5ce7;"></i> MENÚ USUARIOS
        </h1>

        <div class="btn-container d-flex flex-wrap justify-content-center">
          <a href="../fun/listar/lis_usuarios.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-card-list me-2 text-primary"></i>Listar
          </a>
          <a href="../menu/registro.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-person-plus-fill me-2 text-success"></i>Alta
          </a>
          <a href="../fun/modificar/modificar_usuarios.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-pencil-fill me-2 text-warning"></i>Modificar
          </a>
          <a href="../fun/eliminar/eli_usuarios.php" class="btn custom-btn shadow rounded-pill px-4 py-2">
            <i class="bi bi-trash-fill me-2 text-danger"></i>Eliminar
          </a>
          <a href="../menu/menu.php" class="btn btn-danger shadow rounded-pill px-4 py-2">
            <i class="bi bi-arrow-left-circle-fill me-2"></i>Volver
          </a>
        </div>

        <div class="image-container px-2">
          <img src="../img/usuario.jpg"
               alt="Trabajadores agrícolas utilizando drones"
               class="img-fluid rounded shadow">
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

