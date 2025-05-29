<?php
session_start();
include '../../lib/functiones.php'; // Asegúrate de que esta ruta sea correcta para tu estructura de archivos

// Redirigir si el usuario no ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar(); // Establecer conexión a la base de datos
$idAdmin = $_SESSION['usuario']['id_usr'];

// Verificar si el usuario logueado es administrador
$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idAdmin");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) { // Asumiendo que '1' es el ID para el rol de administrador en tu base de datos
        $esAdmin = true;
        break;
    }
}

// Si no es administrador, mostrar mensaje de acceso restringido con SweetAlert y salir
if (!$esAdmin) {
    // Incluir solo lo necesario para que SweetAlert funcione antes de salir
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Denegado</title>';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    echo '</head><body class="d-flex flex-column min-vh-100 justify-content-center align-items-center">';
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '    Swal.fire({';
    echo '        title: "⛔ Acceso Restringido",';
    echo '        html: "Solo administradores pueden acceder a esta sección.",';
    echo '        icon: "error",';
    echo '        confirmButtonColor: "#d33"';
    echo '    }).then((result) => {';
    echo '        if (result.isConfirmed) {';
    echo '            window.location.href = "../../menu/menu_principal.php";'; // Redirige a la página principal del menú
    echo '        }';
    echo '    });';
    echo '});';
    echo '</script>';
    echo '</body></html>';
    exit();
}

// Variables para el SweetAlert final de éxito/error de la operación de guardado
$mensaje = "";
$tipo = ""; // 'exito' o 'error'
$errores_validaciones_servidor = []; // Array para almacenar errores de validación específicos por usuario en el servidor

// Procesar el formulario POST al guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuarios'])) {
    $all_updates_successful = true; // Flag para determinar el mensaje final del servidor

    foreach ($_POST['usuarios'] as $usuarioData) {
        $idUsuario = intval($usuarioData['id']);
        // Limpiar y obtener datos de cada usuario
        $nombre = trim(mysqli_real_escape_string($conexion, $usuarioData['nombre']));
        $apellidos = trim(mysqli_real_escape_string($conexion, $usuarioData['apellidos']));
        $email = trim(mysqli_real_escape_string($conexion, $usuarioData['email']));
        $telefono = trim(mysqli_real_escape_string($conexion, $usuarioData['telefono']));
        $rol = intval($usuarioData['rol']);
        // La parcela puede ser nula si el usuario selecciona "Ninguna" (valor vacío del select)
        $parcela = !empty($usuarioData['parcela']) ? intval($usuarioData['parcela']) : null;

        // --- INICIO DE VALIDACIONES PHP (Server-Side) para cada usuario ---
        $errores_usuario_actual = []; // Errores para el usuario que se está procesando en este bucle

        if (empty($nombre)) {
            $errores_usuario_actual[] = "El nombre es obligatorio.";
        }
        if (empty($apellidos)) {
            $errores_usuario_actual[] = "Los apellidos son obligatorios.";
        }
        if (empty($email)) {
            $errores_usuario_actual[] = "El email es obligatorio.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores_usuario_actual[] = "El email '{$email}' no tiene un formato válido.";
        }
        if (empty($telefono)) {
            $errores_usuario_actual[] = "El teléfono es obligatorio.";
        } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) { // Valida exactamente 9 dígitos
            $errores_usuario_actual[] = "El teléfono '{$telefono}' debe tener exactamente 9 dígitos numéricos.";
        }
        
        // Validar que el rol seleccionado sea válido (exista en la tabla roles y no sea el ID de admin si se excluye)
        $check_rol = mysqli_query($conexion, "SELECT id_rol FROM roles WHERE id_rol = $rol AND id_rol != 1");
        if (mysqli_num_rows($check_rol) == 0) {
            $errores_usuario_actual[] = "El rol seleccionado no es válido.";
        }

        // Validar que la parcela seleccionada exista, si no es "Ninguna"
        if ($parcela !== null) {
            $check_parcela = mysqli_query($conexion, "SELECT id_parcela FROM parcelas WHERE id_parcela = $parcela");
            if (mysqli_num_rows($check_parcela) == 0) {
                $errores_usuario_actual[] = "La parcela seleccionada no existe.";
            }
        }
        // --- FIN DE VALIDACIONES PHP (Server-Side) ---

        // Si hay errores de validación para el usuario actual, guardar y continuar al siguiente
        if (!empty($errores_usuario_actual)) {
            $all_updates_successful = false;
            $errores_validaciones_servidor[$idUsuario] = $errores_usuario_actual;
            continue; // Pasa al siguiente usuario sin intentar actualizar en DB
        }

        // Si todas las validaciones de este usuario pasaron, intentar la transacción en DB
        try {
            mysqli_begin_transaction($conexion);

            // Actualizar datos de la tabla 'usuarios'
            mysqli_query($conexion, "UPDATE usuarios SET nombre = '$nombre', apellidos = '$apellidos', email = '$email', telefono = '$telefono' WHERE id_usr = $idUsuario");
            
            // Actualizar rol en 'usuarios_roles' (eliminar y luego insertar para asegurar un único rol)
            mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $idUsuario");
            mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($idUsuario, $rol)");
            
            // Actualizar asignación de parcela en 'parcelas_usuarios'
            mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $idUsuario");
            if ($parcela !== null) { // Solo inserta si se seleccionó una parcela
                mysqli_query($conexion, "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($idUsuario, $parcela)");
            }

            mysqli_commit($conexion); // Confirmar la transacción si todo fue bien para este usuario
        } catch (Exception $e) {
            mysqli_rollback($conexion); // Deshacer la transacción si hubo un error en DB
            $all_updates_successful = false;
            $errores_validaciones_servidor[$idUsuario][] = "Error en la base de datos: " . $e->getMessage();
        }
    } // Fin del bucle foreach para usuarios

    // Determinar el mensaje final y el tipo de SweetAlert basado en el resultado global
    if ($all_updates_successful && empty($errores_validaciones_servidor)) {
        $mensaje = "✅ Todos los cambios se guardaron correctamente.";
        $tipo = "exito";
    } else {
        $mensaje_final_html = "❌ Se encontraron errores al guardar algunos usuarios:<br><br>";
        foreach ($errores_validaciones_servidor as $id_usr => $errores_usr) {
            $mensaje_final_html .= "<strong>Usuario ID {$id_usr}:</strong><br><ul>";
            foreach ($errores_usr as $error_msg) {
                $mensaje_final_html .= "<li>" . htmlspecialchars($error_msg) . "</li>";
            }
            $mensaje_final_html .= "</ul>";
        }
        $mensaje = $mensaje_final_html; // Este será el contenido HTML del SweetAlert
        $tipo = "error";
    }
}

