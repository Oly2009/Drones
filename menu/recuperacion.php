<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
setlocale(LC_ALL, 'es_ES.utf8', 'spanish');

require '../lib/phpmailer/src/Exception.php';
require '../lib/phpmailer/src/PHPMailer.php';
require '../lib/phpmailer/src/SMTP.php';

session_start();
include '../lib/functiones.php';
include '../componentes/header.php';
$conexion = conectar();

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
    $correo = trim($_POST['correo']);

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = '❌ Correo no válido';
        $tipoMensaje = 'error';
    } else {
        $res = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '" . mysqli_real_escape_string($conexion, $correo) . "'");

        if ($usuario = mysqli_fetch_assoc($res)) {
            $nuevaPass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
            $hashed = base64_encode(hash('sha256', $nuevaPass, true));
            mysqli_query($conexion, "UPDATE usuarios SET contrasena = '$hashed' WHERE id_usr = {$usuario['id_usr']}");

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();

                if ($_SERVER['HTTP_HOST'] === 'localhost') {
                    // Local con MailHog
                    $mail->Host = 'localhost';
                    $mail->Port = 1025;
                    $mail->SMTPAuth = false;
                } else {
                    // Producción (Gmail o SMTP real)
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'olydaw2022@gmail.com'; // cámbialo
                    $mail->Password = 'xvmf misg ygyg dxwx';    // cámbialo
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                }

                $mail->setFrom('no-reply@agrosky.local', 'AgroSky');
                $mail->addAddress($correo);
                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de contraseña - AgroSky';
                $mail->Body = "
                    <p>Estimado/a <strong>{$usuario['nombre']}</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en AgroSky.</p>
                    <p>Su nueva contraseña temporal es: <strong>$nuevaPass</strong></p>
                    <p>Por favor, inicie sesión utilizando esta nueva contraseña y le recomendamos cambiarla lo antes posible por una de su elección.</p>
                    <p style='text-align: center;'>
                        <a href='https://agrosky.infinityfreeapp.com/index.php' style='display: inline-block; padding: 10px 20px; font-size: 16px; text-align: center; text-decoration: none; border-radius: 5px; background-color: #28a745; color: white;'>
                            Iniciar Sesión en AgroSky
                        </a>
                    </p>
                    <p>Si usted no solicitó este restablecimiento de contraseña, puede ignorar este correo electrónico. Su contraseña actual seguirá siendo válida.</p>
                    <p>Atentamente,<br>El equipo de AgroSky</p>
                ";

                $mail->send();
                $mensaje = '✅ Se ha enviado una nueva contraseña a su correo electrónico.';
                $tipoMensaje = 'success';
            } catch (Exception $e) {
                $mensaje = '❌ No se pudo enviar el correo. Error: ' . $mail->ErrorInfo;
                $tipoMensaje = 'error';
            }
        } else {
            $mensaje = '❌ No se encontró ninguna cuenta con ese correo electrónico.';
            $tipoMensaje = 'warning';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperación de Contraseña</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<main class="container flex-grow-1 py-5">
  <h1 class="text-center mb-4 text-success"><i class="bi bi-unlock-fill me-2"></i>Recuperar Contraseña</h1>

  <form method="post" class="mx-auto p-4 border rounded bg-light shadow" style="max-width: 500px;">
    <div class="mb-3">
      <label for="correo" class="form-label">Correo electrónico</label>
      <input type="email" class="form-control" id="correo" name="correo" required>
    </div>
    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-success btn-lg">Enviar nueva contraseña</button>
      <a href="../index.php" class="btn btn-danger btn-lg">Volver al inicio</a>
    </div>
  </form>
</main>

<?php if ($mensaje): ?>
<script>
Swal.fire({
  title: <?= json_encode($tipoMensaje === 'success' ? '✅ Éxito' : '⚠️ Aviso') ?>,
  text: <?= json_encode($mensaje) ?>,
  icon: <?= json_encode($tipoMensaje) ?>,
  confirmButtonColor: '#28a745'
});
</script>
<?php endif; ?>

<?php include '../componentes/footer.php'; ?>
</body>
</html>