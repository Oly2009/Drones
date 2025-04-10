<!DOCTYPE html>
<?php
    include '../lib/functiones.php';
    session_start();
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú principal - AgroSky</title>
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
<?php
if (isset($_REQUEST['enviar'])) {
    @$usu = $_REQUEST['usu'];
    @$password = $_REQUEST['con'];

    if (empty($usu) || empty($password)) {
        echo "<p class='mensaje-error'>⚠️ Has dejado algún campo sin rellenar.</p>";
        echo '<div class="botonera"><a href="login.php" class="btn">🔙 Volver al login</a></div>';
    } else {
        $hash = base64_encode(hash('sha256', $password, true));
        $instruccion = "SELECT * FROM usuarios WHERE email = '$usu' AND contrasena = '$hash'";
        $consulta = mysqli_query(conectar(), $instruccion);

        if (mysqli_num_rows($consulta) > 0) {
            $_SESSION['usuario'] = mysqli_fetch_assoc($consulta);
        } else {
            echo "<p class='mensaje-error'>❌ Credenciales incorrectas.</p>";
            echo '<div class="botonera"><a href="index.php" class="btn">🔙 Volver al login</a></div>';
        }
    }
}

if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
    $id_usr = $_SESSION['usuario']['id_usr'];
    $nombre = strtoupper($_SESSION['usuario']['nombre']);

    $administrador = false;
    $piloto = false;

    $roles = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");
    while ($rol = mysqli_fetch_assoc($roles)) {
        switch ($rol['id_rol']) {
            case 1: $administrador = true; break;
            case 2: $piloto = true; break;
        }
    }
?>

<h1>🌐 Bienvenido <?php echo $nombre; ?></h1>

<div class="botonera">
    <?php if ($administrador): ?>
        <a href="usuarios.php" class="btn">👥 Usuarios</a>
    <?php endif; ?>

    <?php if ($administrador || $piloto): ?>
        <a href="parcelas.php" class="btn">🌾 Parcelas</a>
        <a href="trabajos.php" class="btn">📋 Trabajos</a>
        <a href="drones.php" class="btn">🛸 Drones</a>
    <?php endif; ?>

    <a href="cuenta.php" class="btn">⚙️ Cuenta</a>
    <a href="../index.php" class="btn">❌ Cerrar Sesión</a>
</div>

<div class="imagen-menu">
    <img src="https://static.wixstatic.com/media/c8d297_b388072a9c334587b543a4928584cb33~mv2.gif" alt="Menú principal AgroSky">
</div>

<?php
} else {
    echo "<p class='mensaje-error'>⛔ Acceso denegado</p>";
    echo '<div class="botonera"><a href="../index.php" class="btn">🔙 Volver</a></div>';
    session_destroy();
}
?>
</body>
</html>