// Obtener usuarios para mostrar en la tabla (excluyendo administradores para que no se puedan modificar a sí mismos si es la intención)
$usuarios = mysqli_query($conexion, "
    SELECT u.id_usr, u.nombre, u.apellidos, u.email, u.telefono,
        (SELECT ur.id_rol FROM usuarios_roles ur WHERE ur.id_usr = u.id_usr LIMIT 1) AS id_rol,
        (SELECT pu.id_parcela FROM parcelas_usuarios pu WHERE pu.id_usr = u.id_usr LIMIT 1) AS id_parcela
    FROM usuarios u
    WHERE u.id_usr NOT IN (SELECT id_usr FROM usuarios_roles WHERE id_rol = 1)
    ORDER BY u.nombre
");

// Obtener roles (excluyendo el de administrador, ya que no se deberían asignar roles de admin desde aquí)
$roles = mysqli_query($conexion, "SELECT * FROM roles WHERE id_rol != 1 ORDER BY nombre_rol");
// Obtener parcelas
$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas ORDER BY ubicacion");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Usuarios</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Estilo para resaltar campos inválidos (Bootstrap 5 ya tiene .is-invalid, pero esto refuerza) */
        .is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container flex-grow-1 py-5">
    <h1 class="titulo-listado text-center mb-4">
        <i class="bi bi-person-gear me-2" style="color:#6f42c1;"></i>Modificar Usuarios
    </h1>

    <form method="get" class="d-flex justify-content-center mb-4">
        <input type="text" id="buscarUsuario" class="form-control w-50 me-2" placeholder="🔍 Buscar por nombre, correo o teléfono">
        <button class="btn btn-success" type="button" onclick="filtrarUsuarios()">Buscar</button>
    </form>

    <form method="post" id="modificarUsuariosForm">
        <div class="table-responsive">
            <table class="table table-hover table-striped"> <thead class="table-success text-center">
                    <tr>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Parcela</th>
                        </tr>
                </thead>
                <tbody>
                    <?php 
                    // Verificar si hay usuarios para mostrar, si no, mostrar un mensaje
                    if (mysqli_num_rows($usuarios) > 0): 
                        mysqli_data_seek($usuarios, 0); // Asegurar que el puntero está al inicio
                        while ($u = mysqli_fetch_assoc($usuarios)): 
                    ?>
                    <tr data-user-id="<?= $u['id_usr'] ?>">
                        <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][nombre]" class="form-control" value="<?= htmlspecialchars($u['nombre']) ?>" required></td>
                        <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][apellidos]" class="form-control" value="<?= htmlspecialchars($u['apellidos']) ?>" required></td>
                        <td><input type="email" name="usuarios[<?= $u['id_usr'] ?>][email]" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required></td>
                        <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][telefono]" class="form-control" value="<?= htmlspecialchars($u['telefono']) ?>" required pattern="[0-9]{9}" title="El teléfono debe tener 9 dígitos numéricos"></td>
                        <td>
                            <select name="usuarios[<?= $u['id_usr'] ?>][rol]" class="form-select" required>
                                <option value="">Selecciona un rol</option> <?php mysqli_data_seek($roles, 0); // Reiniciar el puntero del resultado de roles
                                while ($r = mysqli_fetch_assoc($roles)): ?>
                                    <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $u['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre_rol']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td>
                            <select name="usuarios[<?= $u['id_usr'] ?>][parcela]" class="form-select">
                                <option value="">Ninguna</option> <?php mysqli_data_seek($parcelas, 0); // Reiniciar el puntero del resultado de parcelas
                                while ($p = mysqli_fetch_assoc($parcelas)): ?>
                                    <option value="<?= $p['id_parcela'] ?>" <?= $p['id_parcela'] == $u['id_parcela'] ? 'selected' : '' ?>><?= htmlspecialchars($p['ubicacion']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <input type="hidden" name="usuarios[<?= $u['id_usr'] ?>][id]" value="<?= $u['id_usr'] ?>">
                    </tr>
                    <?php endwhile; 
                    else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No hay usuarios disponibles para modificar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <div class="d-flex flex-column flex-sm-row justify-content-center align-items-stretch gap-3">
                <button type="submit" class="btn btn-success w-100 w-sm-auto px-4">
                    <i class="bi bi-floppy me-2"></i>Guardar cambios
                </button>
                <a href="../../menu/usuarios.php" class="btn btn-danger w-100 w-sm-auto px-4">
                    <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de usuarios
                </a>
            </div>
        </div>
    </form>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
    // Función para filtrar usuarios en la tabla (cliente-side)
    function filtrarUsuarios() {
        const filtro = document.getElementById('buscarUsuario').value.toLowerCase();
        document.querySelectorAll('.table tbody tr').forEach(fila => {
            // Unir todo el contenido de texto de la fila para la búsqueda
            const rowText = fila.textContent.toLowerCase();
            fila.style.display = rowText.includes(filtro) ? '' : 'none';
        });
    }

    // --- Validaciones Cliente-Side (JavaScript) al enviar el formulario ---
    document.getElementById('modificarUsuariosForm').addEventListener('submit', function(event) {
        let isValid = true;
        let firstInvalidField = null; // Para enfocar el primer campo inválido si falla la validación
        let validationMessages = []; // Para almacenar los mensajes de error específicos por usuario

        // Eliminar clases de validación previas de todos los campos
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Iterar sobre cada fila (usuario) en la tabla para validar sus campos
        document.querySelectorAll('.table tbody tr').forEach(fila => {
            const userId = fila.dataset.userId; // Obtener el ID del usuario de la fila
            
            // Obtener referencias a los inputs y selects dentro de la fila actual
            const nombreInput = fila.querySelector('input[name$="[nombre]"]');
            const apellidosInput = fila.querySelector('input[name$="[apellidos]"]');
            const emailInput = fila.querySelector('input[name$="[email]"]');
            const telefonoInput = fila.querySelector('input[name$="[telefono]"]');
            const rolSelect = fila.querySelector('select[name$="[rol]"]');

            // --- Validaciones de campos vacíos ---
            if (nombreInput && nombreInput.value.trim() === '') {
                nombreInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: El nombre es obligatorio.`);
                if (!firstInvalidField) firstInvalidField = nombreInput;
            }
            if (apellidosInput && apellidosInput.value.trim() === '') {
                apellidosInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: Los apellidos son obligatorios.`);
                if (!firstInvalidField) firstInvalidField = apellidosInput;
            }
            if (emailInput && emailInput.value.trim() === '') {
                emailInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: El email es obligatorio.`);
                if (!firstInvalidField) firstInvalidField = emailInput;
            }
            if (telefonoInput && telefonoInput.value.trim() === '') {
                telefonoInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: El teléfono es obligatorio.`);
                if (!firstInvalidField) firstInvalidField = telefonoInput;
            }
            if (rolSelect && rolSelect.value === '') { // Si la opción por defecto "Selecciona un rol" está seleccionada
                rolSelect.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: Debe seleccionar un rol.`);
                if (!firstInvalidField) firstInvalidField = rolSelect;
            }

            // --- Validación de formato de email ---
            // Un regex más robusto para email, pero este es suficiente para una validación básica.
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput && emailInput.value.trim() !== '' && !emailPattern.test(emailInput.value.trim())) {
                emailInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: El email '${emailInput.value.trim()}' no tiene un formato válido.`);
                if (!firstInvalidField) firstInvalidField = emailInput;
            }

            // --- Validación de formato de teléfono (exactamente 9 dígitos numéricos) ---
            const phonePattern = /^[0-9]{9}$/;
            if (telefonoInput && telefonoInput.value.trim() !== '' && !phonePattern.test(telefonoInput.value.trim())) {
                telefonoInput.classList.add('is-invalid');
                isValid = false;
                validationMessages.push(`Usuario ${userId}: El teléfono '${telefonoInput.value.trim()}' debe tener exactamente 9 dígitos numéricos.`);
                if (!firstInvalidField) firstInvalidField = telefonoInput;
            }
        });

        // Si la validación falla en el cliente (isValid es false), mostrar SweetAlert de error
        if (!isValid) {
            event.preventDefault(); // Detener el envío del formulario al servidor
            
            // Construir el mensaje HTML para el SweetAlert con todos los errores
            let htmlMessage = 'Por favor, corrige los siguientes errores:<br><br><ul>';
            validationMessages.forEach(msg => {
                htmlMessage += `<li>${msg}</li>`;
            });
            htmlMessage += '</ul>';

            Swal.fire({
                title: '❌ Error de Validación',
                html: htmlMessage, // Muestra los mensajes de error específicos de validación
                icon: 'error',
                confirmButtonColor: '#d33', // Rojo para el botón de confirmar
                customClass: {
                    confirmButton: 'btn btn-danger'
                },
                buttonsStyling: false, // Deshabilita el estilo por defecto de SweetAlert para usar Bootstrap
                position: 'center' // Asegura que el SweetAlert esté centrado en la pantalla
            });

            if (firstInvalidField) {
                firstInvalidField.focus(); // Enfoca el primer campo con error para que el usuario lo vea
            }
        }
    });

    // --- SweetAlert para mensajes del servidor (éxito/error de la operación de guardado) ---
    <?php if (!empty($mensaje)): ?>
        // Utilizamos DOMContentLoaded para asegurarnos de que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            let swalTitle = '';
            let swalIcon = '';
            let confirmBtnClass = ''; // Clase CSS para el botón de confirmación

            if ('<?= $tipo ?>' === 'exito') {
                swalTitle = '✅ ¡Éxito!';
                swalIcon = 'success';
                confirmBtnClass = 'btn-success'; // Clase Bootstrap para botón verde
            } else if ('<?= $tipo ?>' === 'error') {
                swalTitle = '❌ ¡Error!';
                swalIcon = 'error';
                confirmBtnClass = 'btn-danger'; // Clase Bootstrap para botón rojo
            }

            Swal.fire({
                title: swalTitle,
                html: '<?= $mensaje ?>', // Usa 'html' para permitir formato HTML en el mensaje (ej. saltos de línea, listas)
                icon: swalIcon,
                confirmButtonColor: (swalIcon === 'success' ? '#218838' : '#d33'), // Color directo del botón
                customClass: {
                    confirmButton: 'btn ' + confirmBtnClass // Aplica la clase Bootstrap al botón
                },
                buttonsStyling: false, // Importante: desactiva los estilos por defecto de SweetAlert para usar los de Bootstrap
                position: 'center' // Asegura que el SweetAlert esté centrado en la pantalla
            });
        });
    <?php endif; ?>
</script>
</body>
