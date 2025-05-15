<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso denegado",
                text: "Debes iniciar sesión para acceder a esta página",
                icon: "error",
                confirmButtonText: "Volver",
                confirmButtonColor: "#dc3545"
            }).then(() => window.location.href = "../../index.php");
        });
    </script>';
    exit;
}

$conexion = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_parcelas']) && !empty($_POST['seleccionadas'])) {
    $ids_eliminar = array_map('intval', $_POST['seleccionadas']);
    $eliminadas_count = 0;

    foreach ($ids_eliminar as $id_parcela) {
        $stmt = $conexion->prepare("SELECT fichero FROM parcelas WHERE id_parcela = ?");
        $stmt->bind_param("i", $id_parcela);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $ruta = __DIR__ . "/../agregar/parcelas/" . $row['fichero'];
            if (file_exists($ruta) && !unlink($ruta)) {
                $_SESSION['mensaje_error'] = "Error al eliminar el fichero de la parcela ID $id_parcela.";
                header("Location: eli_parcelas.php");
                exit;
            }
        }
        $stmt->close();

        $conexion->query("DELETE FROM parcelas_usuarios WHERE id_parcela = $id_parcela");
        $conexion->query("UPDATE drones SET id_parcela = NULL WHERE id_parcela = $id_parcela");
        $conexion->query("UPDATE trabajos SET id_parcela = NULL WHERE id_parcela = $id_parcela");
        $conexion->query("DELETE FROM ruta WHERE id_parcela = $id_parcela");

        if ($conexion->query("DELETE FROM parcelas WHERE id_parcela = $id_parcela")) {
            $eliminadas_count++;
        } else {
            $_SESSION['mensaje_error'] = "Error al eliminar la parcela ID $id_parcela.";
            header("Location: eli_parcelas.php");
            exit;
        }
    }

    $_SESSION['mensaje'] = "$eliminadas_count " . ($eliminadas_count === 1 ? "parcela eliminada" : "parcelas eliminadas") . " correctamente.";
    header("Location: eli_parcelas.php");
    exit;
}

$resultado = $conexion->query("SELECT id_parcela, COALESCE(nombre, ubicacion) AS nombre, ubicacion, tipo_cultivo, area_m2 FROM parcelas ORDER BY fecha_registro DESC");
$parcelas = $resultado->fetch_all(MYSQLI_ASSOC);
$conexion->close();

include '../../componentes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Parcelas</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<main class="container py-5 flex-grow-1">
    <h1 class="titulo-listado">
        <i class="bi bi-x-circle-fill me-2 text-danger"></i>Eliminar Parcelas
    </h1>

    <form method="post" id="formEliminarParcelas">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Tipo</th>
                        <th>Área</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parcelas as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['ubicacion']) ?></td>
                            <td><?= htmlspecialchars($p['tipo_cultivo']) ?></td>
                            <td><?= number_format($p['area_m2'], 2) ?> m²</td>
                            <td class="text-center">
                                <input type="checkbox" name="seleccionadas[]" value="<?= $p['id_parcela'] ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

       <div class="text-center mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-center align-items-stretch gap-3">
        <!-- Botón visible con SweetAlert -->
        <button type="button" onclick="confirmarEliminar()" class="btn btn-danger w-100 w-sm-auto px-4">
            <i class="bi bi-trash"></i> Eliminar seleccionados
        </button>

        <!-- Botón oculto que dispara el POST real -->
        <button type="submit" name="eliminar_parcelas" id="submitOculto" style="display: none;"></button>

        <a href="../../menu/parcelas.php" class="btn btn-success w-100 w-sm-auto px-4">
            <i class="bi bi-arrow-left-circle"></i> Volver al menú de parcelas
        </a>
    </div>
</div>
    </form>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
function confirmarEliminar() {
    const seleccionados = document.querySelectorAll('input[name="seleccionadas[]"]:checked');
    if (seleccionados.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: '⚠️ Atención',
            text: 'Debes seleccionar al menos una parcela para eliminar.',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: '¿Estás seguro?',
        html: 'Las parcelas seleccionadas serán eliminadas.<br>Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('submitOculto').click();
        }
    });
}

<?php if (isset($_SESSION['mensaje'])): ?>
Swal.fire({
    icon: 'success',
    title: '✅ Éxito',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonColor: '#28a745'
});
<?php unset($_SESSION['mensaje']); endif; ?>

<?php if (isset($_SESSION['mensaje_error'])): ?>
Swal.fire({
    icon: 'error',
    title: '❌ Error',
    text: <?= json_encode($_SESSION['mensaje_error']) ?>,
    confirmButtonColor: '#dc3545'
});
<?php unset($_SESSION['mensaje_error']); endif; ?>
</script>
</body>
</html>
