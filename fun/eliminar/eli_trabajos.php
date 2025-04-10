<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();
$mensaje = "";

// Eliminar trabajo si se ha enviado el formulario
if (isset($_POST['eliminar']) && isset($_POST['id_trabajo'])) {
    $id = intval($_POST['id_trabajo']);

    $delete_tareas = "DELETE FROM trabajos_tareas WHERE id_trabajo = $id";
    $delete_trabajo = "DELETE FROM trabajos WHERE id_trabajo = $id";

    if (mysqli_query($con, $delete_tareas) && mysqli_query($con, $delete_trabajo)) {
        $mensaje = "✅ Trabajo eliminado correctamente.";
    } else {
        $mensaje = "❌ Error al eliminar el trabajo.";
    }
}

// Obtener lista de trabajos
$trabajos_q = "SELECT t.id_trabajo, t.fecha, p.ubicacion, d.nombre AS dron, GROUP_CONCAT(tt.id_tarea SEPARATOR ', ') AS tareas
               FROM trabajos t
               JOIN parcelas p ON t.id_parcela = p.id_parcela
               JOIN drones d ON t.id_dron = d.id_dron
               LEFT JOIN trabajos_tareas tt ON tt.id_trabajo = t.id_trabajo
               GROUP BY t.id_trabajo
               ORDER BY t.fecha DESC";
$trabajos = mysqli_query($con, $trabajos_q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar trabajos</title>
    <link rel="stylesheet" href="../../css/eliminarTrabajos.css">
</head>
<body>
    <h2 class="titulo">🗑️ Eliminar trabajos asignados</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje-exito"> <?= $mensaje ?> </div>
    <?php endif; ?>

    <div class="buscador">
        <input type="text" id="filtro" placeholder="🔍 Buscar por parcela o dron..." onkeyup="filtrarTabla()">
    </div>

    <table class="tabla-parcelas" id="tablaTrabajos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Parcela</th>
                <th>Dron</th>
                <th>Tarea(s)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($trabajo = mysqli_fetch_assoc($trabajos)) { ?>
                <tr>
                    <td><?= $trabajo['fecha'] ?></td>
                    <td><?= $trabajo['ubicacion'] ?></td>
                    <td><?= $trabajo['dron'] ?></td>
                    <td><?= $trabajo['tareas'] ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="id_trabajo" value="<?= $trabajo['id_trabajo'] ?>">
                            <button type="submit" name="eliminar" class="btn">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 20px;">
        <form action="../trabajos.php" method="post">
            <button type="submit" class="btn">⬅ Volver</button>
        </form>
    </div>

    <script>
    function filtrarTabla() {
        const input = document.getElementById("filtro").value.toLowerCase();
        const filas = document.querySelectorAll("#tablaTrabajos tbody tr");

        filas.forEach(fila => {
            const textoFila = fila.textContent.toLowerCase();
            fila.style.display = textoFila.includes(input) ? "" : "none";
        });
    }
    </script>
</body>
</html>
