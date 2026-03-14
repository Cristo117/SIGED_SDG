-- SIGED - Base de datos para despliegue (solo administrador)
-- Sin clientes, empleados ni datos de prueba.
-- Usuario: admin | Contraseña: admin123
-- Cambie la contraseña desde Ajustes después del primer inicio de sesión.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Tablas (estructura completa con migraciones aplicadas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `usuario_admin` (
  `usuario_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `rol` varchar(20) NOT NULL DEFAULT 'ADMINISTRADOR',
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auditoria` (
  `auditoria_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `detalle` longtext DEFAULT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`auditoria_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario_admin` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cliente` (
  `cliente_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `apellidos` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_identificacion` varchar(30) DEFAULT NULL,
  `identificacion` varchar(50) DEFAULT NULL,
  `tipo_cliente` varchar(30) NOT NULL,
  `estado_pago` varchar(30) DEFAULT 'AL_DIA',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cobrar_seguridad_social_mensual` tinyint(1) NOT NULL DEFAULT 1,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`cliente_id`),
  UNIQUE KEY `identificacion` (`identificacion`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuario_admin` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `configuracion` (
  `configuracion_id` int(11) NOT NULL AUTO_INCREMENT,
  `valor_afiliacion` decimal(12,2) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`configuracion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `empleado` (
  `empleado_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellidos` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_documento` varchar(30) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`empleado_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grupo_familiar` (
  `familiar_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `empleado_id` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo_documento` varchar(30) NOT NULL,
  `numero_documento` varchar(50) NOT NULL,
  `parentesco` varchar(50) DEFAULT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`familiar_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `empleado_id` (`empleado_id`),
  CONSTRAINT `grupo_familiar_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE,
  CONSTRAINT `grupo_familiar_ibfk_2` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `info_adicional` (
  `info_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `empleado_id` int(11) DEFAULT NULL,
  `etiqueta` varchar(50) NOT NULL,
  `valor` varchar(15) NOT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`info_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `empleado_id` (`empleado_id`),
  CONSTRAINT `info_adicional_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE,
  CONSTRAINT `info_adicional_ibfk_2` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proceso` (
  `proceso_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`proceso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proceso_cliente` (
  `proceso_cliente_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `valor_aplicado` decimal(12,2) NOT NULL,
  `estado` varchar(30) DEFAULT 'ACTIVO',
  `fecha_asignacion` date NOT NULL,
  `fecha_vencimiento_pago` date DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado_pago` varchar(30) DEFAULT 'PENDIENTE',
  PRIMARY KEY (`proceso_cliente_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `proceso_id` (`proceso_id`),
  CONSTRAINT `proceso_cliente_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE,
  CONSTRAINT `proceso_cliente_ibfk_2` FOREIGN KEY (`proceso_id`) REFERENCES `proceso` (`proceso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cliente_documento` (
  `documento_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `creado_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`documento_id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `cliente_documento_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notificacion` (
  `notificacion_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `proceso_cliente_id` int(11) DEFAULT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo_alerta` varchar(50) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `creada_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notificacion_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `proceso_cliente_id` (`proceso_cliente_id`),
  CONSTRAINT `notificacion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario_admin` (`usuario_id`),
  CONSTRAINT `notificacion_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`cliente_id`) ON DELETE CASCADE,
  CONSTRAINT `notificacion_ibfk_3` FOREIGN KEY (`proceso_cliente_id`) REFERENCES `proceso_cliente` (`proceso_cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `historial_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `usuario_nombre` varchar(150) NOT NULL,
  `usuario_rol` varchar(20) NOT NULL DEFAULT 'COLABORADOR',
  `accion` varchar(50) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  `detalle` text DEFAULT NULL,
  `creado_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_creado` (`creado_at`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Datos iniciales (solo administrador y configuración)
-- --------------------------------------------------------

INSERT INTO `usuario_admin` (`usuario_id`, `username`, `email`, `password_hash`, `nombre`, `activo`, `rol`, `creado_at`) VALUES
(1, 'admin', 'admin@siged.local', '$2y$10$CDvyZgtt3zfaizOkLCxyWuMknSvgRVy/TaHYdNqPEyHaMbblH5A8S', 'Administrador', 1, 'ADMINISTRADOR', CURRENT_TIMESTAMP);

INSERT INTO `configuracion` (`configuracion_id`, `valor_afiliacion`, `activo`, `creado_at`) VALUES
(1, 250000.00, 1, CURRENT_TIMESTAMP);
