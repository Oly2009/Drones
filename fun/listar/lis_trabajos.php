<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();

$trabajos_q = "SELECT t.fecha, t.hora, t.estado_general, p.ubicacion AS parcela, d.marca AS dron, d.modelo, d.numero_serie,
                      GROUP_CONCAT(ta.nombre_tarea SEPARATOR ', ') AS tareas,
                      u.nombre AS nombre_usuario, u.apellidos AS apellidos_usuario
               FROM trabajos t
               JOIN parcelas p ON t.id_parcela = p.id_parcela
               JOIN drones d ON t.id_dron = d.id_dron
               LEFT JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
               LEFT JOIN tareas ta ON ta.id_tarea = tt.id_tarea
               LEFT JOIN usuarios u ON t.id_usr = u.id_usr
               GROUP BY t.id_trabajo
               ORDER BY t.fecha DESC, t.hora DESC";

$trabajos = mysqli_query($con, $trabajos_q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de trabajos</title>
     <link rel="stylesheet" href="../../css/listarUsuarios.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h1 class="titulo">📋 Listado de todos los trabajos</h1>

    <div class="contenedor">
        <form class="busqueda-form" onsubmit="event.preventDefault(); filtrarTabla();">
            <input type="text" id="filtro" placeholder="🔍 Buscar por cualquier campo...">
            <button type="submit">Buscar</button>
        </form>

        <div class="tabla-responsive">
            <table id="tablaTrabajos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Parcela</th>
                        <th>Marca/Modelo</th>
                        <th>Nº Serie</th>
                        <th>Tarea(s)</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trabajo = mysqli_fetch_assoc($trabajos)) { ?>
                        <tr>
                            <td><?= $trabajo['fecha'] ?></td>
                            <td><?= $trabajo['hora'] ?></td>
                            <td><?= $trabajo['parcela'] ?></td>
                            <td><?= $trabajo['dron'] . ' ' . $trabajo['modelo'] ?></td>
                            <td><?= $trabajo['numero_serie'] ?></td>
                            <td><?= $trabajo['tareas'] ?></td>
                            <td>
                                <?= $trabajo['nombre_usuario'] ? htmlspecialchars($trabajo['nombre_usuario'] . ' ' . $trabajo['apellidos_usuario']) : 'Desconocido' ?>
                            </td>
                            <td>
                                <span class="estado-<?= strtolower(str_replace(' ', '-', $trabajo['estado_general'])) ?>">
                                    <?= ucfirst($trabajo['estado_general']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div id="mensajeNoResultados" class="sin-resultados" style="display: none;">
            ❌ No se encontraron resultados.
        </div>

        <a href="../../menu/trabajos.php" class="btn">⬅ Volver</a>
    </div>

    <script>
    function filtrarTabla() {
        const input = document.getElementById("filtro").value.toLowerCase();
        const filas = document.querySelectorAll("#tablaTrabajos tbody tr");
        let coincidencias = 0;

        filas.forEach(fila => {
            let textoFila = '';
            fila.querySelectorAll('td').forEach(td => {
                textoFila += td.innerText.toLowerCase() + ' ';
            });

            const coincide = textoFila.includes(input);
            fila.style.display = coincide ? "" : "none";
            if (coincide) coincidencias++;
        });

        const mensaje = document.getElementById("mensajeNoResultados");
        mensaje.style.display = coincidencias === 0 ? "block" : "none";
    }
    </script>
</body>
</html>
