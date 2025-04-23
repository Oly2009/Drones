DROP DATABASE IF EXISTS agricultura;

-- Crear nueva base de datos
CREATE DATABASE agricultura CHARACTER SET utf8mb4 COLLATE=utf8mb4_spanish_ci;
USE agricultura;

-- Eliminar y recrear la base de datos
DROP DATABASE IF EXISTS agricultura;
CREATE DATABASE agricultura CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE agricultura;

-- Crear tabla: roles
CREATE TABLE roles (
  id_rol INT(11) NOT NULL AUTO_INCREMENT,
  nombre_rol VARCHAR(20) NOT NULL,
  PRIMARY KEY (id_rol)
);

INSERT INTO roles (id_rol, nombre_rol) VALUES
(1, 'Admin'),
(2, 'Piloto');

-- Crear tabla: usuarios
CREATE TABLE usuarios (
  id_usr INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  contrasena VARCHAR(100) NOT NULL,
  telefono VARCHAR(12) NOT NULL,
  email VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (id_usr)
);



-- Crear tabla: usuarios_roles
CREATE TABLE usuarios_roles (
  id_usr INT(11) NOT NULL,
  id_rol INT(11) NOT NULL,
  PRIMARY KEY (id_usr, id_rol),
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO usuarios_roles (id_usr, id_rol) VALUES



CREATE TABLE `parcelas` (
  `id_parcela` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL, -- Nombre corto o identificador de la parcela
  `ubicacion` VARCHAR(150) NOT NULL,  -- Dirección más detallada
  `tipo_cultivo` VARCHAR(100) DEFAULT NULL, -- Tipo de cultivo: trigo, maíz, olivar, etc.
  `area_m2` DECIMAL(10,2) DEFAULT NULL, -- Superficie estimada en metros cuadrados
  `latitud` DECIMAL(10,8) DEFAULT NULL,
  `longitud` DECIMAL(11,8) DEFAULT NULL,
  `fichero` VARCHAR(100) NOT NULL, -- Archivo GeoJSON
  `estado` ENUM('activa','inactiva','en descanso') DEFAULT 'activa', -- Estado de la parcela
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Registro de alta
  `observaciones` TEXT DEFAULT NULL, -- Notas adicionales
  PRIMARY KEY (`id_parcela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;


-- (Aquí puedes insertar todos los datos de parcelas que compartiste...)

-- Crear tabla: parcelas_usuarios
CREATE TABLE parcelas_usuarios (
  id_usr INT(11) NOT NULL,
  id_parcela INT(11) NOT NULL,
  PRIMARY KEY (id_usr, id_parcela),
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Crear tabla: tareas
CREATE TABLE tareas (
  id_tarea INT(11) NOT NULL AUTO_INCREMENT,
  nombre_tarea VARCHAR(20) NOT NULL,
  PRIMARY KEY (id_tarea)
);

INSERT INTO tareas (id_tarea, nombre_tarea) VALUES
(1, 'Sembrar'),
(2, 'Abonar'),
(3, 'Fumigar');

-- Crear tabla: drones
CREATE TABLE drones (
  id_dron INT(11) NOT NULL AUTO_INCREMENT,
  marca VARCHAR(30) NOT NULL,
  modelo VARCHAR(30) NOT NULL,
  numero_serie VARCHAR(50) NOT NULL UNIQUE,
  tipo ENUM('eléctrico','gasolina','híbrido','otro') NOT NULL,
  id_usr INT(11) DEFAULT NULL,
  id_parcela INT(11) DEFAULT NULL,
  id_tarea INT(11) NOT NULL,
  estado ENUM('disponible','en uso','en reparación','fuera de servicio') DEFAULT 'disponible',
  numero_vuelos INT(11) DEFAULT 0,
  PRIMARY KEY (id_dron),
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON UPDATE CASCADE
);

-- Crear tabla: ruta
CREATE TABLE ruta (
  id_ruta INT(11) NOT NULL AUTO_INCREMENT,
  latitud DECIMAL(10,8) NOT NULL,
  longitud DECIMAL(11,8) NOT NULL,
  id_parcela INT(11) NOT NULL,
  PRIMARY KEY (id_ruta),
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Crear tabla: trabajos
CREATE TABLE trabajos (
  id_trabajo INT(11) NOT NULL AUTO_INCREMENT,
  fecha DATE DEFAULT NULL,
  hora TIME DEFAULT NULL,
  id_dron INT(11) DEFAULT NULL,
  id_parcela INT(11) NOT NULL,
  id_usr INT(11) DEFAULT NULL,
  estado_general ENUM('pendiente','en curso','finalizado') DEFAULT 'pendiente',
  PRIMARY KEY (id_trabajo),
  FOREIGN KEY (id_dron) REFERENCES drones(id_dron) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Crear tabla: trabajos_tareas
CREATE TABLE trabajos_tareas (
  id_trabajo INT(11) NOT NULL,
  id_tarea INT(11) NOT NULL,
  PRIMARY KEY (id_trabajo, id_tarea),
  FOREIGN KEY (id_trabajo) REFERENCES trabajos(id_trabajo) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE CASCADE ON UPDATE CASCADE
);
