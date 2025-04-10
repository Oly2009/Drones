<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();
$mensaje = "";

// Si se ha enviado el formulario para asignar trabajo
if (isset($_POST['asignar_trabajo'])) {
    $id_parcela = intval($_POST['parcela']);
    $id_dron = intval($_POST['dron']);
    $tarea = mysqli_real_escape_string($con, $_POST['tarea']);
    $id_usuario = $_SESSION['usuario']['id_usr'];

    // Verificar si el dron tiene trabajos activos en curso
    $verifica_q = "SELECT 1 
                   FROM trabajos t
                   JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
                   WHERE t.id_dron = $id_dron 
                   AND (t.estado_general = 'en curso' 
                        OR (t.estado_general = 'pendiente' AND tt.estado = 'en curso'))";
    $verifica_result = mysqli_query($con, $verifica_q);

    if ($verifica_result && mysqli_num_rows($verifica_result) > 0) {
        $nombre_parcela = mysqli_fetch_assoc(mysqli_query($con, "SELECT ubicacion FROM parcelas WHERE id_parcela = $id_parcela"))['ubicacion'];
        $marca_dron = mysqli_fetch_assoc(mysqli_query($con, "SELECT marca FROM drones WHERE id_dron = $id_dron"))['marca'];
        $mensaje = "⚠ No se puede asignar un nuevo trabajo. El dron de marca <strong>$marca_dron</strong> tiene un trabajo en curso.";
    } else {
        // Insertar el trabajo con id_usr del usuario logueado
        mysqli_query($con, "INSERT INTO trabajos (id_parcela, id_dron, fecha, estado_general, id_usr) 
                            VALUES ($id_parcela, $id_dron, CURDATE(), 'pendiente', $id_usuario)");
        $id_trabajo = mysqli_insert_id($con);

        mysqli_query($con, "INSERT INTO trabajos_tareas (id_trabajo, id_tarea, estado)
                            VALUES ($id_trabajo, 
                                    (SELECT id_tarea FROM tareas WHERE nombre_tarea = '$tarea'),
                                    'pendiente')");

        mysqli_query($con, "UPDATE drones SET estado = 'en uso' WHERE id_dron = $id_dron");

        $nombre_parcela = mysqli_fetch_assoc(mysqli_query($con, "SELECT ubicacion FROM parcelas WHERE id_parcela = $id_parcela"))['ubicacion'];
        $marca_dron = mysqli_fetch_assoc(mysqli_query($con, "SELECT marca FROM drones WHERE id_dron = $id_dron"))['marca'];

        $mensaje = "✅ Trabajo de <strong>$tarea</strong> asignado a <strong>$nombre_parcela</strong> con un dron <strong>$marca_dron</strong>.";
    }
}

// Consulta de drones disponibles con más flexibilidad
$drones_q = "SELECT d.id_dron, d.id_parcela, d.marca
             FROM drones d
             WHERE d.estado = 'disponible'
             AND NOT EXISTS (
                SELECT 1 FROM trabajos t 
                JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
                WHERE t.id_dron = d.id_dron 
                AND (t.estado_general = 'en curso' 
                     OR (t.estado_general = 'pendiente' AND tt.estado = 'en curso'))
             )";
$drones_result = mysqli_query($con, $drones_q);
$drones_por_parcela = [];
while ($dron = mysqli_fetch_assoc($drones_result)) {
    $drones_por_parcela[$dron['id_parcela']][] = $dron;
}

// Parcelas con ruta asignada
$parcelas_q = "SELECT p.id_parcela, p.ubicacion,
                      IF(r.id_parcela IS NULL, 'Sin ruta asignada', 'Ruta asignada') AS ruta_estado
               FROM parcelas p
               LEFT JOIN ruta r ON p.id_parcela = r.id_parcela
               GROUP BY p.id_parcela
               HAVING ruta_estado = 'Ruta asignada'";
$parcelas = mysqli_query($con, $parcelas_q);

// Tareas disponibles
$tareas_q = mysqli_query($con, "SELECT nombre_tarea FROM tareas");
$tareas = [];
while ($fila = mysqli_fetch_assoc($tareas_q)) {
    $tareas[] = $fila['nombre_tarea'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar trabajo</title>
    <link rel="stylesheet" href="../../css/agregarTrabajos.css">
</head>
<body>
    <h2 class="titulo">🛠️ Asignar trabajo a parcelas</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje-exito"><?= $mensaje ?></div>
    <?php endif; ?>

    <div class="buscador">
        <input type="text" id="filtro" placeholder="🔍 Buscar parcela..." onkeyup="filtrarTabla()">
    </div>

    <table class="tabla-parcelas" id="tablaParcelas">
        <thead>
            <tr>
                <th>Ubicación</th>
                <th>Ruta</th>
                <th>Drones disponibles</th>
                <th>Asignar trabajo</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($parcela = mysqli_fetch_assoc($parcelas)) {
                $id = $parcela['id_parcela'];
                $ubicacion = $parcela['ubicacion'];
                $ruta = $parcela['ruta_estado'];
                $drones = isset($drones_por_parcela[$id]) ? $drones_por_parcela[$id] : null;
                ?>
                <tr>
                    <td><?= $ubicacion ?></td>
                    <td class="<?= $ruta === 'Ruta asignada' ? 'estado-ok' : 'estado-alerta' ?>"><?= $ruta ?></td>
                    <td>
                        <?php if ($drones): ?>
                            <select class="styled-select">
                                <?php foreach ($drones as $d): ?>
                                    <option><?= $d['marca'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <span style="color: gray;">Drones no disponibles</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ruta === 'Ruta asignada' && $drones): ?>
                            <form method="post" class="form-asignar">
                                <input type="hidden" name="parcela" value="<?= $id ?>">
                                <input type="hidden" name="dron" value="<?= $drones[0]['id_dron'] ?>">
                                <select name="tarea" class="styled-select" required>
                                    <?php foreach ($tareas as $t): ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="asignar_trabajo" class="btn">Asignar</button>
                            </form>
                        <?php else: ?>
                            <span style="color:gray;">No disponible</span>
                        <?php endif; ?>
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
        let input = document.getElementById("filtro").value.toLowerCase();
        let filas = document.querySelectorAll("#tablaParcelas tbody tr");

        filas.forEach(fila => {
            let nombre = fila.cells[0].textContent.toLowerCase();
            fila.style.display = nombre.includes(input) ? "" : "none";
        });
    }
    </script>
</body>
</html>
