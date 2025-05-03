<?php
include '../../lib/functiones.php';
session_start();

// Verificar si el usuario ha iniciado sesión
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
$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas ORDER BY id_parcela ASC");
include '../../componentes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="../../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<body class="d-flex flex-column min-vh-100">
<main class="container py-2 flex-grow-1">
  <h1 class="titulo-listado text-center mb-4">🌱 Mis Parcelas</h1>

  <section class="mb-4">
    <form method="get" class="d-flex justify-content-center">
      <input type="text" id="buscarParcela" class="form-control w-50 me-2" placeholder="🔍 Buscar por ubicación o nombre...">
      <button class="btn btn-success" type="button" id="botonBuscar">Buscar</button>
    </form>
  </section>

  <?php if ($parcelas->num_rows > 0): ?>
    <section class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="contenedorParcelas">
      <?php while ($parcela = $parcelas->fetch_assoc()): ?>
        <?php
        $ruta_geojson = "../agregar/parcelas/" . $parcela['fichero'];
        $geojson_data = @file_get_contents($ruta_geojson);
        $geojson = json_decode($geojson_data, true);

        $centro_lat = $parcela['latitud'];
        $centro_lon = $parcela['longitud'];

        $tiene_ruta = mysqli_query($conexion, "SELECT COUNT(*) as total FROM ruta WHERE id_parcela = " . $parcela['id_parcela']);
        $tiene_ruta_row = mysqli_fetch_assoc($tiene_ruta);
        $hay_ruta = $tiene_ruta_row['total'] > 0;
        ?>
        <div class="col parcela-item" data-ubicacion="<?= htmlspecialchars(strtolower($parcela['ubicacion'])) ?>" data-nombre="<?= htmlspecialchars(strtolower($parcela['nombre'])) ?>">
          <div class="card shadow-sm h-100 small">
            <div id="map_<?= $parcela['id_parcela'] ?>" style="height: 200px;" class="rounded-top">
              <?php if (!$geojson_data): ?>
                <div class="d-flex justify-content-center align-items-center h-100 bg-light text-muted">
                  <small>No se pudo cargar la vista previa del mapa</small>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
              <div>
                <h5 class="card-title"><?= htmlspecialchars($parcela['nombre'] ?: $parcela['ubicacion']) ?></h5>
                <p class="card-text mb-1"><small class="text-muted">📍 <?= htmlspecialchars($parcela['ubicacion']) ?></small></p>
                <p class="card-text mb-1">🌱 <?= htmlspecialchars($parcela['tipo_cultivo'] ?: 'No especificado') ?></p>
                <p class="card-text mb-1">📏 <?= number_format($parcela['area_m2'], 2) ?> m²</p>
                <p class="card-text mb-0">🚁 Ruta: <strong><?= $hay_ruta ? 'Asignada' : 'No asignada' ?></strong></p>
              </div>
              <div class="mt-3">
                <div class="d-grid gap-2">
                  <a href="../modificar/mod_parcelas.php?id=<?= $parcela['id_parcela'] ?>&fichero=<?= urlencode($parcela['fichero']) ?>&latitud=<?= $parcela['latitud'] ?>&longitud=<?= $parcela['longitud'] ?>" class="btn btn-success btn-sm">✏️ Editar</a>
                  <a href="obs_parcela.php?id=<?= $parcela['id_parcela'] ?>" class="btn btn-primary btn-sm">👁️ Ver Datos</a>
                  <a href="ruta.php?id=<?= $parcela['id_parcela'] ?>" class="btn btn-warning btn-sm">➕ Agregar ruta</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          var map_<?= $parcela['id_parcela'] ?> = L.map('map_<?= $parcela['id_parcela'] ?>').setView([<?= $centro_lat ?: 40.4168 ?>, <?= $centro_lon ?: -3.7038 ?>], 15);
          L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
          }).addTo(map_<?= $parcela['id_parcela'] ?>);

          fetch('<?= $ruta_geojson ?>')
            .then(response => response.json())
            .then(data => {
              L.geoJSON(data, {
                style: {
                  color: '#28a745',
                  weight: 2,
                  opacity: 1,
                  fillColor: '#a2d189',
                  fillOpacity: 0.5
                }
              }).addTo(map_<?= $parcela['id_parcela'] ?>);
              if (data.features && data.features.length > 0 && data.features[0].geometry) {
                var bounds = L.geoJSON(data).getBounds();
                if (bounds.isValid()) {
                  map_<?= $parcela['id_parcela'] ?>.fitBounds(bounds);
                }
              }
            })
            .catch(error => console.error('Error al cargar GeoJSON:', error));
        });
        </script>
      <?php endwhile; ?>
    </section>
  <?php else: ?>
    <div class="text-center">
      <p>No tienes parcelas registradas aún. ¿Por qué no comienzas añadiendo una?</p>
      <a href="../agregar/agr_parcelas.php" class="btn btn-success">➕ Añadir nueva parcela</a>
    </div>
  <?php endif; ?>

  <div class="text-center mt-4">
    <a href="../../menu/parcelas.php" class="btn btn-danger">⬅️ Volver al menú de Parcelas</a>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('buscarParcela');
  const botonBuscar = document.getElementById('botonBuscar');
  const parcelasItems = document.querySelectorAll('#contenedorParcelas .parcela-item');

  function filtrarParcelas() {
    const term = searchInput.value.toLowerCase();
    parcelasItems.forEach(item => {
      const ubicacion = item.dataset.ubicacion || '';
      const nombre = item.dataset.nombre || '';
      item.style.display = (ubicacion.includes(term) || nombre.includes(term)) ? 'block' : 'none';
    });
  }

  searchInput.addEventListener('input', filtrarParcelas);
  botonBuscar.addEventListener('click', filtrarParcelas);
});
</script>

<?php include '../../componentes/footer.php'; ?>
</body>
