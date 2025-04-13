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
    <link rel="stylesheet" href="../../css/listarDrones.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
</head>
<body>
    <h2 class="titulo">📋 Listado de todos los trabajos</h2>

    <div class="buscador-container">
        <input type="text" id="filtro" placeholder="🔍 Buscar por cualquier campo...">
        <button class="btn-buscar" onclick="filtrarTabla()">Buscar</button>
    </div>

    <div class="tabla-container">
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
                        <td data-label="Fecha"><?= $trabajo['fecha'] ?></td>
                        <td data-label="Hora"><?= $trabajo['hora'] ?></td>
                        <td data-label="Parcela"><?= $trabajo['parcela'] ?></td>
                        <td data-label="Dron"><?= $trabajo['dron'] . ' ' . $trabajo['modelo'] ?></td>
                        <td data-label="Serie"><?= $trabajo['numero_serie'] ?></td>
                        <td data-label="Tarea(s)"><?= $trabajo['tareas'] ?></td>
                        <td data-label="Usuario">
                            <?= $trabajo['nombre_usuario'] ? htmlspecialchars($trabajo['nombre_usuario'] . ' ' . $trabajo['apellidos_usuario']) : 'Desconocido' ?>
                        </td>
                        <td data-label="Estado">
                            <span class="estado-<?= strtolower(str_replace(' ', '-', $trabajo['estado_general'])) ?>">
                                <?= ucfirst($trabajo['estado_general']) ?>
                            </span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
      

    </div>
  <div id="mensajeNoResultados" style="display: none; text-align: center; margin-top: 1rem; font-weight: bold; color: #c62828;">
    ❌ No se encontraron resultados.
</div>
    <div class="volver-contenedor">
        <a href="../../menu/trabajos.php" class="btn btn-secundario">⬅ Volver</a>
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
