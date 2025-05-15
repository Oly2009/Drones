
    <?php include '../../componentes/header.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <div class="container">
        <?php
        if (isset($_GET['origen'])) {
            $archivoOrigen = $_GET['origen'];

            switch ($archivoOrigen) {
                case 'registro.php':
                    echo '<h1>Ayuda de la página: Alta de Usuario</h1>';
                    echo '<p>Esta página te permite crear una nueva cuenta de usuario para acceder al sistema AgroSky. Debes completar todos los campos del formulario y hacer clic en el botón "Registrar" para crear la cuenta.</p>';
                    echo '<h2>Campos del formulario:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Nombre:</strong> Introduce tu nombre.</li>';
                    echo '<li><strong>Apellidos:</strong> Introduce tus apellidos.</li>';
                    echo '<li><strong>Email:</strong> Introduce una dirección de correo electrónico válida. Esta será tu nombre de usuario.</li>';
                    echo '<li><strong>Contraseña:</strong> Introduce una contraseña segura.</li>';
                    echo '<li><strong>Confirmar Contraseña:</strong> Vuelve a introducir la contraseña para confirmar que coinciden.</li>';
                    echo '</ul>';
                    break;

                case 'modificar_usuarios.php':
                    echo '<h1>Ayuda de la página: Modificar Usuario</h1>';
                    echo '<p>En esta página puedes editar la información de un usuario existente. Selecciona el usuario que deseas modificar y actualiza los campos necesarios. Haz clic en "Guardar Cambios" para aplicar las modificaciones.</p>';
                    echo '<h2>Acciones disponibles:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Seleccionar Usuario:</strong> Elige un usuario de la lista para ver su información actual.</li>';
                    echo '<li><strong>Editar Campos:</strong> Modifica el nombre, apellidos, email o contraseña del usuario seleccionado.</li>';
                    echo '<li><strong>Guardar Cambios:</strong> Guarda la información actualizada del usuario.</li>';
                    echo '</ul>';
                    break;

                case 'eli_usuarios.php':
                    echo '<h1>Ayuda de la página: Eliminar Usuario</h1>';
                    echo '<p>Esta página te permite eliminar usuarios del sistema. <strong>Ten cuidado al realizar esta acción, ya que es irreversible.</strong> Selecciona el usuario que deseas eliminar y haz clic en el botón "Eliminar Usuario". Se te pedirá una confirmación antes de proceder.</p>';
                    break;

                case 'lis_usuarios.php':
                    echo '<h1>Ayuda de la página: Listar Usuarios</h1>';
                    echo '<p>Esta página muestra la lista de todos los usuarios registrados en el sistema. Puedes ver su nombre, apellidos y email. No se pueden realizar modificaciones desde esta página.</p>';
                    break;

                case 'mod_parcelas.php':
                    echo '<h1>Ayuda de la página: Modificar Parcela</h1>';
                    echo '<p>Desde esta página puedes modificar los detalles de una parcela existente. Selecciona la parcela que deseas editar y actualiza los campos como nombre, tipo de cultivo y área. Puedes también modificar su ubicación en el mapa. Haz clic en "Guardar Cambios" para aplicar las modificaciones.</p>';
                    echo '<h2>Campos editables:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Nombre:</strong> El nombre identificativo de la parcela.</li>';
                    echo '<li><strong>Tipo de Cultivo:</strong> El tipo de cultivo principal de la parcela.</li>';
                    echo '<li><strong>Área:</strong> El tamaño de la parcela.</li>';
                    echo '<li><strong>Ubicación:</strong> La ubicación geográfica de la parcela marcada en el mapa.</li>';
                    echo '</ul>';
                    break;

                case 'agr_parcelas.php':
                    echo '<h1>Ayuda de la página: Añadir Parcela</h1>';
                    echo '<p>Esta página te permite registrar una nueva parcela en el sistema. Introduce el nombre, selecciona el tipo de cultivo, indica el área y marca su ubicación en el mapa. Haz clic en "Guardar Parcela" para registrarla.</p>';
                    echo '<h2>Campos requeridos:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Nombre:</strong> Un nombre para identificar la nueva parcela.</li>';
                    echo '<li><strong>Tipo de Cultivo:</strong> Selecciona el tipo de cultivo.</li>';
                    echo '<li><strong>Área:</strong> Introduce el tamaño de la parcela.</li>';
                    echo '<li><strong>Ubicación en el Mapa:</strong> Debes marcar la ubicación de la parcela en el mapa.</li>';
                    echo '</ul>';
                    break;

                case 'eli_parcelas.php':
                    echo '<h1>Ayuda de la página: Eliminar Parcela</h1>';
                    echo '<p>Esta página te permite eliminar parcelas del sistema. <strong>Ten en cuenta que al eliminar una parcela, también se pueden eliminar los trabajos y datos de drones asociados a ella.</strong> Selecciona la parcela que deseas eliminar y haz clic en "Eliminar Parcela". Se te pedirá una confirmación.</p>';
                    break;

                case 'lis_parcelas.php':
                    echo '<h1>Ayuda de la página: Listar Parcelas</h1>';
                    echo '<p>Esta página muestra un listado de todas las parcelas registradas en el sistema, junto con sus nombres, tipos de cultivo y áreas. Desde aquí puedes acceder a la vista detallada de cada parcela.</p>';
                    break;

                case 'ver_parcelas.php':
                    echo '<h1>Ayuda de la página: Ver Parcela</h1>';
                    echo '<p>En esta página puedes ver los detalles completos de una parcela específica, incluyendo su nombre, tipo de cultivo, área y ubicación en el mapa. También puede mostrar observaciones o datos adicionales asociados a la parcela.</p>';
                    break;

                case 'mod_drones.php':
                    echo '<h1>Ayuda de la página: Modificar Dron</h1>';
                    echo '<p>Desde esta página puedes editar la información de un dron existente. Selecciona el dron que deseas modificar y actualiza campos como marca, modelo y estado. Haz clic en "Guardar Cambios" para aplicar las modificaciones.</p>';
                    echo '<h2>Campos editables:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Marca:</strong> La marca del dron.</li>';
                    echo '<li><strong>Modelo:</strong> El modelo específico del dron.</li>';
                    echo '<li><strong>Número de Serie:</strong> El identificador único del dron.</li>';
                    echo '<li><strong>Tipo:</strong> El tipo de dron (ej. fumigación, mapeo).</li>';
                    echo '<li><strong>Estado:</strong> El estado actual del dron (ej. disponible, en mantenimiento).</li>';
                    echo '</ul>';
                    break;

                case 'agr_drones.php':
                    echo '<h1>Ayuda de la página: Añadir Dron</h1>';
                    echo '<p>Esta página te permite registrar un nuevo dron en el sistema. Introduce la marca, modelo, número de serie y tipo del dron. Haz clic en "Guardar Dron" para registrarlo.</p>';
                    echo '<h2>Campos requeridos:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Marca:</strong> La marca del dron.</li>';
                    echo '<li><strong>Modelo:</strong> El modelo específico del dron.</li>';
                    echo '<li><strong>Número de Serie:</strong> Un identificador único para el dron.</li>';
                    echo '<li><strong>Tipo:</strong> Selecciona el tipo de dron.</li>';
                    echo '</ul>';
                    break;

                case 'eli_drones.php':
                    echo '<h1>Ayuda de la página: Eliminar Dron</h1>';
                    echo '<p>Esta página te permite eliminar drones del sistema. <strong>Asegúrate de que el dron no esté actualmente asignado a ningún trabajo activo antes de eliminarlo.</strong> Selecciona el dron y haz clic en "Eliminar Dron". Se te pedirá una confirmación.</p>';
                    break;

                case 'lis_drones.php':
                    echo '<h1>Ayuda de la página: Listar Drones</h1>';
                    echo '<p>Esta página muestra la lista de todos los drones registrados en el sistema, incluyendo su marca, modelo y número de serie. Desde aquí puedes acceder a la edición de cada dron.</p>';
                    break;

                case 'agr_trabajos.php':
                    echo '<h1>Ayuda de la página: Añadir Trabajo</h1>';
                    echo '<p>Esta página te permite programar un nuevo trabajo agrícola. Selecciona la parcela, el dron que se asignará al trabajo y la fecha de ejecución. Puedes añadir detalles o instrucciones adicionales si es necesario. Haz clic en "Programar Trabajo" para guardar la tarea.</p>';
                    echo '<h2>Campos requeridos:</h2>';
                    echo '<ul>';
                    echo '<li><strong>Parcela:</strong> Selecciona la parcela donde se realizará el trabajo.</li>';
                    echo '<li><strong>Dron:</strong> Elige el dron que llevará a cabo la tarea.</li>';
                    echo '<li><strong>Fecha de Ejecución:</strong> La fecha en la que se realizará el trabajo.</li>';
                    echo '</ul>';
                    break;

                case 'eli_trabajos.php':
                    echo '<h1>Ayuda de la página: Eliminar Trabajo</h1>';
                    echo '<p>Esta página te permite eliminar trabajos programados. <strong>Ten cuidado al eliminar trabajos que ya están en curso o completados.</strong> Selecciona el trabajo y haz clic en "Eliminar Trabajo". Se te pedirá confirmación.</p>';
                    break;

                case 'eje_trabajos.php':
                    echo '<h1>Ayuda de la página: Ejecutar Trabajo</h1>';
                    echo '<p>En esta página puedes marcar un trabajo como en ejecución y gestionar su progreso. Puedes ver detalles del trabajo, el dron asignado y la parcela. También puede haber opciones para registrar el inicio y fin del trabajo, y posiblemente cargar datos o resultados.</p>';
                    break;

                case 'lis_trabajos.php':
                    echo '<h1>Ayuda de la página: Listar Trabajos</h1>';
                    echo '<p>Esta página muestra la lista de todos los trabajos programados en el sistema, incluyendo la parcela, el dron asignado, la fecha y el estado. Desde aquí puedes acceder a la edición o ejecución de cada trabajo.</p>';
                    break;

                default:
                    echo '<h1>Ayuda</h1>';
                    echo '<p>No hay información de ayuda específica disponible para esta página.</p>';
                    break;
            }
        } else {
            echo '<h1>Ayuda General</h1>';
            echo '<p>Bienvenido a la página de ayuda general de AgroSky. Selecciona el icono de ayuda en la página donde necesites asistencia para obtener información específica.</p>';
        }
        ?>

        <div class="volver-menu">
            <a href="../../menu/menu.php" class="btn btn-success"><i class="bi bi-arrow-left-circle me-2"></i>Volver al Menú Principal</a>
        </div>
    </div>
<?php include '../../componentes/footer.php'; ?>

