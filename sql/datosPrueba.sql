DROP DATABASE IF EXISTS agricultura;

-- Crear nueva base de datos
CREATE DATABASE agricultura CHARACTER SET utf8mb4 COLLATE=utf8mb4_spanish_ci;
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

-- Crear tabla: parcelas
CREATE TABLE parcelas (
  id_parcela INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) DEFAULT NULL,
  ubicacion VARCHAR(150) NOT NULL,
  tipo_cultivo VARCHAR(100) DEFAULT NULL,
  area_m2 DECIMAL(10,2) DEFAULT NULL,
  latitud DECIMAL(10,8) DEFAULT NULL,
  longitud DECIMAL(11,8) DEFAULT NULL,
  fichero VARCHAR(100) NOT NULL,
  estado ENUM('activa','inactiva','en descanso') DEFAULT 'activa',
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  observaciones TEXT DEFAULT NULL,
  PRIMARY KEY (id_parcela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

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

CREATE TABLE trabajos (
  id_trabajo INT(11) NOT NULL AUTO_INCREMENT,
  fecha_asignacion DATE NOT NULL, -- fecha cuando se asigna el trabajo
  fecha_ejecucion DATE DEFAULT NULL, -- se actualiza al ejecutar el trabajo
  hora TIME DEFAULT NULL, -- hora opcional de ejecución
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