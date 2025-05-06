<?php
include 'lib/functiones.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $conexion = conectar();

    $correo = mysqli_real_escape_string($conexion, trim($_POST['usu']));
    $password = trim($_POST['con']);

    if (empty($correo) || empty($password)) {
        $error = "⚠ Has dejado algún campo vacío.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El formato del correo no es válido.";
    } else {
        $hash = base64_encode(hash('sha256', $password, true));
        $sql = "SELECT * FROM usuarios WHERE email = '$correo' AND contrasena = '$hash'";
        $resultado = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($resultado) === 1) {
            $_SESSION['usuario'] = mysqli_fetch_assoc($resultado);
            header("Location: menu/menu.php");
            exit();
        } else {
            $error = "❌ Credenciales incorrectas. Acceso denegado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        @media (max-width: 768px) {
            .login-form {
                margin-top: 2rem !important;
            }
            .logo-box {
                margin-top: 0.5rem !important;
            }
        }
    </style>
</head>
<body class="index-body bg-login">
<main class="container-fluid min-vh-100">
    <div class="row w-100 h-100">
        <section class="col-md-6 d-flex flex-column justify-content-start align-items-start ps-md-5 ps-3 pt-4">
            <div class="logo-box d-flex flex-column align-items-start mb-4 ms-md-3">
                <img src="img/logo.png" alt="AgroSky Logo" width="80" class="mb-2 mx-auto d-block">
              <p class="text-white fw-semibold fs-5 mt-1 d-flex align-items-center">
    <span style="font-size: 1.8rem; margin-right: 0.5rem;">🌿</span>
    <span style="font-size: 1.4rem">
        Cultiva desde el cielo, <br>
        gestiona con inteligencia.
    </span>
</p>
            </div>

            <section class="login-form animate__animated animate__fadeInUp mt-4 ms-md-3">
                <h2 class="text-center mb-4">Iniciar Sesión</h2>
                <form action="index.php" method="post">
                    <div class="mb-3 input-group">
                        <span class="input-group-text bg-success text-white"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="usu" id="correo" class="form-control" placeholder="Correo" required>
                    </div>
                    <div class="mb-3 input-group">
                        <span class="input-group-text bg-success text-white"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="con" id="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="mostrarPass" onclick="togglePassword()">
                        <label class="form-check-label" for="mostrarPass">Mostrar contraseña</label>
                    </div>
                    <div class="d-grid mb-2">
                        <button type="submit" name="enviar" class="btn btn-success">Entrar</button>
                    </div>
                    <div class="text-center">
                        <a href="menu/recuperacion.php" class="text-white text-decoration-underline">
                            🔁 Recuperar contraseña
                        </a>
                    </div>
                </form>
            </section>
        </section>

        <div class="col-md-6 px-0">
            <div class="h-100 w-100 bg-img-login"></div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)): ?>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?= json_encode($error) ?>,
            confirmButtonColor: '#218838'
        });
    });
    <?php endif; ?>
</script>
</body>
</html>
