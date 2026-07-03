-- ============================================================
-- Migración: Facturación Electrónica SRI (Ecuador)
-- Sistema: ec_gym-system
-- ============================================================
-- Ejecutar este script sobre la base de datos `ec_gym_system`.
-- ============================================================

USE `ec_gym_system`;

-- 1. Ampliar tabla `configuracion` con datos fiscales SRI Ecuador
ALTER TABLE `configuracion`
    ADD COLUMN `sri_ambiente`          ENUM('1','2') DEFAULT '1' COMMENT '1=Pruebas, 2=Produccion',
    ADD COLUMN `sri_establecimiento`   VARCHAR(3) DEFAULT '001',
    ADD COLUMN `sri_punto_emision`     VARCHAR(3) DEFAULT '001',
    ADD COLUMN `sri_certificado_p12`   VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `sri_certificado_clave` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `iva_tasa`              DECIMAL(5,2) DEFAULT 15.00,
    ADD COLUMN `incluye_iva`           TINYINT(1) DEFAULT 1 COMMENT '1 = los precios mostrados YA incluyen IVA';

-- Actualizar valores por defecto para Ecuador
UPDATE `configuracion` SET
    `moneda` = '$',
    `iva_tasa` = 15.00,
    `sri_ambiente` = '1',
    `sri_establecimiento` = '001',
    `sri_punto_emision` = '001'
WHERE `id` = 1;

-- 2. Crear tabla de series SRI
DROP TABLE IF EXISTS `sri_series`;
CREATE TABLE `sri_series` (
    `id`           INT NOT NULL AUTO_INCREMENT,
    `tipo_doc`     VARCHAR(2)  NOT NULL COMMENT '01=Factura, 04=Nota de Credito',
    `serie`        VARCHAR(6)  NOT NULL COMMENT 'Establecimiento + Punto Emision (ej. 001001)',
    `correlativo`  INT NOT NULL DEFAULT 0,
    `descripcion`  VARCHAR(120) DEFAULT NULL,
    `estado`       ENUM('activo','inactivo') DEFAULT 'activo',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unq_tipo_serie` (`tipo_doc`,`serie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sri_series` (`tipo_doc`,`serie`,`correlativo`,`descripcion`) VALUES
    ('01','001001',0,'Facturas electrónicas'),
    ('04','001001',0,'Notas de crédito electrónicas');

-- 3. Crear tabla de Log SRI
DROP TABLE IF EXISTS `sri_log`;
CREATE TABLE `sri_log` (
    `id`              INT NOT NULL AUTO_INCREMENT,
    `comprobante_id`  INT DEFAULT NULL,
    `accion`          VARCHAR(40) NOT NULL COMMENT 'validarComprobante, autorizacionComprobante, etc.',
    `request_xml`     MEDIUMTEXT DEFAULT NULL,
    `response_xml`    MEDIUMTEXT DEFAULT NULL,
    `codigo`          VARCHAR(10) DEFAULT NULL,
    `mensaje`         TEXT DEFAULT NULL,
    `creado_en`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_comp_log` (`comprobante_id`),
    KEY `idx_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Modificar comprobantes para SRI
-- En Ecuador, usamos clave de acceso de 49 caracteres y estado SRI
ALTER TABLE `comprobantes_electronicos`
    ADD COLUMN `clave_acceso` VARCHAR(49) DEFAULT NULL AFTER `correlativo`,
    ADD COLUMN `sri_authorization_xml` MEDIUMTEXT DEFAULT NULL AFTER `xml_firmado`,
    ADD COLUMN `estado_sri` ENUM('pendiente','recibida','devuelta','autorizado','no_autorizado','anulado','error') DEFAULT 'pendiente' AFTER `cdr_descripcion`;

-- Para los registros existentes, migrar de estado_sunat a estado_sri si hay datos
UPDATE `comprobantes_electronicos` SET
    `estado_sri` = CASE 
        WHEN `estado_sunat` = 'pendiente' THEN 'pendiente'
        WHEN `estado_sunat` = 'aceptado' THEN 'autorizado'
        WHEN `estado_sunat` = 'rechazado' THEN 'no_autorizado'
        WHEN `estado_sunat` = 'anulado' THEN 'anulado'
        ELSE 'error'
    END;

-- Eliminar tablas SUNAT anteriores que ya no se usan
DROP TABLE IF EXISTS `sunat_resumenes_detalle`;
DROP TABLE IF EXISTS `sunat_resumenes`;
DROP TABLE IF EXISTS `sunat_log`;
DROP TABLE IF EXISTS `sunat_series`;
