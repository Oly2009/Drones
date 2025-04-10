<?php 
session_start();
include '../lib/functiones.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuarios - AgroSky</title>
    <link rel="stylesheet" href="../css/registro.css">
</head>
<body>

<?php
$registroExitoso = false;
$mensaje = '';
$mostrarModal = false;
$conexion = conectar();

if (isset($_POST['enviarReg'])) {
    $nombre = trim($_POST['usu']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['passwordMatchInput']);

    if (
        empty($nombre) || empty($apellidos) || empty($correo) || 
        empty($telefono) || empty($password) || empty($confirm)
    ) {
        $mensaje = "❌ Todos los campos son obligatorios.";
        $mostrarModal = true;
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "❌ El correo electrónico no tiene un formato válido.";
        $mostrarModal = true;
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje = "❌ El número de teléfono debe tener exactamente 9 dígitos.";
        $mostrarModal = true;
    } elseif ($password !== $confirm) {
        $mensaje = "❌ Las contraseñas no coinciden.";
        $mostrarModal = true;
    } else {
        $check = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '$correo'");
        if (mysqli_num_rows($check) > 0) {
            $mensaje = "⚠️ Ya existe un usuario con ese correo.";
            $mostrarModal = true;
        } else {
            $passwordHashed = base64_encode(hash('sha256', $password, true));

            $insert = "INSERT INTO usuarios (nombre, apellidos, contrasena, telefono, email) 
                       VALUES ('$nombre', '$apellidos', '$passwordHashed', '$telefono', '$correo')";
            if (mysqli_query($conexion, $insert)) {
                $id_usr = mysqli_insert_id($conexion);
                $resRol = mysqli_query($conexion, "SELECT id_rol FROM roles WHERE nombre_rol = 'piloto'");
                if ($rowRol = mysqli_fetch_assoc($resRol)) {
                    $id_rol = $rowRol['id_rol'];
                    mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($id_usr, $id_rol)");
                }
                $registroExitoso = true;
                $mensaje = "✅ ¡Usuario registrado correctamente!";
                $mostrarModal = true;
            } else {
                $mensaje = "❌ Error al registrar el usuario.";
                $mostrarModal = true;
            }
        }
    }
}
?>

<!-- Bolas decorativas -->
<div class="bola bola1"></div>
<div class="bola bola2"></div>

<!-- Contenido principal con formulario -->
<div class="contenido-pagina">
    <div class="registro-container">
        <h1 class="titulo-registro">🛫 Registro de Usuarios<br><span>- AgroSky -</span></h1>
        <form action="registro.php" method="post" class="registro-form">
            <label>👤 Nombre de usuario</label>
            <input type="text" name="usu" required>

            <label>👥 Apellidos</label>
            <input type="text" name="apellidos" required>

            <label>📧 Correo electrónico</label>
            <input type="email" name="correo" required>

            <label>📱 Número de teléfono</label>
            <input type="tel" name="telefono" required pattern="[0-9]{9}" maxlength="9">

            <label>🔒 Contraseña</label>
            <input type="password" name="password" required>

            <label>🔁 Repite la contraseña</label>
            <input type="password" name="passwordMatchInput" required>

            <div class="botones-accion">
                <input type="submit" name="enviarReg" value="Registrar" class="btn btn-primary">
                <button type="button" onclick="window.location.href='usuarios.php'" class="btn btn-secundario">Volver</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de mensaje -->
<?php if ($mostrarModal): ?>
<div class="modal" id="modalExito">
    <div class="modal-content">
        <h3><?= $mensaje ?></h3>
        <?php if ($registroExitoso): ?>
            <form action="usuarios.php" method="post">
                <input type="submit" value="Ir al panel de usuarios">
            </form>
        <?php endif; ?>
        <button onclick="cerrarModal()">Cerrar</button>
    </div>
</div>
<script>
function cerrarModal() {
    document.getElementById("modalExito").style.display = "none";
}
</script>
<?php endif; ?>

</body>
</html>
