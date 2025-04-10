DROP DATABASE IF EXISTS agricultura;

-- Crear nueva base de datos
CREATE DATABASE agricultura CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE agricultura;

-- Tabla de Roles
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar roles
INSERT INTO roles (nombre_rol) VALUES 
('Admin'), 
('Piloto');

-- Tabla de Usuarios
CREATE TABLE usuarios (
  id_usr INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  contrasena VARCHAR(100) NOT NULL,
  telefono VARCHAR(12) NOT NULL,
  email VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de Usuarios-Roles (N:M)
CREATE TABLE usuarios_roles (
  id_usr INT NOT NULL,
  id_rol INT NOT NULL,
  PRIMARY KEY (id_usr, id_rol),
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de Tareas
CREATE TABLE tareas (
  id_tarea INT AUTO_INCREMENT PRIMARY KEY,
  nombre_tarea VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar tareas base
INSERT INTO tareas (nombre_tarea) VALUES 
('Sembrar'), 
('Abonar'), 
('Fumigar');

-- Tabla de Parcelas
CREATE TABLE parcelas (
  id_parcela INT AUTO_INCREMENT PRIMARY KEY,
  ubicacion VARCHAR(100) NOT NULL,
  fichero VARCHAR(100) NOT NULL,
  latitud DECIMAL(10,8),
  longitud DECIMAL(11,8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla intermedia: Parcelas - Usuarios (1:N)
CREATE TABLE parcelas_usuarios (
  id_usr INT NOT NULL,
  id_parcela INT NOT NULL,
  PRIMARY KEY (id_usr),
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de Drones
CREATE TABLE drones (
  id_dron INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE,
  marca VARCHAR(20) NOT NULL,
  id_usr INT DEFAULT NULL,
  id_parcela INT DEFAULT NULL,
  id_tarea INT NOT NULL, -- nueva columna para indicar la tarea del dron
  estado ENUM('disponible','estropeado') DEFAULT 'disponible',
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de Rutas
CREATE TABLE ruta (
  id_ruta INT AUTO_INCREMENT PRIMARY KEY,
  latitud DECIMAL(10,8) NOT NULL,
  longitud DECIMAL(11,8) NOT NULL,
  id_parcela INT NOT NULL,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de Trabajos
CREATE TABLE trabajos (
  id_trabajo INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE DEFAULT NULL,
  hora TIME DEFAULT NULL,
  id_dron INT DEFAULT NULL,
  id_parcela INT NOT NULL,
  id_usr INT DEFAULT NULL,
  estado_general ENUM('pendiente','en curso','finalizado') DEFAULT 'pendiente',
  FOREIGN KEY (id_dron) REFERENCES drones(id_dron) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (id_parcela) REFERENCES parcelas(id_parcela) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_usr) REFERENCES usuarios(id_usr) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla de relación trabajos - tareas (N:M)
CREATE TABLE trabajos_tareas (
  id_trabajo INT NOT NULL,
  id_tarea INT NOT NULL,
  PRIMARY KEY (id_trabajo, id_tarea),
  FOREIGN KEY (id_trabajo) REFERENCES trabajos(id_trabajo) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;