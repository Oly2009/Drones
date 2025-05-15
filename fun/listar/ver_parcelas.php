<?php
include '../../lib/functiones.php';
session_start();

// Verificar login (mantenemos tu lógica de verificación)
if (!isset($_SESSION['usuario'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso denegado",
                text: "Debes iniciar sesión para acceder a esta página",
                icon: "error",
                confirmButtonText: "Volver",
                confirmButtonColor: "#dc3545"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../../index.php";
                }
            });
        });
    </script>';
    exit;
}

$conexion = conectar();

// Obtener todas las parcelas para la lista (para la columna izquierda)
$resultadoParcelasLista = mysqli_query($conexion, "SELECT id_parcela, COALESCE(nombre, ubicacion) AS nombre_ubicacion FROM parcelas");
$parcelasLista = [];
while ($fila = mysqli_fetch_assoc($resultadoParcelasLista)) {
    $parcelasLista[] = $fila;
}
mysqli_free_result($resultadoParcelasLista);

// Inicializar la variable para los detalles de la parcela (para la columna derecha)
$detalleParcelaHTML = '<div class="alert alert-info w-100 text-center"><i class="bi bi-map-fill me-2"></i> Selecciona una parcela para ver sus detalles.</div>';

// =========== PETICIONES AJAX PARA DETALLE DE PARCELA ============
if (isset($_GET['action']) && $_GET['action'] === 'cargar_detalle_parcela' && isset($_GET['id_parcela'])) {
    $id_parcela_ajax = intval($_GET['id_parcela']);

    // Obtener datos de la parcela (AJAX request)
    $stmtParcelaAjax = $conexion->prepare("SELECT * FROM parcelas WHERE id_parcela = ?");
    $stmtParcelaAjax->bind_param("i", $id_parcela_ajax);
    $stmtParcelaAjax->execute();
    $parcela_ajax_result = $stmtParcelaAjax->get_result();
    $parcela_ajax = $parcela_ajax_result->fetch_assoc();
    $stmtParcelaAjax->close();

    if ($parcela_ajax) {
        $detalleHTML = '<div class="card shadow-sm">';
        $detalleHTML .= '<h5 class="card-header bg-success text-white"><i class="bi bi-map-fill me-2"></i> Detalles de la Parcela</h5>';
        $detalleHTML .= '<div class="card-body">';
        $detalleHTML .= '<h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-info-circle me-2"></i> Información General</h6>';
        $detalleHTML .= '<ul class="list-unstyled">';
        $detalleHTML .= '<li><strong>Nombre:</strong> ' . htmlspecialchars($parcela_ajax['nombre'] ?? 'Sin nombre') . '</li>';
        $detalleHTML .= '<li><strong>Ubicación:</strong> ' . htmlspecialchars($parcela_ajax['ubicacion']) . '</li>';
        $detalleHTML .= '<li><strong>Tipo de cultivo:</strong> ' . htmlspecialchars($parcela_ajax['tipo_cultivo'] ?? 'No especificado') . '</li>';
        $detalleHTML .= '<li><strong>Área:</strong> ' . number_format($parcela_ajax['area_m2'], 2) . ' m²</li>';
        $detalleHTML .= '<li><strong>Estado:</strong> <span class="' . ($parcela_ajax['estado'] === 'activa' ? 'text-success' : ($parcela_ajax['estado'] === 'inactiva' ? 'text-danger' : 'text-warning')) . '">' . htmlspecialchars(ucfirst($parcela_ajax['estado'])) . '</span></li>';
        $detalleHTML .= '<li><strong>Fecha de registro:</strong> ' . htmlspecialchars($parcela_ajax['fecha_registro']) . '</li>';
        $detalleHTML .= '<li><strong>Observaciones:</strong> ' . nl2br(htmlspecialchars($parcela_ajax['observaciones'] ?? 'Sin observaciones')) . '</li>';
        $tiene_ruta_ajax = mysqli_num_rows(mysqli_query($conexion, "SELECT id_ruta FROM ruta WHERE id_parcela = $id_parcela_ajax")) > 0;
        $detalleHTML .= '<li><strong>Ruta asignada:</strong> ' . ($tiene_ruta_ajax ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Sí</span>' : '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> No</span>') . '</li>';
        $detalleHTML .= '</ul>';
        $detalleHTML .= '</div>'; // card-body

        // Usuarios asignados (AJAX request)
        $stmtUsuariosAjax = $conexion->prepare("SELECT u.nombre, u.apellidos, u.email FROM usuarios u INNER JOIN parcelas_usuarios pu ON u.id_usr = pu.id_usr WHERE pu.id_parcela = ?");
        $stmtUsuariosAjax->bind_param("i", $id_parcela_ajax);
        $stmtUsuariosAjax->execute();
        $usuarios_ajax_result = $stmtUsuariosAjax->get_result();
        if ($usuarios_ajax_result->num_rows > 0) {
            $detalleHTML .= '<div class="card-body">';
            $detalleHTML .= '<h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-people-fill me-2"></i> Usuarios Asignados</h6>';
            $detalleHTML .= '<ul class="list-group">';
            while ($u_ajax = $usuarios_ajax_result->fetch_assoc()) {
                $detalleHTML .= '<li class="list-group-item">' . htmlspecialchars($u_ajax['nombre'] . ' ' . $u_ajax['apellidos']) . ' <small>(' . htmlspecialchars($u_ajax['email']) . ')</small></li>';
            }
            $detalleHTML .= '</ul>';
            $detalleHTML .= '</div>'; // card-body
        }
        $stmtUsuariosAjax->close();

        // Drones asignados (AJAX request)
        $stmtDronesAjax = $conexion->prepare("SELECT d.marca, d.modelo, d.estado FROM drones d WHERE d.id_parcela = ?");
        $stmtDronesAjax->bind_param("i", $id_parcela_ajax);
        $stmtDronesAjax->execute();
        $drones_ajax_result = $stmtDronesAjax->get_result();
        if ($drones_ajax_result->num_rows > 0) {
            $detalleHTML .= '<div class="card-body">';
            $detalleHTML .= '<h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-drone-fill me-2"></i> Drones Asignados</h6>';
            $detalleHTML .= '<ul class="list-group">';
            while ($d_ajax = $drones_ajax_result->fetch_assoc()) {
                $detalleHTML .= '<li class="list-group-item">' . htmlspecialchars($d_ajax['marca'] . ' ' . $d_ajax['modelo']) . ' <span class="badge bg-secondary">' . htmlspecialchars(ucfirst($d_ajax['estado'])) . '</span></li>';
            }
            $detalleHTML .= '</ul>';
            $detalleHTML .= '</div>'; // card-body
        }
        $stmtDronesAjax->close();

        $detalleHTML .= '</div>'; // card shadow-sm

        echo $detalleHTML; // Enviamos el HTML del detalle si es una petición AJAX
        mysqli_close($conexion);
        exit;
    } else {
        echo '<div class="alert alert-warning w-100 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> Parcela no encontrada.</div>';
        mysqli_close($conexion);
        exit;
    }
}

mysqli_close($conexion);

include '../../componentes/header.php';
?>
<link rel="stylesheet" href="../../css/style.css">
<style>
    
</style>

<body class="d-flex flex-column min-vh-100">
<main class="container-fluid py-3">
    <h2 class="text-center mb-4 text-success"><i class="bi bi-tree-fill me-2"></i> Ver Información de Parcelas</h2>
    <div class="row">
        <div class="col-md-4 border-end pe-3">
            <h4 class="text-success mb-3"><i class="bi bi-list-ul me-2"></i> Parcelas registradas</h4>
            <ul class="list-group" id="listaParcelas">
                <?php foreach ($parcelasLista as $parcelaItem): ?>
                    <li class="list-group-item list-group-item-action" onclick="cargarDetalleParcela(<?= $parcelaItem['id_parcela'] ?>, this)">
                        <?= htmlspecialchars($parcelaItem['nombre_ubicacion']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="col-md-8" id="detalleParcela">
            <?= $detalleParcelaHTML ?>
        </div>
    </div>
    <div class="text-center mt-5">
        <a href="../../menu/parcelas.php" class="btn btn-danger"><i class="bi bi-arrow-left-circle-fill me-2"></i> Volver a la lista de Parcelas</a>
    </div>
</main>

<script>
const currentFile = window.location.pathname.split('/').pop();
const listaItems = document.querySelectorAll('#listaParcelas li');

function cargarDetalleParcela(idParcela, listItem) {
    if (!idParcela) {
        document.getElementById('detalleParcela').innerHTML = '<div class="alert alert-info w-100 text-center"><i class="bi bi-map-fill me-2"></i> Selecciona una parcela para ver sus detalles.</div>';
        // Desactivar la clase 'active' de todos los elementos de la lista
        listaItems.forEach(item => item.classList.remove('active'));
        return;
    }
    document.getElementById('detalleParcela').innerHTML = '<div class="alert alert-info w-100 text-center"><i class="bi bi-hourglass-split me-2"></i> Cargando detalles de la parcela...</div>';
    const timestamp = new Date().getTime();
    fetch(`${currentFile}?action=cargar_detalle_parcela&id_parcela=${idParcela}&_=${timestamp}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('detalleParcela').innerHTML = data;
            // Activar la clase 'active' en el elemento de la lista clickeado
            listaItems.forEach(item => item.classList.remove('active'));
            listItem.classList.add('active');
        })
        .catch(error => {
            console.error('Error al cargar detalle:', error);
            document.getElementById('detalleParcela').innerHTML = '<div class="alert alert-danger w-100 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> No se pudieron cargar los detalles de la parcela.</div>';
            // Desactivar la clase 'active' de todos los elementos de la lista en caso de error
            listaItems.forEach(item => item.classList.remove('active'));
        });
}

document.addEventListener('DOMContentLoaded', () => {
    // Puedes agregar aquí alguna acción inicial si lo necesitas
});
</script>

</body>
</html>