DROP DATABASE IF EXISTS agricultura;

-- Crear nueva base de datos
CREATE DATABASE agricultura CHARACTER SET utf8mb4 COLLATE=utf8mb4_spanish_ci;
USE agricultura;

--
-- Estructura de tabla para la tabla `drones`
--
DROP TABLE IF EXISTS `drones`;
CREATE TABLE `drones` (
  `id_dron` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(30) NOT NULL,
  `modelo` varchar(30) NOT NULL,
  `numero_serie` varchar(50) NOT NULL UNIQUE,
  `tipo` enum('eléctrico','gasolina','híbrido','otro') NOT NULL,
  `id_usr` int(11) DEFAULT NULL,
  `id_parcela` int(11) DEFAULT NULL,
  `id_tarea` int(11) NOT NULL,
  `estado` enum('disponible','en uso','en reparación','fuera de servicio') DEFAULT 'disponible',
  `numero_vuelos` int(11) DEFAULT 0,
  PRIMARY KEY (`id_dron`),
  KEY `id_usr` (`id_usr`),
  KEY `id_parcela` (`id_parcela`),
  KEY `id_tarea` (`id_tarea`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `parcelas`
--
DROP TABLE IF EXISTS `parcelas`;
CREATE TABLE `parcelas` (
  `id_parcela` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `tipo_cultivo` varchar(100) DEFAULT NULL,
  `area_m2` decimal(10,2) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `fichero` varchar(100) NOT NULL,
  `estado` enum('activa','inactiva','en descanso') DEFAULT 'activa',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_parcela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `parcelas_usuarios`
--
DROP TABLE IF EXISTS `parcelas_usuarios`;
CREATE TABLE `parcelas_usuarios` (
  `id_usr` int(11) NOT NULL,
  `id_parcela` int(11) NOT NULL,
  PRIMARY KEY (`id_usr`,`id_parcela`),
  KEY `id_parcela` (`id_parcela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `roles`
--
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(20) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `ruta`
--
DROP TABLE IF EXISTS `ruta`;
CREATE TABLE `ruta` (
  `id_ruta` int(11) NOT NULL AUTO_INCREMENT,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `id_parcela` int(11) NOT NULL,
  PRIMARY KEY (`id_ruta`),
  KEY `id_parcela` (`id_parcela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `tareas`
--
DROP TABLE IF EXISTS `tareas`;
CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tarea` varchar(20) NOT NULL,
  PRIMARY KEY (`id_tarea`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `trabajos`
--
DROP TABLE IF EXISTS `trabajos`;
CREATE TABLE `trabajos` (
  `id_trabajo` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_asignacion` date NOT NULL DEFAULT current_timestamp(),
  `fecha_ejecucion` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `id_dron` int(11) DEFAULT NULL,
  `id_parcela` int(11) NOT NULL,
  `id_usr` int(11) DEFAULT NULL,
  `estado_general` enum('pendiente','en curso','finalizado') DEFAULT 'pendiente',
  PRIMARY KEY (`id_trabajo`),
  KEY `id_dron` (`id_dron`),
  KEY `id_parcela` (`id_parcela`),
  KEY `id_usr` (`id_usr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `trabajos_tareas`
--
DROP TABLE IF EXISTS `trabajos_tareas`;
CREATE TABLE `trabajos_tareas` (
  `id_trabajo` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  PRIMARY KEY (`id_trabajo`,`id_tarea`),
  KEY `id_tarea` (`id_tarea`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `usuarios`
--
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usr` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `contrasena` varchar(100) NOT NULL,
  `telefono` varchar(12) NOT NULL,
  `email` varchar(50) NOT NULL UNIQUE,
  PRIMARY KEY (`id_usr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `usuarios_roles`
--
DROP TABLE IF EXISTS `usuarios_roles`;
CREATE TABLE `usuarios_roles` (
  `id_usr` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  PRIMARY KEY (`id_usr`,`id_rol`),
  KEY `id_rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Insertamos los roles 
--
INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Admin'),
(2, 'Piloto');
--
-- Insertamos las tareas 
--
INSERT INTO `tareas` (`id_tarea`, `nombre_tarea`) VALUES
(1, 'Sembrar'),
(2, 'Abonar'),
(3, 'Fumigar');