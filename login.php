<?php
include 'lib/functiones.php';
session_start();

$error = "";

if (isset($_POST['enviar'])) {
    $correo = trim($_POST['usu']);
    $password = trim($_POST['con']);

    if (empty($correo) || empty($password)) {
        $error = "⚠ Has dejado algún campo vacío.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El formato del correo no es válido.";
    } else {
        $hash = base64_encode(hash('sha256', $password, true));
        $conexion = conectar();
        $sql = "SELECT * FROM usuarios WHERE email = '$correo' AND contrasena = '$hash'";
        $resultado = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($resultado) === 1) {
            $_SESSION['usuario'] = mysqli_fetch_assoc($resultado);
            header("Location: menu.php");
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
    <link rel="stylesheet" href="./css/login.css">
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
            <form action="login.php" method="post">
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

            <div style="text-align:center; margin-top: 15px;">
                <a href="./fun/recuperar.php" style="color:#45f3ff; text-decoration:none; font-weight:500;">🔁 Recuperar contraseña</a>
            </div>
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
