-- CreateTable
CREATE TABLE `asistencias` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `socio_id` INTEGER NOT NULL,
    `fecha_hora` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
    `metodo_ingreso` ENUM('manual', 'dni', 'qr', 'app') NULL,

    INDEX `socio_id`(`socio_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `cajas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `usuario_id` INTEGER NOT NULL,
    `monto_inicial` DECIMAL(10, 2) NOT NULL,
    `monto_final` DECIMAL(10, 2) NULL DEFAULT 0.00,
    `total_ventas` DECIMAL(10, 2) NULL DEFAULT 0.00,
    `total_gastos` DECIMAL(10, 2) NULL DEFAULT 0.00,
    `diferencia` DECIMAL(10, 2) NULL DEFAULT 0.00,
    `fecha_apertura` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
    `fecha_cierre` DATETIME(0) NULL,
    `estado` ENUM('abierta', 'cerrada') NULL DEFAULT 'abierta',

    INDEX `usuario_id`(`usuario_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `categorias` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `estado` ENUM('activo', 'inactivo') NULL DEFAULT 'activo',

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `comprobantes_detalle` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `comprobante_id` INTEGER NOT NULL,
    `linea` INTEGER NOT NULL DEFAULT 1,
    `codigo` VARCHAR(50) NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `unidad` VARCHAR(5) NULL DEFAULT 'NIU',
    `cantidad` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
    `valor_unitario` DECIMAL(12, 4) NOT NULL,
    `precio_unitario` DECIMAL(12, 4) NOT NULL,
    `subtotal` DECIMAL(12, 2) NOT NULL,
    `igv_linea` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `total_linea` DECIMAL(12, 2) NOT NULL,
    `tipo_afectacion` VARCHAR(2) NULL DEFAULT '10',

    INDEX `idx_comp`(`comprobante_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `comprobantes_electronicos` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `origen_tipo` ENUM('suscripcion', 'venta', 'manual') NOT NULL,
    `origen_id` INTEGER NULL,
    `tipo_doc` VARCHAR(2) NOT NULL,
    `serie` VARCHAR(17) NOT NULL,
    `correlativo` INTEGER NOT NULL,
    `clave_acceso` VARCHAR(49) NULL,
    `fecha_emision` DATE NOT NULL,
    `hora_emision` TIME(0) NULL,
    `fecha_vencimiento` DATE NULL,
    `emisor_ruc` VARCHAR(15) NOT NULL,
    `emisor_razon` VARCHAR(255) NOT NULL,
    `cliente_tipo_doc` VARCHAR(2) NOT NULL,
    `cliente_num_doc` VARCHAR(20) NULL,
    `cliente_razon` VARCHAR(255) NOT NULL,
    `cliente_direccion` VARCHAR(255) NULL,
    `cliente_email` VARCHAR(120) NULL,
    `moneda` VARCHAR(3) NULL DEFAULT 'PEN',
    `gravadas` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `inafectas` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `exoneradas` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `gratuitas` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `descuentos` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `igv` DECIMAL(12, 2) NULL DEFAULT 0.00,
    `total` DECIMAL(12, 2) NOT NULL,
    `total_letras` VARCHAR(255) NULL,
    `forma_pago` ENUM('Contado', 'Credito') NULL DEFAULT 'Contado',
    `metodo_pago` VARCHAR(40) NULL DEFAULT 'efectivo',
    `ref_tipo_doc` VARCHAR(2) NULL,
    `ref_serie` VARCHAR(4) NULL,
    `ref_correlativo` INTEGER NULL,
    `motivo_codigo` VARCHAR(2) NULL,
    `motivo_descripcion` VARCHAR(250) NULL,
    `xml_firmado` MEDIUMTEXT NULL,
    `sri_authorization_xml` MEDIUMTEXT NULL,
    `xml_hash` VARCHAR(64) NULL,
    `cdr_zip` MEDIUMBLOB NULL,
    `cdr_codigo` VARCHAR(10) NULL,
    `cdr_descripcion` VARCHAR(255) NULL,
    `estado_sri` ENUM('pendiente', 'recibida', 'devuelta', 'autorizado', 'no_autorizado', 'anulado', 'error') NULL DEFAULT 'pendiente',
    `estado_sunat` ENUM('pendiente', 'aceptado', 'rechazado', 'anulado', 'observado', 'enviando', 'error') NULL DEFAULT 'pendiente',
    `mensaje_error` TEXT NULL,
    `usuario_id` INTEGER NULL,
    `creado_en` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
    `actualizado_en` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `idx_estado`(`estado_sunat`),
    INDEX `idx_fecha`(`fecha_emision`),
    INDEX `idx_origen`(`origen_tipo`, `origen_id`),
    UNIQUE INDEX `unq_comprobante`(`tipo_doc`, `serie`, `correlativo`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `configuracion` (
    `id` INTEGER NOT NULL,
    `nombre_sistema` VARCHAR(100) NULL,
    `ruc` VARCHAR(20) NULL,
    `razon_social` VARCHAR(255) NULL,
    `nombre_comercial` VARCHAR(255) NULL,
    `direccion` VARCHAR(255) NULL,
    `ubigeo` VARCHAR(6) NULL DEFAULT '150101',
    `departamento` VARCHAR(60) NULL DEFAULT 'LIMA',
    `provincia` VARCHAR(60) NULL DEFAULT 'LIMA',
    `distrito` VARCHAR(60) NULL DEFAULT 'LIMA',
    `urbanizacion` VARCHAR(120) NULL,
    `codigo_pais` VARCHAR(2) NULL DEFAULT 'PE',
    `telefono` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `logo` VARCHAR(255) NULL,
    `moneda` VARCHAR(10) NULL DEFAULT '$',
    `sunat_ambiente` ENUM('beta', 'produccion') NULL DEFAULT 'beta',
    `sunat_usuario_sol` VARCHAR(50) NULL,
    `sunat_clave_sol` VARCHAR(255) NULL,
    `sunat_certificado` VARCHAR(255) NULL,
    `sunat_cert_clave` VARCHAR(255) NULL,
    `igv_tasa` DECIMAL(5, 2) NULL DEFAULT 18.00,
    `incluye_igv` BOOLEAN NULL DEFAULT true,
    `sri_ambiente` ENUM('1', '2') NULL DEFAULT 1,
    `sri_establecimiento` VARCHAR(3) NULL DEFAULT '001',
    `sri_punto_emision` VARCHAR(3) NULL DEFAULT '001',
    `sri_certificado_p12` VARCHAR(255) NULL,
    `sri_certificado_clave` VARCHAR(255) NULL,
    `iva_tasa` DECIMAL(5, 2) NULL DEFAULT 15.00,
    `incluye_iva` BOOLEAN NULL DEFAULT true,

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `detalle_ventas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `venta_id` INTEGER NOT NULL,
    `producto_id` INTEGER NOT NULL,
    `cantidad` INTEGER NOT NULL,
    `precio_unitario` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,

    INDEX `producto_id`(`producto_id`),
    INDEX `venta_id`(`venta_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `gastos` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `descripcion` VARCHAR(255) NOT NULL,
    `monto` DECIMAL(10, 2) NOT NULL,
    `fecha` DATE NOT NULL,
    `estado` ENUM('creado', 'anulado') NULL DEFAULT 'creado',
    `motivo_anulacion` VARCHAR(255) NULL,

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `medidas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `socio_id` INTEGER NOT NULL,
    `peso` DECIMAL(5, 2) NULL,
    `grasa` DECIMAL(5, 2) NULL,
    `cintura` DECIMAL(5, 2) NULL,
    `brazo` DECIMAL(5, 2) NULL,
    `fecha` DATE NULL,

    INDEX `socio_id`(`socio_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `planes` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `precio` DECIMAL(10, 2) NOT NULL,
    `duracion_dias` INTEGER NOT NULL,
    `descripcion` TEXT NULL,
    `estado` ENUM('activo', 'inactivo') NULL DEFAULT 'activo',

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `productos` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `categoria_id` INTEGER NOT NULL,
    `codigo` VARCHAR(50) NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `precio_compra` DECIMAL(10, 2) NOT NULL,
    `precio_venta` DECIMAL(10, 2) NOT NULL,
    `stock` INTEGER NULL DEFAULT 0,
    `foto` VARCHAR(255) NULL,
    `estado` ENUM('activo', 'inactivo') NULL DEFAULT 'activo',

    INDEX `categoria_id`(`categoria_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `movimientos_inventario` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `producto_id` INTEGER NOT NULL,
    `tipo` ENUM('manual_add', 'manual_subtract', 'sale', 'product_create', 'product_update') NOT NULL,
    `cantidad` INTEGER NOT NULL,
    `stock_anterior` INTEGER NOT NULL,
    `stock_nuevo` INTEGER NOT NULL,
    `venta_id` INTEGER NULL,
    `usuario_id` INTEGER NULL,
    `notas` VARCHAR(255) NULL,
    `created_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `idx_mov_inv_producto`(`producto_id`),
    INDEX `idx_mov_inv_venta`(`venta_id`),
    INDEX `idx_mov_inv_usuario`(`usuario_id`),
    INDEX `idx_mov_inv_created`(`created_at`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `rutinas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `socio_id` INTEGER NOT NULL,
    `dia1` TEXT NULL,
    `dia2` TEXT NULL,
    `dia3` TEXT NULL,
    `dia4` TEXT NULL,
    `dia5` TEXT NULL,
    `dia6` TEXT NULL,
    `observaciones` TEXT NULL,
    `fecha_asignacion` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `socio_id`(`socio_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `socios` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `dni` VARCHAR(20) NOT NULL,
    `tipo_doc` VARCHAR(1) NULL DEFAULT '1',
    `direccion_fiscal` VARCHAR(255) NULL,
    `email` VARCHAR(100) NULL,
    `telefono` VARCHAR(20) NULL,
    `whatsapp_api_key` VARCHAR(50) NULL,
    `fecha_registro` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
    `estado` ENUM('activo', 'inactivo', 'pendiente') NULL DEFAULT 'activo',
    `foto` VARCHAR(255) NULL,
    `password` VARCHAR(255) NULL,

    UNIQUE INDEX `dni`(`dni`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `sri_log` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `comprobante_id` INTEGER NULL,
    `accion` VARCHAR(40) NOT NULL,
    `request_xml` MEDIUMTEXT NULL,
    `response_xml` MEDIUMTEXT NULL,
    `codigo` VARCHAR(10) NULL,
    `mensaje` TEXT NULL,
    `creado_en` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `idx_accion`(`accion`),
    INDEX `idx_comp_log`(`comprobante_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `sri_series` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tipo_doc` VARCHAR(2) NOT NULL,
    `serie` VARCHAR(6) NOT NULL,
    `correlativo` INTEGER NOT NULL DEFAULT 0,
    `descripcion` VARCHAR(120) NULL,
    `estado` ENUM('activo', 'inactivo') NULL DEFAULT 'activo',

    UNIQUE INDEX `unq_tipo_serie`(`tipo_doc`, `serie`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `suscripciones` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `socio_id` INTEGER NULL,
    `plan_id` INTEGER NULL,
    `fecha_inicio` DATE NULL,
    `fecha_fin` DATE NULL,
    `estado` ENUM('activa', 'vencida') NULL DEFAULT 'activa',
    `tipo_comprobante` ENUM('boleta', 'factura', 'ninguno') NULL DEFAULT 'boleta',
    `comprobante_id` INTEGER NULL,

    INDEX `plan_id`(`plan_id`),
    INDEX `socio_id`(`socio_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `usuarios` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NULL,
    `email` VARCHAR(100) NULL,
    `password` VARCHAR(255) NULL,
    `rol` ENUM('admin', 'recepcionista', 'entrenador') NULL DEFAULT 'recepcionista',
    `estado` ENUM('activo', 'inactivo') NULL DEFAULT 'activo',

    UNIQUE INDEX `email`(`email`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `auth_refresh_tokens` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `userId` INTEGER NULL,
    `memberId` INTEGER NULL,
    `tokenHash` VARCHAR(255) NOT NULL,
    `jti` VARCHAR(64) NOT NULL,
    `expiresAt` DATETIME(0) NOT NULL,
    `revokedAt` DATETIME(0) NULL,
    `replacedById` INTEGER NULL,
    `createdAt` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
    `userAgent` VARCHAR(255) NULL,
    `ip` VARCHAR(64) NULL,

    UNIQUE INDEX `jti`(`jti`),
    INDEX `idx_auth_refresh_tokens_userId`(`userId`),
    INDEX `idx_auth_refresh_tokens_memberId`(`memberId`),
    INDEX `idx_auth_refresh_tokens_expiresAt`(`expiresAt`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `ventas` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `caja_id` INTEGER NOT NULL,
    `socio_id` INTEGER NULL,
    `total` DECIMAL(10, 2) NOT NULL,
    `descuento` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `metodo_pago` ENUM('efectivo', 'tarjeta', 'transferencia') NULL DEFAULT 'efectivo',
    `tipo_comprobante` ENUM('boleta', 'factura', 'ninguno') NULL DEFAULT 'boleta',
    `cliente_tipo_doc` VARCHAR(1) NULL,
    `fecha` DATETIME(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
    `cliente_num_doc` VARCHAR(20) NULL,
    `cliente_razon` VARCHAR(255) NULL,
    `cliente_direccion` VARCHAR(255) NULL,
    `comprobante_id` INTEGER NULL,

    INDEX `caja_id`(`caja_id`),
    INDEX `socio_id`(`socio_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `ai_conversations` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `member_id` INTEGER NOT NULL,
    `titulo` VARCHAR(120) NULL,
    `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    `creado_en` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
    `actualizado_en` DATETIME(0) NOT NULL,

    INDEX `idx_ai_conv_member`(`member_id`),
    INDEX `idx_ai_conv_member_status`(`member_id`, `status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `ai_messages` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `conversation_id` INTEGER NOT NULL,
    `role` ENUM('user', 'assistant', 'system') NOT NULL,
    `content` TEXT NOT NULL,
    `metadata` JSON NULL,
    `creado_en` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `idx_ai_msg_conv`(`conversation_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `notifications` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `member_id` INTEGER NOT NULL,
    `type` VARCHAR(60) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `body` VARCHAR(500) NOT NULL,
    `data` JSON NULL,
    `read_at` DATETIME(0) NULL,
    `created_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
    `updated_at` DATETIME(0) NOT NULL,

    INDEX `idx_notifications_member_created`(`member_id`, `created_at`),
    INDEX `idx_notifications_member_read`(`member_id`, `read_at`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `asistencias` ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `cajas` ADD CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `comprobantes_detalle` ADD CONSTRAINT `fk_comp_detalle` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes_electronicos`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `detalle_ventas` ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `detalle_ventas` ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `medidas` ADD CONSTRAINT `medidas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `productos` ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `movimientos_inventario` ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `movimientos_inventario` ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `movimientos_inventario` ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `rutinas` ADD CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `suscripciones` ADD CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `suscripciones` ADD CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `auth_refresh_tokens` ADD CONSTRAINT `auth_refresh_tokens_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `auth_refresh_tokens` ADD CONSTRAINT `auth_refresh_tokens_member_fk` FOREIGN KEY (`memberId`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `auth_refresh_tokens` ADD CONSTRAINT `auth_refresh_tokens_replacedById_fkey` FOREIGN KEY (`replacedById`) REFERENCES `auth_refresh_tokens`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `ventas` ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `cajas`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `ventas` ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `socios`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `ai_conversations` ADD CONSTRAINT `fk_ai_conv_socio` FOREIGN KEY (`member_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `ai_messages` ADD CONSTRAINT `fk_ai_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- AddForeignKey
ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_member` FOREIGN KEY (`member_id`) REFERENCES `socios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
