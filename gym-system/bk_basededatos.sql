-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para ec_gym_system
CREATE DATABASE IF NOT EXISTS `ec_gym_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ec_gym_system`;

-- Volcando estructura para tabla ec_gym_system.asistencias
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.asistencias: ~30 rows (aproximadamente)
DELETE FROM `asistencias`;
INSERT INTO `asistencias` (`id`, `socio_id`, `fecha_hora`) VALUES
	(1, 3, '2026-04-04 16:24:31'),
	(2, 5, '2026-03-26 13:53:41'),
	(3, 8, '2026-03-28 21:47:22'),
	(4, 3, '2026-05-08 18:14:15'),
	(5, 2, '2026-03-25 11:35:28'),
	(6, 4, '2026-04-06 08:09:13'),
	(7, 7, '2026-04-24 22:37:52'),
	(8, 5, '2026-05-10 18:45:44'),
	(9, 8, '2026-04-20 21:51:21'),
	(10, 10, '2026-04-10 12:04:40'),
	(11, 3, '2026-05-08 14:06:15'),
	(12, 10, '2026-03-24 16:32:39'),
	(13, 6, '2026-03-24 10:00:00'),
	(14, 1, '2026-05-18 18:12:51'),
	(15, 8, '2026-03-23 22:27:50'),
	(16, 2, '2026-03-27 13:58:22'),
	(17, 6, '2026-05-10 18:56:32'),
	(18, 5, '2026-05-07 16:59:07'),
	(19, 4, '2026-05-03 15:28:48'),
	(20, 7, '2026-04-25 13:53:58'),
	(21, 1, '2026-03-28 11:55:27'),
	(22, 3, '2026-04-24 17:28:30'),
	(23, 7, '2026-03-29 07:46:20'),
	(24, 4, '2026-04-05 22:42:05'),
	(25, 2, '2026-04-25 08:23:03'),
	(26, 1, '2026-05-03 21:15:28'),
	(27, 7, '2026-05-08 21:32:20'),
	(28, 10, '2026-03-22 19:08:49'),
	(29, 8, '2026-05-05 21:54:21'),
	(30, 6, '2026-03-24 17:58:42');

-- Volcando estructura para tabla ec_gym_system.cajas
CREATE TABLE IF NOT EXISTS `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `monto_inicial` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) DEFAULT '0.00',
  `total_ventas` decimal(10,2) DEFAULT '0.00',
  `total_gastos` decimal(10,2) DEFAULT '0.00',
  `diferencia` decimal(10,2) DEFAULT '0.00',
  `fecha_apertura` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` datetime DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.cajas: ~7 rows (aproximadamente)
DELETE FROM `cajas`;
INSERT INTO `cajas` (`id`, `usuario_id`, `monto_inicial`, `monto_final`, `total_ventas`, `total_gastos`, `diferencia`, `fecha_apertura`, `fecha_cierre`, `estado`) VALUES
	(1, 1, 100.00, 263.00, 163.00, 0.00, 0.00, '2026-02-14 14:39:32', '2026-02-15 00:39:32', 'cerrada'),
	(2, 1, 100.00, 529.00, 429.00, 0.00, 0.00, '2025-11-23 07:16:34', '2025-11-23 17:16:34', 'cerrada'),
	(3, 1, 100.00, 791.00, 691.00, 0.00, 0.00, '2025-12-15 09:15:52', '2025-12-15 19:15:52', 'cerrada'),
	(4, 1, 100.00, 337.00, 237.00, 0.00, 0.00, '2026-03-28 14:41:48', '2026-03-29 00:41:48', 'cerrada'),
	(5, 1, 100.00, 739.00, 639.00, 0.00, 0.00, '2026-01-17 08:37:03', '2026-01-17 18:37:03', 'cerrada'),
	(6, 1, 100.00, 725.00, 625.00, 0.00, 0.00, '2025-12-09 19:03:01', '2025-12-10 05:03:01', 'cerrada'),
	(7, 1, 100.00, 0.00, 0.00, 0.00, 0.00, '2026-05-21 00:44:23', NULL, 'abierta');

-- Volcando estructura para tabla ec_gym_system.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.categorias: ~5 rows (aproximadamente)
DELETE FROM `categorias`;
INSERT INTO `categorias` (`id`, `nombre`, `estado`) VALUES
	(1, 'Bebidas', 'activo'),
	(2, 'Suplementos', 'activo'),
	(3, 'Accesorios', 'activo'),
	(4, 'Indumentaria', 'activo'),
	(5, 'Snacks', 'activo');

