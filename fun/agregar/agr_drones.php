<?php
include '../../lib/functiones.php';
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>➕ Agregar Dron - AgroSky</title>
    <link rel="stylesheet" href="../../css/agregarDrones.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>

<?php
$mensaje = null;
$tipo = null;

if (isset($_SESSION['usuario'])) {
    $conexion = conectar();
    $idUsr = $_SESSION['usuario']['id_usr'];

    $rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idUsr");
    $esAdmin = false;
    while ($rol = mysqli_fetch_assoc($rolCheck)) {
        if ($rol['id_rol'] == 1) {
            $esAdmin = true;
            break;
        }
    }

    if (!$esAdmin) {
        echo "<div class='mensaje-error'>⛔ Acceso denegado. Solo los administradores pueden agregar drones.</div>";
        echo '<div class="botones"><a href="../menu.php" class="btn-secundario">Volver al menú</a></div>';
        exit();
    }

    if (isset($_POST['anhadir'])) {
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $numero_serie = trim($_POST['numero_serie']);
        $tipo_dron = trim($_POST['tipo']);
        $id_parcela = intval($_POST['parcela']);
        $id_tarea = intval($_POST['tarea']);

        // Validaciones
        $existeSerie = mysqli_query($conexion, "SELECT * FROM drones WHERE numero_serie='$numero_serie'");
        if (mysqli_num_rows($existeSerie) > 0) {
            $mensaje = "⚠ Ya existe un dron con ese número de serie.";
            $tipo = "error";
        } else {
            $insert = "INSERT INTO drones (marca, modelo, numero_serie, tipo, id_usr, id_parcela, id_tarea, estado)
                       VALUES ('$marca', '$modelo', '$numero_serie', '$tipo_dron', $idUsr, $id_parcela, $id_tarea, 'disponible')";
            if (mysqli_query($conexion, $insert)) {
                $mensaje = "✅ Dron añadido correctamente.";
                $tipo = "exito";
            } else {
                $mensaje = "❌ Error al insertar el dron.";
                $tipo = "error";
            }
        }
    }

    $parcelas = mysqli_query($conexion, "SELECT * FROM parcelas");
    $tareas = mysqli_query($conexion, "SELECT * FROM tareas");
?>

<div class="formulario-container">
    <h1>🛸 Agregar Dron</h1>

    <form action="agr_drones.php" method="post" class="formulario">
        <label>🏷️ Marca</label>
        <input type="text" name="marca" required placeholder="Marca del dron">

        <label>📦 Modelo</label>
        <input type="text" name="modelo" required placeholder="Modelo del dron">

        <label>🔢 Número de serie</label>
        <input type="text" name="numero_serie" required placeholder="Número de serie único">

        <label>🧬 Tipo</label>
        <select name="tipo" required>
            <option value="">-- Selecciona tipo --</option>
            <option value="eléctrico">Eléctrico</option>
            <option value="combustible">Combustible</option>
            <option value="híbrido">Híbrido</option>
        </select>

        <label>📍 Asignar a parcela</label>
        <select name="parcela" required>
            <option value="">-- Selecciona una parcela --</option>
            <?php while ($fila = mysqli_fetch_array($parcelas)) {
                echo "<option value='{$fila['id_parcela']}'>{$fila['ubicacion']}</option>";
            } ?>
        </select>

        <label>🛠️ Tarea específica</label>
        <select name="tarea" required>
            <option value="">-- Selecciona una tarea --</option>
            <?php while ($fila = mysqli_fetch_array($tareas)) {
                echo "<option value='{$fila['id_tarea']}'>{$fila['nombre_tarea']}</option>";
            } ?>
        </select>

        <div class="botones">
            <button type="submit" name="anhadir" class="btn btn-primario">Agregar</button>
            <a href="../../menu/drones.php" class="btn btn-secundario">Volver</a>
        </div>
    </form>
</div>

<?php if ($mensaje): ?>
<div class="modal" id="modalMensaje">
  <div class="modal-contenido <?= $tipo === 'exito' ? 'exito' : 'error' ?>">
    <h2><?= $mensaje ?></h2>
    <button onclick="cerrarModal()">Cerrar</button>
  </div>
</div>
<script>
function cerrarModal() {
    document.getElementById("modalMensaje").style.display = "none";
}
</script>
<?php endif; ?>

<?php
} else {
    echo "<p class='mensaje-error'>⛔ Acceso denegado</p>";
    echo '<a href="../../index.php" class="btn-secundario">Volver</a>';
    session_destroy();
}
?>

</body>
</html>
