<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();

// Solo mostrar trabajos finalizados
$trabajos_q = "SELECT t.fecha, p.ubicacion AS parcela, d.marca AS dron, 
                      GROUP_CONCAT(ta.nombre_tarea SEPARATOR ', ') AS tareas,
                      u.nombre AS nombre_usuario, u.apellidos AS apellidos_usuario
               FROM trabajos t
               JOIN parcelas p ON t.id_parcela = p.id_parcela
               JOIN drones d ON t.id_dron = d.id_dron
               LEFT JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
               LEFT JOIN tareas ta ON ta.id_tarea = tt.id_tarea
               LEFT JOIN usuarios u ON t.id_usr = u.id_usr
               WHERE t.estado_general = 'finalizado'
               GROUP BY t.id_trabajo
               ORDER BY t.fecha DESC";

$trabajos = mysqli_query($con, $trabajos_q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de trabajos finalizados</title>
    <link rel="stylesheet" href="../../css/listarTrabajos.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h2 class="titulo">✅ Listado de trabajos finalizados</h2>

    <div class="buscador">
        <input type="text" id="filtro" placeholder="🔍 Buscar por parcela, dron o usuario..." onkeyup="filtrarTabla()">
    </div>

    <table class="tabla-parcelas" id="tablaTrabajos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Parcela</th>
                <th>Marca del Dron</th>
                <th>Tarea(s)</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($trabajo = mysqli_fetch_assoc($trabajos)) { ?>
                <tr>
                    <td data-label="Fecha"><?= $trabajo['fecha'] ?></td>
                    <td data-label="Parcela"><?= $trabajo['parcela'] ?></td>
                    <td data-label="Dron"><?= $trabajo['dron'] ?></td>
                    <td data-label="Tarea(s)"><?= $trabajo['tareas'] ?></td>
                    <td data-label="Usuario">
                        <?= $trabajo['nombre_usuario'] ? htmlspecialchars($trabajo['nombre_usuario'] . ' ' . $trabajo['apellidos_usuario']) : 'Desconocido' ?>
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
