<?php
include '../lib/functiones.php';
session_start();

$mensaje = "";
$tipo_mensaje = ""; // puede ser 'error' o 'exito'

// Si el formulario ha sido enviado
if (isset($_POST['recuperar'])) {
    $email = trim($_POST['email']);
    
    // Validar email
    if (empty($email)) {
        $mensaje = "⚠️ Debes ingresar tu email";
        $tipo_mensaje = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "❌ El formato del correo no es válido";
        $tipo_mensaje = "error";
    } else {
        // Verificar si el email existe en la base de datos
        $conexion = conectar();
        $email = mysqli_real_escape_string($conexion, $email);
        
        $consulta = "SELECT id_usr, nombre FROM usuarios WHERE email = '$email'";
        $resultado = mysqli_query($conexion, $consulta);
        
        if (mysqli_num_rows($resultado) > 0) {
            $usuario = mysqli_fetch_assoc($resultado);
            $id_usuario = $usuario['id_usr'];
            $nombre = $usuario['nombre'];
            
            // Generar contraseña temporal aleatoria
            $nueva_contrasena = generarContrasenaAleatoria(8);
            $hash_contrasena = base64_encode(hash('sha256', $nueva_contrasena, true));
            
            // Actualizar la contraseña en la base de datos - Usamos prepared statement para mayor seguridad
            $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena = ? WHERE id_usr = ?");
            mysqli_stmt_bind_param($stmt, "si", $hash_contrasena, $id_usuario);
            
            if (mysqli_stmt_execute($stmt)) {
                // Verificamos que realmente se haya actualizado un registro
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $mensaje = "✅ Se ha generado una nueva contraseña para tu cuenta.<br>
                               Tu nueva contraseña es: <strong>$nueva_contrasena</strong><br>
                               Por favor, guárdala en un lugar seguro y cámbiala después de iniciar sesión.";
                    $tipo_mensaje = "exito";
                } else {
                    // Si no se actualizó ningún registro, hay un problema
                    $mensaje = "❌ No se pudo actualizar la contraseña. El usuario existe pero no se actualizó.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "❌ Ha ocurrido un error al actualizar tu contraseña: " . mysqli_error($conexion);
                $tipo_mensaje = "error";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $mensaje = "❌ No existe ninguna cuenta asociada a este correo electrónico.";
            $tipo_mensaje = "error";
        }
        
        mysqli_close($conexion);
    }
}

// Función para generar una contraseña aleatoria
function generarContrasenaAleatoria($longitud) {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_';
    $contrasena = '';
    $max = strlen($caracteres) - 1;
    
    for ($i = 0; $i < $longitud; $i++) {
        $contrasena .= $caracteres[random_int(0, $max)];
    }
    
    return $contrasena;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - AgroSky</title>
    <link rel="stylesheet" href="../../css/login.css">
    <style>
        .recuperar-box {
            width: 380px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            box-sizing: border-box;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.6);
            border-radius: 10px;
            margin: 0 auto;
            margin-top: 30px;
        }
        
        .recuperar-box h2 {
            margin: 0 0 30px;
            padding: 0;
            color: #fff;
            text-align: center;
        }
        
        .mensaje {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .mensaje-error {
            background-color: rgba(255, 0, 0, 0.2);
            color: #ff0000;
        }
        
        .mensaje-exito {
            background-color: rgba(0, 255, 0, 0.2);
            color: #00ff00;
        }
        
        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn-volver {
            display: inline-block;
            padding: 10px 20px;
            color: #45f3ff;
            text-decoration: none;
            border: 1px solid #45f3ff;
            border-radius: 5px;
            transition: 0.5s;
            margin-top: 10px;
        }
        
        .btn-volver:hover {
            background: #45f3ff;
            color: #000;
        }
    </style>
</head>
<body>
    <!-- Logo y eslogan -->
    <header class="header-login">
        <div class="logo-box">
            <img src="../../img/logo.png" alt="AgroSky Logo">
            <p class="eslogan">🌱 Cultiva desde el cielo, gestiona con inteligencia.</p>
        </div>
    </header>
    
    <div class="recuperar-box">
        <h2>🔐 Recuperar Contraseña</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= $tipo_mensaje === 'error' ? 'mensaje-error' : 'mensaje-exito' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>
        
        <div class="form">
            <form action="recuperar.php" method="post">
                <div class="inputBox">
                    <input type="email" name="email" required placeholder="Tu correo electrónico">
                    <span>Correo</span>
                </div>
                
                <div class="botones">
                    <input type="submit" name="recuperar" value="Recuperar Contraseña">
                    <a href="../login.php" class="btn-volver">Volver al Login</a>
                </div>
            </form>
            
            <p style="color: #fff; margin-top: 20px; text-align: center; font-size: 14px;">
                Ingresa tu correo electrónico y te enviaremos una nueva contraseña para acceder a tu cuenta.
            </p>
        </div>
    </div>
</body>
</html>