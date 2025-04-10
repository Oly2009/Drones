<?php 
session_start();
include 'lib/functiones.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>

<?php
$registroExitoso = false;
$mensaje = '';
$mostrarModal = false;
$conexion = conectar();

// Obtener todas las parcelas (sin filtro)
$parcelasDisponibles = mysqli_query($conexion, "SELECT id_parcela, ubicacion FROM parcelas");

if (isset($_POST['enviarReg'])) {
    $nombre = trim($_POST['usu']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['passwordMatchInput']);
    $rolSeleccionado = $_POST['rol'];
    $parcelaSeleccionada = intval($_POST['parcela']);

    if (
        empty($nombre) || empty($apellidos) || empty($correo) || 
        empty($telefono) || empty($password) || empty($confirm)
    ) {
        $mensaje = "❌ Todos los campos son obligatorios.";
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

                // Asignar rol
                $rolQuery = "SELECT id_rol FROM roles WHERE nombre_rol = '$rolSeleccionado'";
                $resRol = mysqli_query($conexion, $rolQuery);
                if ($rowRol = mysqli_fetch_assoc($resRol)) {
                    $id_rol = $rowRol['id_rol'];
                    mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($id_usr, $id_rol)");
                }

                // Asignar parcela al usuario en la tabla intermedia
                $yaTieneParcela = mysqli_query($conexion, "SELECT * FROM parcelas_usuarios WHERE id_usr = $id_usr");
                if (mysqli_num_rows($yaTieneParcela) === 0) {
                    mysqli_query($conexion, "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($id_usr, $parcelaSeleccionada)");
                    $registroExitoso = true;
                    $mensaje = "✅ ¡Usuario registrado correctamente!";
                } else {
                    $mensaje = "⚠️ El usuario ya tiene una parcela asignada.";
                }

                $mostrarModal = true;
            } else {
                $mensaje = "❌ Error al registrar el usuario.";
                $mostrarModal = true;
            }
        }
    }
}
?>

<div class="form"> 
    <form action="registro.php" method="post" class="grid-form">
        <div class="columna">
            <label>Nombre de usuario</label>
            <input type="text" name="usu" required>

            <label>Apellidos</label>
            <input type="text" name="apellidos" required>

            <label>Correo electrónico</label>
            <input type="email" name="correo" required>

            <label>Número de teléfono</label>
            <input type="tel" name="telefono" required pattern="[0-9]{9}" maxlength="9" title="Debe tener 9 dígitos numéricos">

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <label>Repite la contraseña</label>
            <input type="password" name="passwordMatchInput" required>
        </div>

        <div class="columna">
            <label>Rol del usuario</label>
            <select name="rol" required>
                <option value="piloto">Piloto</option>
                <option value="agricultor">Agricultor</option>
            </select>

            <label>Parcela</label>
            <select name="parcela" required>
                <?php while ($parcela = mysqli_fetch_assoc($parcelasDisponibles)): ?>
                    <option value="<?= $parcela['id_parcela'] ?>"><?= $parcela['ubicacion'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="botones-accion">
            <input type="submit" name="enviarReg" value="Registrar">
            <button type="button" onclick="window.location.href='fun/usuarios.php'">Volver</button>
        </div>
    </form>
</div>

<!-- Modal de resultado -->
<?php if ($mostrarModal): ?>
<div class="modal" id="modalExito">
    <div class="modal-content">
        <h3><?= $mensaje ?></h3>
        <?php if ($registroExitoso): ?>
            <form action="fun/usuarios.php" method="post">
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


<!-- Animaciones decorativas -->
<div class="loader">
    <div class="bolas"></div>
    <div class="bolas2"></div>
    <div class="semi-circle"></div>
</div>

</body>
</html>
