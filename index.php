<?php
include 'lib/functiones.php';
session_start();

$error = "";

if (isset($_POST['enviar'])) {
    $conexion = conectar();

    $correo = mysqli_real_escape_string($conexion, trim($_POST['usu']));
    $password = trim($_POST['con']);

    if (empty($correo) || empty($password)) {
        $error = "⚠ Has dejado algún campo vacío.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El formato del correo no es válido.";
    } else {
        $hash = base64_encode(hash('sha256', $password, true));
        $sql = "select * from usuarios where email = '$correo' and contrasena = '$hash'";
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
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap">
</head>
<body>
    <!-- Logo y eslogan -->
    <header class="header-login">
        <div class="logo-box">
            <img src="img/logo.png" alt="AgroSky Logo">
            <p class="eslogan">🌱 Cultiva desde el cielo, gestiona con inteligencia.</p>
        </div>
    </header>

    <!-- Mensaje de error -->
    <?php if (!empty($error)): ?>
        <p style="color: red; text-align: center; font-family: 'Poppins'; margin-top: 20px;">
            <?= $error ?>
        </p>
    <?php endif; ?>

    <!-- Formulario de login -->
    <div class="box">
        <div class="form">
            <form action="index.php" method="post">
                <div class="inputBox">
                    <input type="email" name="usu" required placeholder="Correo">
                    <span>Correo</span>
                </div>

                <div class="inputBox">
                    <input type="password" name="con" id="password" required placeholder="Contraseña">
                    <span>Contraseña</span>
                </div>

                <div class="checkbox-pass">
                    <input type="checkbox" id="mostrarPass" onclick="togglePassword()">
                    <label for="mostrarPass">Mostrar contraseña</label>
                </div>

                <input type="submit" name="enviar" value="Entrar">
            </form>

            <a href="./fun/recuperar.php">🔁 Recuperar contraseña</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