-- Volcando estructura para tabla ec_gym_system.comprobantes_detalle
CREATE TABLE IF NOT EXISTS `comprobantes_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comprobante_id` int NOT NULL,
  `linea` int NOT NULL DEFAULT '1',
  `codigo` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `unidad` varchar(5) DEFAULT 'NIU' COMMENT 'Cat.03: NIU=unidad, ZZ=servicio',
  `cantidad` decimal(12,3) NOT NULL DEFAULT '1.000',
  `valor_unitario` decimal(12,4) NOT NULL COMMENT 'Sin IGV',
  `precio_unitario` decimal(12,4) NOT NULL COMMENT 'Con IGV',
  `subtotal` decimal(12,2) NOT NULL COMMENT 'cantidad * valor_unitario',
  `igv_linea` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_linea` decimal(12,2) NOT NULL,
  `tipo_afectacion` varchar(2) DEFAULT '10' COMMENT 'Cat.07: 10=Grav., 20=Exo., 30=Inaf., 11/12/13...=Grav.gratuita',
  PRIMARY KEY (`id`),
  KEY `idx_comp` (`comprobante_id`),
  CONSTRAINT `fk_comp_detalle` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes_electronicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.comprobantes_detalle: ~10 rows (aproximadamente)
DELETE FROM `comprobantes_detalle`;
INSERT INTO `comprobantes_detalle` (`id`, `comprobante_id`, `linea`, `codigo`, `descripcion`, `unidad`, `cantidad`, `valor_unitario`, `precio_unitario`, `subtotal`, `igv_linea`, `total_linea`, `tipo_afectacion`) VALUES
	(1, 1, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 70.6700, 83.3900, 70.67, 12.72, 83.39, '10'),
	(2, 2, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 25.6100, 30.2200, 25.61, 4.61, 30.22, '10'),
	(3, 3, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 76.9000, 90.7400, 76.90, 13.84, 90.74, '10'),
	(4, 4, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 93.3900, 110.2000, 93.39, 16.81, 110.20, '10'),
	(5, 5, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 96.6500, 114.0500, 96.65, 17.40, 114.05, '10'),
	(6, 6, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 8.8800, 10.4800, 8.88, 1.60, 10.48, '10'),
	(7, 7, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 90.0800, 106.2900, 90.08, 16.21, 106.29, '10'),
	(8, 8, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 118.5700, 139.9100, 118.57, 21.34, 139.91, '10'),
	(9, 9, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 58.8300, 69.4200, 58.83, 10.59, 69.42, '10'),
	(10, 10, 1, 'ITM-001', 'Servicio / producto de prueba', 'ZZ', 1.000, 42.7100, 50.4000, 42.71, 7.69, 50.40, '10');

-- Volcando estructura para tabla ec_gym_system.comprobantes_electronicos
CREATE TABLE IF NOT EXISTS `comprobantes_electronicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origen_tipo` enum('suscripcion','venta','manual') NOT NULL,
  `origen_id` int DEFAULT NULL,
  `tipo_doc` varchar(2) NOT NULL COMMENT '01,03,07,08',
  `serie` varchar(17) NOT NULL,
  `correlativo` int NOT NULL,
  `clave_acceso` varchar(49) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `hora_emision` time DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `emisor_ruc` varchar(15) NOT NULL,
  `emisor_razon` varchar(255) NOT NULL,
  `cliente_tipo_doc` varchar(2) NOT NULL,
  `cliente_num_doc` varchar(20) DEFAULT NULL,
  `cliente_razon` varchar(255) NOT NULL,
  `cliente_direccion` varchar(255) DEFAULT NULL,
  `cliente_email` varchar(120) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT 'PEN',
  `gravadas` decimal(12,2) DEFAULT '0.00',
  `inafectas` decimal(12,2) DEFAULT '0.00',
  `exoneradas` decimal(12,2) DEFAULT '0.00',
  `gratuitas` decimal(12,2) DEFAULT '0.00',
  `descuentos` decimal(12,2) DEFAULT '0.00',
  `igv` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `total_letras` varchar(255) DEFAULT NULL,
  `forma_pago` enum('Contado','Credito') DEFAULT 'Contado',
  `metodo_pago` varchar(40) DEFAULT 'efectivo',
  `ref_tipo_doc` varchar(2) DEFAULT NULL,
  `ref_serie` varchar(4) DEFAULT NULL,
  `ref_correlativo` int DEFAULT NULL,
  `motivo_codigo` varchar(2) DEFAULT NULL COMMENT 'Cat.09 (NC) / Cat.10 (ND)',
  `motivo_descripcion` varchar(250) DEFAULT NULL,
  `xml_firmado` mediumtext,
  `sri_authorization_xml` mediumtext,
  `xml_hash` varchar(64) DEFAULT NULL COMMENT 'DigestValue SHA-1 / SHA-256',
  `cdr_zip` mediumblob,
  `cdr_codigo` varchar(10) DEFAULT NULL,
  `cdr_descripcion` varchar(255) DEFAULT NULL,
  `estado_sri` enum('pendiente','recibida','devuelta','autorizado','no_autorizado','anulado','error') DEFAULT 'pendiente',
  `estado_sunat` enum('pendiente','aceptado','rechazado','anulado','observado','enviando','error') DEFAULT 'pendiente',
  `mensaje_error` text,
  `usuario_id` int DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_comprobante` (`tipo_doc`,`serie`,`correlativo`),
  KEY `idx_estado` (`estado_sunat`),
  KEY `idx_fecha` (`fecha_emision`),
  KEY `idx_origen` (`origen_tipo`,`origen_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.comprobantes_electronicos: ~10 rows (aproximadamente)
DELETE FROM `comprobantes_electronicos`;
INSERT INTO `comprobantes_electronicos` (`id`, `origen_tipo`, `origen_id`, `tipo_doc`, `serie`, `correlativo`, `clave_acceso`, `fecha_emision`, `hora_emision`, `fecha_vencimiento`, `emisor_ruc`, `emisor_razon`, `cliente_tipo_doc`, `cliente_num_doc`, `cliente_razon`, `cliente_direccion`, `cliente_email`, `moneda`, `gravadas`, `inafectas`, `exoneradas`, `gratuitas`, `descuentos`, `igv`, `total`, `total_letras`, `forma_pago`, `metodo_pago`, `ref_tipo_doc`, `ref_serie`, `ref_correlativo`, `motivo_codigo`, `motivo_descripcion`, `xml_firmado`, `sri_authorization_xml`, `xml_hash`, `cdr_zip`, `cdr_codigo`, `cdr_descripcion`, `estado_sri`, `estado_sunat`, `mensaje_error`, `usuario_id`, `creado_en`, `actualizado_en`) VALUES
	(1, 'manual', NULL, '03', 'B001', 1, NULL, '2026-03-31', '19:06:46', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '39842614', 'Camila Reyes Apaza', NULL, NULL, 'PEN', 70.67, 0.00, 0.00, 0.00, 0.00, 12.72, 83.39, 'SON: 83.39 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'f552f6653d03ef0a68bf', NULL, '0', 'La Factura numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(2, 'manual', NULL, '03', 'B001', 2, NULL, '2026-02-17', '10:49:43', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '30806549', 'Jorge Silva Quispe', NULL, NULL, 'PEN', 25.61, 0.00, 0.00, 0.00, 0.00, 4.61, 30.22, 'SON: 30.22 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '27fbcf371010d57e5a50', NULL, '0', 'La Boleta numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(3, 'manual', NULL, '03', 'B001', 3, NULL, '2026-04-08', '12:20:48', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '29604397', 'Andrea Paredes Soto', NULL, NULL, 'PEN', 76.90, 0.00, 0.00, 0.00, 0.00, 13.84, 90.74, 'SON: 90.74 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1774c10c65f44e8448a9', NULL, '0', 'La Boleta numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(4, 'manual', NULL, '03', 'B001', 4, NULL, '2025-12-22', '10:22:43', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '35878690', 'María Castillo Ramos', NULL, NULL, 'PEN', 93.39, 0.00, 0.00, 0.00, 0.00, 16.81, 110.20, 'SON: 110.2 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '489a15f7a625d3061e2b', NULL, '0', 'La Boleta numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(5, 'manual', NULL, '01', 'F001', 1, NULL, '2026-02-16', '20:36:48', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '6', '20289320105', 'EMPRESA NORTE SRL', NULL, NULL, 'PEN', 96.65, 0.00, 0.00, 0.00, 0.00, 17.40, 114.05, 'SON: 114.05 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '423f32eb8ddb2e1b8894', NULL, '0', 'La Factura numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(6, 'manual', NULL, '01', 'F001', 2, NULL, '2026-01-18', '11:17:18', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '6', '20570905252', 'CONSTRUCTORA ABC SAC', NULL, NULL, 'PEN', 8.88, 0.00, 0.00, 0.00, 0.00, 1.60, 10.48, 'SON: 10.48 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5ac27aac1d08751c4208', NULL, '', 'Pendiente de envío', 'pendiente', 'pendiente', NULL, 1, '2026-05-21 00:44:23', '2026-05-21 00:44:23'),
	(7, 'manual', NULL, '03', 'B001', 5, NULL, '2026-05-18', '10:51:22', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '90467683', 'María Castillo Ramos', NULL, NULL, 'PEN', 90.08, 0.00, 0.00, 0.00, 0.00, 16.21, 106.29, 'SON: 106.29 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5bb4b41e19c9f20ba5c0', NULL, '2335', 'El XML está mal formado', 'no_autorizado', 'rechazado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(8, 'manual', NULL, '03', 'B001', 6, NULL, '2026-05-09', '19:58:59', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '74971747', 'Fernando Ruiz Cárdenas', NULL, NULL, 'PEN', 118.57, 0.00, 0.00, 0.00, 0.00, 21.34, 139.91, 'SON: 139.91 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'b7d99bea1e9cf607f71a', NULL, '0', 'La Boleta numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(9, 'manual', NULL, '07', 'BC01', 1, NULL, '2026-04-30', '16:32:40', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '51779826', 'Patricia Aguilar Núñez', NULL, NULL, 'PEN', 58.83, 0.00, 0.00, 0.00, 0.00, 10.59, 69.42, 'SON: 69.42 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'd3cfc9e001e7b04c4833', NULL, '0', 'La Nota de Crédito…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52'),
	(10, 'manual', NULL, '03', 'B001', 7, NULL, '2026-03-17', '19:17:21', NULL, '20000000001', 'EMPRESA DE PRUEBAS SAC', '1', '99899540', 'Fernando Ruiz Cárdenas', NULL, NULL, 'PEN', 42.71, 0.00, 0.00, 0.00, 0.00, 7.69, 50.40, 'SON: 50.4 SOLES', 'Contado', 'efectivo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08ab359d68cea3aa794d', NULL, '0', 'La Boleta numérica…', 'autorizado', 'aceptado', NULL, 1, '2026-05-21 00:44:23', '2026-05-23 06:43:52');

-- Volcando estructura para tabla ec_gym_system.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL,
  `nombre_sistema` varchar(100) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `razon_social` varchar(255) DEFAULT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ubigeo` varchar(6) DEFAULT '150101',
  `departamento` varchar(60) DEFAULT 'LIMA',
  `provincia` varchar(60) DEFAULT 'LIMA',
  `distrito` varchar(60) DEFAULT 'LIMA',
  `urbanizacion` varchar(120) DEFAULT NULL,
  `codigo_pais` varchar(2) DEFAULT 'PE',
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT '$',
  `sunat_ambiente` enum('beta','produccion') DEFAULT 'beta',
  `sunat_usuario_sol` varchar(50) DEFAULT NULL,
  `sunat_clave_sol` varchar(255) DEFAULT NULL,
  `sunat_certificado` varchar(255) DEFAULT NULL,
  `sunat_cert_clave` varchar(255) DEFAULT NULL,
  `igv_tasa` decimal(5,2) DEFAULT '18.00',
  `incluye_igv` tinyint(1) DEFAULT '1' COMMENT '1 = los precios mostrados YA incluyen IGV',
  `sri_ambiente` enum('1','2') DEFAULT '1' COMMENT '1=Pruebas, 2=Produccion',
  `sri_establecimiento` varchar(3) DEFAULT '001',
  `sri_punto_emision` varchar(3) DEFAULT '001',
  `sri_certificado_p12` varchar(255) DEFAULT NULL,
  `sri_certificado_clave` varchar(255) DEFAULT NULL,
  `iva_tasa` decimal(5,2) DEFAULT '15.00',
  `incluye_iva` tinyint(1) DEFAULT '1' COMMENT '1 = los precios mostrados YA incluyen IVA',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.configuracion: ~1 rows (aproximadamente)
DELETE FROM `configuracion`;
INSERT INTO `configuracion` (`id`, `nombre_sistema`, `ruc`, `razon_social`, `nombre_comercial`, `direccion`, `ubigeo`, `departamento`, `provincia`, `distrito`, `urbanizacion`, `codigo_pais`, `telefono`, `email`, `logo`, `moneda`, `sunat_ambiente`, `sunat_usuario_sol`, `sunat_clave_sol`, `sunat_certificado`, `sunat_cert_clave`, `igv_tasa`, `incluye_igv`, `sri_ambiente`, `sri_establecimiento`, `sri_punto_emision`, `sri_certificado_p12`, `sri_certificado_clave`, `iva_tasa`, `incluye_iva`) VALUES
	(1, 'Gimnasio Xtremo', '20600000001', 'EMPRESA DE PRUEBAS SAC', NULL, 'Av. Principal 123, Lima', '150101', 'LIMA', 'LIMA', 'LIMA', NULL, 'PE', '987654321', 'admin@gym.com', 'logo_empresa.png', '$', 'beta', 'MODDATOS', 'MODDATOS', NULL, NULL, 18.00, 1, '1', '001', '001', NULL, NULL, 15.00, 1);

-- Volcando estructura para tabla ec_gym_system.detalle_ventas
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.detalle_ventas: ~50 rows (aproximadamente)
DELETE FROM `detalle_ventas`;
INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
	(1, 1, 8, 1, 95.00, 95.00),
	(2, 1, 6, 2, 85.00, 170.00),
	(3, 1, 8, 2, 95.00, 190.00),
	(4, 2, 2, 1, 10.00, 10.00),
	(5, 2, 8, 2, 95.00, 190.00),
	(6, 3, 6, 2, 85.00, 170.00),
	(7, 3, 1, 2, 5.00, 10.00),
	(8, 3, 2, 1, 10.00, 10.00),
	(9, 4, 2, 3, 10.00, 30.00),
	(10, 4, 4, 1, 110.00, 110.00),
	(11, 4, 10, 2, 60.00, 120.00),
	(12, 5, 4, 2, 110.00, 220.00),
	(13, 5, 9, 1, 55.00, 55.00),
	(14, 6, 4, 2, 110.00, 220.00),
	(15, 6, 1, 3, 5.00, 15.00),
	(16, 7, 10, 1, 60.00, 60.00),
	(17, 8, 8, 3, 95.00, 285.00),
	(18, 9, 4, 2, 110.00, 220.00),
	(19, 9, 5, 1, 180.00, 180.00),
	(20, 9, 1, 2, 5.00, 10.00),
	(21, 10, 8, 1, 95.00, 95.00),
	(22, 11, 8, 3, 95.00, 285.00),
	(23, 11, 2, 2, 10.00, 20.00),
	(24, 12, 4, 2, 110.00, 220.00),
	(25, 13, 6, 2, 85.00, 170.00),
	(26, 13, 4, 2, 110.00, 220.00),
	(27, 13, 4, 2, 110.00, 220.00),
	(28, 14, 10, 3, 60.00, 180.00),
	(29, 14, 4, 2, 110.00, 220.00),
	(30, 14, 2, 3, 10.00, 30.00),
	(31, 15, 8, 2, 95.00, 190.00),
	(32, 15, 7, 2, 45.00, 90.00),
	(33, 15, 8, 1, 95.00, 95.00),
	(34, 16, 9, 2, 55.00, 110.00),
	(35, 17, 3, 2, 22.00, 44.00),
	(36, 17, 5, 3, 180.00, 540.00),
	(37, 17, 5, 1, 180.00, 180.00),
	(38, 18, 6, 2, 85.00, 170.00),
	(39, 18, 1, 3, 5.00, 15.00),
	(40, 18, 6, 3, 85.00, 255.00),
	(41, 19, 6, 1, 85.00, 85.00),
	(42, 20, 6, 1, 85.00, 85.00),
	(43, 20, 9, 1, 55.00, 55.00),
	(44, 21, 7, 2, 45.00, 90.00),
	(45, 22, 2, 3, 10.00, 30.00),
	(46, 22, 9, 3, 55.00, 165.00),
	(47, 23, 5, 3, 180.00, 540.00),
	(48, 24, 4, 3, 110.00, 330.00),
	(49, 24, 2, 1, 10.00, 10.00),
	(50, 25, 10, 2, 60.00, 120.00);

-- Volcando estructura para tabla ec_gym_system.gastos
CREATE TABLE IF NOT EXISTS `gastos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('creado','anulado') DEFAULT 'creado',
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.gastos: ~10 rows (aproximadamente)
DELETE FROM `gastos`;
INSERT INTO `gastos` (`id`, `descripcion`, `monto`, `fecha`, `estado`, `motivo_anulacion`) VALUES
	(1, 'Pago servicios eléctricos', 833.00, '2026-02-28', 'creado', NULL),
	(2, 'Compra de implementos', 377.00, '2026-02-01', 'creado', NULL),
	(3, 'Pago de agua', 494.00, '2026-02-05', 'creado', NULL),
	(4, 'Mantenimiento de máquinas', 597.00, '2026-02-07', 'creado', NULL),
	(5, 'Sueldo de entrenador', 578.00, '2026-05-15', 'creado', NULL),
	(6, 'Productos de limpieza', 854.00, '2026-03-17', 'creado', NULL),
	(7, 'Renta del local', 490.00, '2026-02-19', 'creado', NULL),
	(8, 'Publicidad redes sociales', 1124.00, '2026-03-15', 'creado', NULL),
	(9, 'Reparación aire acondicionado', 631.00, '2026-05-17', 'creado', NULL),
	(10, 'Compra de música/licencia', 1149.00, '2026-05-17', 'creado', NULL);

-- Volcando estructura para tabla ec_gym_system.medidas
CREATE TABLE IF NOT EXISTS `medidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `grasa` decimal(5,2) DEFAULT NULL,
  `cintura` decimal(5,2) DEFAULT NULL,
  `brazo` decimal(5,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `medidas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.medidas: ~0 rows (aproximadamente)
DELETE FROM `medidas`;

-- Volcando estructura para tabla ec_gym_system.planes
CREATE TABLE IF NOT EXISTS `planes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `duracion_dias` int NOT NULL,
  `descripcion` text,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.planes: ~10 rows (aproximadamente)
DELETE FROM `planes`;
INSERT INTO `planes` (`id`, `nombre`, `precio`, `duracion_dias`, `descripcion`, `estado`) VALUES
	(1, 'Mensual Básico', 80.00, 30, 'Acceso 6:00-22:00, área de pesas', 'activo'),
	(2, 'Mensual Premium', 150.00, 30, 'Incluye clases grupales y sauna', 'activo'),
	(3, 'Trimestral Básico', 210.00, 90, '3 meses con 10% descuento', 'activo'),
	(4, 'Trimestral Premium', 390.00, 90, 'Plan completo trimestral', 'activo'),
	(5, 'Semestral Fitness', 720.00, 180, '6 meses + entrenador 2 veces/mes', 'activo'),
	(6, 'Anual Full', 1300.00, 365, '12 meses ilimitados + nutricionista', 'activo'),
	(7, 'Day Pass', 15.00, 1, 'Pase por un día', 'activo'),
	(8, 'Semanal Estudiante', 35.00, 7, 'Solo con carnet universitario', 'activo'),
	(9, 'Plan Empresarial', 600.00, 180, 'Plan corporativo para 1 colaborador', 'activo'),
	(10, 'CrossFit Mensual', 200.00, 30, 'Box exclusivo de CrossFit', 'activo');

-- Volcando estructura para tabla ec_gym_system.productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `foto` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.productos: ~10 rows (aproximadamente)
DELETE FROM `productos`;
INSERT INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `precio_compra`, `precio_venta`, `stock`, `foto`, `estado`) VALUES
	(1, 1, 'BEB-001', 'Agua mineral 625ml', 3.00, 5.00, 27, NULL, 'activo'),
	(2, 2, 'BEB-002', 'Gatorade 500ml', 6.00, 10.00, 51, NULL, 'activo'),
	(3, 1, 'BEB-003', 'Bebida proteica', 15.00, 22.00, 69, NULL, 'activo'),
	(4, 2, 'SUP-001', 'Creatina 300g', 70.00, 110.00, 28, NULL, 'activo'),
	(5, 2, 'SUP-002', 'Proteína Whey 1kg', 120.00, 180.00, 48, NULL, 'activo'),
	(6, 4, 'SUP-003', 'BCAA 250g', 55.00, 85.00, 28, NULL, 'activo'),
	(7, 5, 'ACC-001', 'Guantes de gym', 25.00, 45.00, 51, NULL, 'activo'),
	(8, 1, 'ACC-002', 'Cinturón lumbar', 60.00, 95.00, 81, NULL, 'activo'),
	(9, 5, 'IND-001', 'Polo Iron Gym', 30.00, 55.00, 56, NULL, 'activo'),
	(10, 4, 'IND-002', 'Short deportivo', 35.00, 60.00, 52, NULL, 'activo');

-- Volcando estructura para tabla ec_gym_system.rutinas
CREATE TABLE IF NOT EXISTS `rutinas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `dia1` text,
  `dia2` text,
  `dia3` text,
  `dia4` text,
  `dia5` text,
  `dia6` text,
  `observaciones` text,
  `fecha_asignacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.rutinas: ~0 rows (aproximadamente)
DELETE FROM `rutinas`;

-- Volcando estructura para tabla ec_gym_system.socios
CREATE TABLE IF NOT EXISTS `socios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `tipo_doc` varchar(1) DEFAULT '1' COMMENT '1=DNI, 6=RUC, 4=CE, 7=PAS, 0=SinDoc',
  `direccion_fiscal` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp_api_key` varchar(50) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo','pendiente') DEFAULT 'activo',
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.socios: ~10 rows (aproximadamente)
DELETE FROM `socios`;
INSERT INTO `socios` (`id`, `nombre`, `dni`, `tipo_doc`, `direccion_fiscal`, `email`, `telefono`, `whatsapp_api_key`, `fecha_registro`, `estado`, `foto`) VALUES
	(1, 'Carlos Mendoza Rojas', '45123456', '1', NULL, 'carlos.mendoza@mail.com', '987654321', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(2, 'Lucía Fernández Torres', '45234567', '1', NULL, 'lucia.fernandez@mail.com', '987111222', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(3, 'Jorge Silva Quispe', '45345678', '1', NULL, 'jorge.silva@mail.com', '987222333', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(4, 'María Castillo Ramos', '45456789', '1', NULL, 'maria.castillo@mail.com', '987333444', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(5, 'Diego Vargas Huamán', '45567890', '1', NULL, 'diego.vargas@mail.com', '987444555', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(6, 'Andrea Paredes Soto', '45678901', '1', NULL, 'andrea.paredes@mail.com', '987555666', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(7, 'Fernando Ruiz Cárdenas', '45789012', '1', NULL, 'fernando.ruiz@mail.com', '987666777', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(8, 'Patricia Aguilar Núñez', '45890123', '1', NULL, 'patricia.aguilar@mail.com', '987777888', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(9, 'Roberto Salazar Vega', '45901234', '1', NULL, 'roberto.salazar@mail.com', '987888999', NULL, '2026-05-21 00:44:22', 'activo', NULL),
	(10, 'Camila Reyes Apaza', '46012345', '1', NULL, 'camila.reyes@mail.com', '987999000', NULL, '2026-05-21 00:44:22', 'activo', NULL);

-- Volcando estructura para tabla ec_gym_system.sri_log
CREATE TABLE IF NOT EXISTS `sri_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comprobante_id` int DEFAULT NULL,
  `accion` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'validarComprobante, autorizacionComprobante, etc.',
  `request_xml` mediumtext COLLATE utf8mb4_unicode_ci,
  `response_xml` mediumtext COLLATE utf8mb4_unicode_ci,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comp_log` (`comprobante_id`),
  KEY `idx_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ec_gym_system.sri_log: ~0 rows (aproximadamente)
DELETE FROM `sri_log`;

-- Volcando estructura para tabla ec_gym_system.sri_series
CREATE TABLE IF NOT EXISTS `sri_series` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_doc` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '01=Factura, 04=Nota de Credito',
  `serie` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Establecimiento + Punto Emision (ej. 001001)',
  `correlativo` int NOT NULL DEFAULT '0',
  `descripcion` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_tipo_serie` (`tipo_doc`,`serie`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ec_gym_system.sri_series: ~2 rows (aproximadamente)
DELETE FROM `sri_series`;
INSERT INTO `sri_series` (`id`, `tipo_doc`, `serie`, `correlativo`, `descripcion`, `estado`) VALUES
	(1, '01', '001001', 0, 'Facturas electrónicas', 'activo'),
	(2, '04', '001001', 0, 'Notas de crédito electrónicas', 'activo');

-- Volcando estructura para tabla ec_gym_system.suscripciones
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int DEFAULT NULL,
  `plan_id` int DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','vencida') DEFAULT 'activa',
  `tipo_comprobante` enum('boleta','factura','ninguno') DEFAULT 'boleta',
  `comprobante_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.suscripciones: ~10 rows (aproximadamente)
DELETE FROM `suscripciones`;
INSERT INTO `suscripciones` (`id`, `socio_id`, `plan_id`, `fecha_inicio`, `fecha_fin`, `estado`, `tipo_comprobante`, `comprobante_id`) VALUES
	(1, 1, 2, '2025-11-28', '2025-12-28', 'vencida', 'boleta', NULL),
	(2, 2, 1, '2026-03-21', '2026-04-20', 'vencida', 'boleta', NULL),
	(3, 3, 4, '2026-01-06', '2026-04-06', 'vencida', 'boleta', NULL),
	(4, 4, 2, '2026-01-12', '2026-02-11', 'vencida', 'boleta', NULL),
	(5, 5, 1, '2026-04-23', '2026-05-23', 'activa', 'boleta', NULL),
	(6, 6, 2, '2025-12-13', '2026-01-12', 'vencida', 'boleta', NULL),
	(7, 7, 5, '2025-12-05', '2026-06-03', 'activa', 'boleta', NULL),
	(8, 8, 8, '2026-04-23', '2026-04-30', 'vencida', 'boleta', NULL),
	(9, 9, 4, '2026-05-02', '2026-07-31', 'activa', 'boleta', NULL),
	(10, 10, 10, '2026-03-18', '2026-04-17', 'vencida', 'boleta', NULL);

-- Volcando estructura para tabla ec_gym_system.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('admin','recepcionista','entrenador') DEFAULT 'recepcionista',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.usuarios: ~0 rows (aproximadamente)
DELETE FROM `usuarios`;
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `estado`) VALUES
	(1, 'Administrador', 'admin@gym.com', '$2y$10$eeUFYFldSH9vWP3kElE3buIPXvXdEfIryMpYh9J47a6mxsju0q1GC', 'admin', 'activo');

-- Volcando estructura para tabla ec_gym_system.ventas
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `caja_id` int NOT NULL,
  `socio_id` int DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` enum('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
  `tipo_comprobante` enum('boleta','factura','ninguno') DEFAULT 'boleta',
  `cliente_tipo_doc` varchar(1) DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `cliente_num_doc` varchar(20) DEFAULT NULL,
  `cliente_razon` varchar(255) DEFAULT NULL,
  `cliente_direccion` varchar(255) DEFAULT NULL,
  `comprobante_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caja_id` (`caja_id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla ec_gym_system.ventas: ~25 rows (aproximadamente)
DELETE FROM `ventas`;
INSERT INTO `ventas` (`id`, `caja_id`, `socio_id`, `total`, `descuento`, `metodo_pago`, `tipo_comprobante`, `cliente_tipo_doc`, `fecha`, `cliente_num_doc`, `cliente_razon`, `cliente_direccion`, `comprobante_id`) VALUES
	(1, 6, 3, 455.00, 0.00, 'efectivo', 'boleta', NULL, '2026-04-25 14:42:43', NULL, NULL, NULL, NULL),
	(2, 5, 10, 200.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-05-03 10:32:31', NULL, NULL, NULL, NULL),
	(3, 4, 4, 190.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-04-26 16:09:18', NULL, NULL, NULL, NULL),
	(4, 6, 7, 260.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-05-16 14:00:41', NULL, NULL, NULL, NULL),
	(5, 2, 8, 275.00, 0.00, 'tarjeta', 'boleta', NULL, '2025-12-30 19:07:59', NULL, NULL, NULL, NULL),
	(6, 4, 6, 235.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-04-09 07:14:04', NULL, NULL, NULL, NULL),
	(7, 2, 8, 60.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-04-08 09:15:48', NULL, NULL, NULL, NULL),
	(8, 7, 8, 285.00, 0.00, 'transferencia', 'boleta', NULL, '2026-02-16 10:25:59', NULL, NULL, NULL, NULL),
	(9, 3, 3, 410.00, 0.00, 'efectivo', 'boleta', NULL, '2026-05-15 11:43:51', NULL, NULL, NULL, NULL),
	(10, 5, 2, 95.00, 0.00, 'efectivo', 'boleta', NULL, '2026-04-12 15:03:14', NULL, NULL, NULL, NULL),
	(11, 6, 1, 305.00, 0.00, 'efectivo', 'boleta', NULL, '2026-03-04 18:35:43', NULL, NULL, NULL, NULL),
	(12, 5, 6, 220.00, 0.00, 'transferencia', 'boleta', NULL, '2026-02-23 19:52:45', NULL, NULL, NULL, NULL),
	(13, 1, 8, 610.00, 0.00, 'transferencia', 'boleta', NULL, '2026-04-15 13:14:59', NULL, NULL, NULL, NULL),
	(14, 1, 10, 430.00, 0.00, 'transferencia', 'boleta', NULL, '2025-12-17 11:16:59', NULL, NULL, NULL, NULL),
	(15, 5, 7, 375.00, 0.00, 'efectivo', 'boleta', NULL, '2026-01-30 19:38:19', NULL, NULL, NULL, NULL),
	(16, 7, 10, 110.00, 0.00, 'efectivo', 'boleta', NULL, '2025-12-10 09:12:55', NULL, NULL, NULL, NULL),
	(17, 1, 9, 764.00, 0.00, 'transferencia', 'boleta', NULL, '2026-03-12 12:15:47', NULL, NULL, NULL, NULL),
	(18, 6, 3, 440.00, 0.00, 'transferencia', 'boleta', NULL, '2026-02-04 22:48:47', NULL, NULL, NULL, NULL),
	(19, 2, 9, 85.00, 0.00, 'transferencia', 'boleta', NULL, '2025-11-21 19:09:10', NULL, NULL, NULL, NULL),
	(20, 1, 4, 140.00, 0.00, 'tarjeta', 'boleta', NULL, '2025-12-02 11:40:01', NULL, NULL, NULL, NULL),
	(21, 6, 5, 90.00, 0.00, 'transferencia', 'boleta', NULL, '2026-05-11 07:43:06', NULL, NULL, NULL, NULL),
	(22, 4, 7, 195.00, 0.00, 'transferencia', 'boleta', NULL, '2025-12-11 20:37:21', NULL, NULL, NULL, NULL),
	(23, 7, 3, 540.00, 0.00, 'transferencia', 'boleta', NULL, '2025-11-24 22:43:20', NULL, NULL, NULL, NULL),
	(24, 7, 2, 340.00, 0.00, 'tarjeta', 'boleta', NULL, '2026-04-04 19:20:47', NULL, NULL, NULL, NULL),
	(25, 1, 4, 120.00, 0.00, 'transferencia', 'boleta', NULL, '2025-12-24 19:28:36', NULL, NULL, NULL, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
