-- SAAS-03 — Multi-tenant DB foundation.
--
-- Shared database + row-level tenant isolation foundation. Applied in stages so it is safe
-- on a database that already holds the legacy single-gym data:
--   1. tenants / tenant_memberships
--   2. tenant_id NULLABLE on business tables
--   3. initial tenant (only when legacy data exists; id resolved by slug, never assumed)
--   4. backfill (roots -> initial tenant, children -> inherit from parent) + memberships
--   5. fail-fast validation guards (NULLs, cross-tenant parents, duplicates for new uniques)
--   6. tenant_id NOT NULL + indexes / composite uniques
--   7. foreign keys (direct to tenants + composite (parent_id, tenant_id) to parents)
--   8. triggers that resolve tenant_id for the current single-gym code (until SAAS-04/05)
--   9. tenant_id immutability (BEFORE UPDATE on every tenant-scoped table)
--
-- MySQL/MariaDB DDL is NOT transactional: if a statement fails, earlier statements stay
-- applied and Prisma marks the migration as failed. Recovery = restore the pre-migration
-- backup (docs/SAAS-03-PRODUCTION-MIGRATION-RUNBOOK.md). Never edit this file once applied.
--
-- Out of scope on purpose: configuracion.sri_ambiente default representation (SAAS-02 S02-D4).

-- ---------------------------------------------------------------------------------------
-- 1. Tenant tables
-- ---------------------------------------------------------------------------------------
CREATE TABLE `tenants` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `public_id` CHAR(26) NOT NULL,
    `slug` VARCHAR(63) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `status` ENUM('active', 'suspended', 'cancelled') NOT NULL DEFAULT 'active',
    `plan_code` VARCHAR(40) NULL,
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'America/Guayaquil',
    `country` CHAR(2) NOT NULL DEFAULT 'EC',
    `metadata` JSON NULL,
    `created_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
    `updated_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),

    UNIQUE INDEX `uq_tenants_public_id`(`public_id`),
    UNIQUE INDEX `uq_tenants_slug`(`slug`),
    INDEX `idx_tenants_status`(`status`),
    PRIMARY KEY (`id`),
    -- public_id: ULID (Crockford base32, 26 chars, first char <= 7). (?-i) = case-sensitive.
    CONSTRAINT `chk_tenants_public_id` CHECK (`public_id` REGEXP '(?-i)^[0-7][0-9A-HJKMNP-TV-Z]{25}$'),
    -- slug: lowercase kebab-case, used only to resolve the tenant (never as authorization).
    CONSTRAINT `chk_tenants_slug` CHECK (`slug` REGEXP '(?-i)^[a-z0-9]+(-[a-z0-9]+)*$')
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `tenant_memberships` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `tenant_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `role` ENUM('owner', 'admin', 'reception', 'trainer') NOT NULL,
    `status` ENUM('active', 'invited', 'suspended', 'removed') NOT NULL DEFAULT 'active',
    `created_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
    `updated_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),

    INDEX `idx_tenant_memberships_user_status`(`user_id`, `status`),
    UNIQUE INDEX `uq_tenant_memberships_tenant_user`(`tenant_id`, `user_id`),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_tenant_memberships_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_tenant_memberships_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Temporary guard: every validation inserts its violation count; CHECK aborts the
-- migration on the first non-zero count. Dropped at the end of a successful run.
CREATE TABLE `_saas03_guard` (
    `check_name` VARCHAR(80) NOT NULL,
    `violations` INTEGER NOT NULL,
    CONSTRAINT `chk_saas03_guard_zero` CHECK (`violations` = 0)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------------------
-- 2. tenant_id NULLABLE (safe on populated tables)
-- ---------------------------------------------------------------------------------------
ALTER TABLE `socios` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `planes` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `categorias` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `cajas` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `gastos` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `configuracion` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `sri_series` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `comprobantes_electronicos` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `comprobantes_detalle` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `sri_log` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `suscripciones` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `asistencias` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `medidas` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `rutinas` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `notifications` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `ai_conversations` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `ai_messages` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `productos` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `movimientos_inventario` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `ventas` ADD COLUMN `tenant_id` INTEGER NULL;
ALTER TABLE `detalle_ventas` ADD COLUMN `tenant_id` INTEGER NULL;

-- ---------------------------------------------------------------------------------------
-- 3. Initial tenant = the existing single gym. Created ONLY if legacy data exists (a fresh
--    database stays with zero tenants). Name comes from configuracion when available.
--    public_id is a ULID: 10 chars of millisecond timestamp + 16 random chars.
-- ---------------------------------------------------------------------------------------
INSERT INTO `tenants` (`public_id`, `slug`, `name`, `status`, `timezone`, `country`, `created_at`, `updated_at`)
SELECT
    CONCAT(
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 9)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 8)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 7)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 6)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 5)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 4)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 3)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / POW(32, 2)) % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(t.ms / 32) % 32 + 1, 1),
        SUBSTRING(a.alphabet, t.ms % 32 + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1),
        SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1), SUBSTRING(a.alphabet, FLOOR(RAND() * 32) + 1, 1)
    ),
    'iron-gym',
    LEFT(COALESCE(
        (SELECT COALESCE(NULLIF(TRIM(c.`nombre_comercial`), ''), NULLIF(TRIM(c.`nombre_sistema`), ''))
         FROM `configuracion` c ORDER BY c.`id` LIMIT 1),
        'Iron Gym'
    ), 150),
    'active',
    'America/Guayaquil',
    'EC',
    NOW(),
    NOW()
FROM (SELECT '0123456789ABCDEFGHJKMNPQRSTVWXYZ' AS alphabet) a
CROSS JOIN (SELECT CAST(FLOOR(UNIX_TIMESTAMP(NOW(3)) * 1000) AS UNSIGNED) AS ms) t
WHERE NOT EXISTS (SELECT 1 FROM `tenants` WHERE `slug` = 'iron-gym')
  AND (
       EXISTS (SELECT 1 FROM `usuarios`) OR EXISTS (SELECT 1 FROM `configuracion`)
    OR EXISTS (SELECT 1 FROM `socios`) OR EXISTS (SELECT 1 FROM `planes`)
    OR EXISTS (SELECT 1 FROM `categorias`) OR EXISTS (SELECT 1 FROM `cajas`)
    OR EXISTS (SELECT 1 FROM `gastos`) OR EXISTS (SELECT 1 FROM `sri_series`)
    OR EXISTS (SELECT 1 FROM `comprobantes_electronicos`) OR EXISTS (SELECT 1 FROM `sri_log`)
    OR EXISTS (SELECT 1 FROM `suscripciones`) OR EXISTS (SELECT 1 FROM `productos`)
  );

SET @seed_tenant_id = (SELECT `id` FROM `tenants` WHERE `slug` = 'iron-gym');

-- ---------------------------------------------------------------------------------------
-- 4. Backfill. Idempotent: only rows WHERE tenant_id IS NULL are touched.
--    Roots receive the initial tenant; children inherit the tenant of their parent.
-- ---------------------------------------------------------------------------------------
UPDATE `socios` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `planes` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `categorias` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `cajas` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `gastos` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `configuracion` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `sri_series` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;
UPDATE `comprobantes_electronicos` SET `tenant_id` = @seed_tenant_id WHERE `tenant_id` IS NULL;

UPDATE `suscripciones` x LEFT JOIN `socios` s ON s.`id` = x.`socio_id` LEFT JOIN `planes` p ON p.`id` = x.`plan_id`
SET x.`tenant_id` = COALESCE(s.`tenant_id`, p.`tenant_id`, @seed_tenant_id) WHERE x.`tenant_id` IS NULL;
UPDATE `sri_log` x LEFT JOIN `comprobantes_electronicos` c ON c.`id` = x.`comprobante_id`
SET x.`tenant_id` = COALESCE(c.`tenant_id`, @seed_tenant_id) WHERE x.`tenant_id` IS NULL;

UPDATE `asistencias` x JOIN `socios` p ON p.`id` = x.`socio_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `medidas` x JOIN `socios` p ON p.`id` = x.`socio_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `rutinas` x JOIN `socios` p ON p.`id` = x.`socio_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `notifications` x JOIN `socios` p ON p.`id` = x.`member_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `ai_conversations` x JOIN `socios` p ON p.`id` = x.`member_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `ai_messages` x JOIN `ai_conversations` p ON p.`id` = x.`conversation_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `productos` x JOIN `categorias` p ON p.`id` = x.`categoria_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `movimientos_inventario` x JOIN `productos` p ON p.`id` = x.`producto_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `ventas` x JOIN `cajas` p ON p.`id` = x.`caja_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `detalle_ventas` x JOIN `ventas` p ON p.`id` = x.`venta_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;
UPDATE `comprobantes_detalle` x JOIN `comprobantes_electronicos` p ON p.`id` = x.`comprobante_id` SET x.`tenant_id` = p.`tenant_id` WHERE x.`tenant_id` IS NULL;

-- Every existing staff user gets a membership in the initial tenant. Legacy roles map 1:1;
-- `rol` NULL maps to reception (the column default). No `owner` is inferred (SAAS-04).
INSERT INTO `tenant_memberships` (`tenant_id`, `user_id`, `role`, `status`, `created_at`, `updated_at`)
SELECT @seed_tenant_id, u.`id`,
       CASE u.`rol` WHEN 'admin' THEN 'admin' WHEN 'entrenador' THEN 'trainer' ELSE 'reception' END,
       CASE u.`estado` WHEN 'inactivo' THEN 'suspended' ELSE 'active' END,
       NOW(), NOW()
FROM `usuarios` u
WHERE @seed_tenant_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `tenant_memberships` m WHERE m.`tenant_id` = @seed_tenant_id AND m.`user_id` = u.`id`);

-- ---------------------------------------------------------------------------------------
-- 5. Validation guards (fail-fast). Nothing is deleted or reassigned to "fix" data.
-- ---------------------------------------------------------------------------------------
INSERT INTO `_saas03_guard` SELECT 'socios.tenant_id NULL', COUNT(*) FROM `socios` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'planes.tenant_id NULL', COUNT(*) FROM `planes` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'categorias.tenant_id NULL', COUNT(*) FROM `categorias` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'cajas.tenant_id NULL', COUNT(*) FROM `cajas` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'gastos.tenant_id NULL', COUNT(*) FROM `gastos` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'configuracion.tenant_id NULL', COUNT(*) FROM `configuracion` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'sri_series.tenant_id NULL', COUNT(*) FROM `sri_series` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'comprobantes_electronicos.tenant_id NULL', COUNT(*) FROM `comprobantes_electronicos` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'comprobantes_detalle.tenant_id NULL', COUNT(*) FROM `comprobantes_detalle` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'sri_log.tenant_id NULL', COUNT(*) FROM `sri_log` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'suscripciones.tenant_id NULL', COUNT(*) FROM `suscripciones` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'asistencias.tenant_id NULL', COUNT(*) FROM `asistencias` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'medidas.tenant_id NULL', COUNT(*) FROM `medidas` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'rutinas.tenant_id NULL', COUNT(*) FROM `rutinas` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'notifications.tenant_id NULL', COUNT(*) FROM `notifications` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'ai_conversations.tenant_id NULL', COUNT(*) FROM `ai_conversations` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'ai_messages.tenant_id NULL', COUNT(*) FROM `ai_messages` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'productos.tenant_id NULL', COUNT(*) FROM `productos` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'movimientos_inventario.tenant_id NULL', COUNT(*) FROM `movimientos_inventario` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'ventas.tenant_id NULL', COUNT(*) FROM `ventas` WHERE `tenant_id` IS NULL;
INSERT INTO `_saas03_guard` SELECT 'detalle_ventas.tenant_id NULL', COUNT(*) FROM `detalle_ventas` WHERE `tenant_id` IS NULL;

-- Optional parents (no composite FK possible in Prisma): child tenant must match parent tenant.
INSERT INTO `_saas03_guard` SELECT 'suscripciones.socio cross-tenant', COUNT(*) FROM `suscripciones` x JOIN `socios` p ON p.`id` = x.`socio_id` WHERE p.`tenant_id` <> x.`tenant_id`;
INSERT INTO `_saas03_guard` SELECT 'suscripciones.plan cross-tenant', COUNT(*) FROM `suscripciones` x JOIN `planes` p ON p.`id` = x.`plan_id` WHERE p.`tenant_id` <> x.`tenant_id`;
INSERT INTO `_saas03_guard` SELECT 'ventas.socio cross-tenant', COUNT(*) FROM `ventas` x JOIN `socios` p ON p.`id` = x.`socio_id` WHERE p.`tenant_id` <> x.`tenant_id`;
INSERT INTO `_saas03_guard` SELECT 'movimientos_inventario.venta cross-tenant', COUNT(*) FROM `movimientos_inventario` x JOIN `ventas` p ON p.`id` = x.`venta_id` WHERE p.`tenant_id` <> x.`tenant_id`;
INSERT INTO `_saas03_guard` SELECT 'sri_log.comprobante cross-tenant', COUNT(*) FROM `sri_log` x JOIN `comprobantes_electronicos` p ON p.`id` = x.`comprobante_id` WHERE p.`tenant_id` <> x.`tenant_id`;
INSERT INTO `_saas03_guard` SELECT 'detalle_ventas.producto cross-tenant', COUNT(*) FROM `detalle_ventas` x JOIN `productos` p ON p.`id` = x.`producto_id` WHERE p.`tenant_id` <> x.`tenant_id`;

-- Duplicates that would break the new per-tenant uniques.
INSERT INTO `_saas03_guard` SELECT 'socios duplicate (tenant_id, dni)', COUNT(*) FROM (SELECT 1 FROM `socios` GROUP BY `tenant_id`, `dni` HAVING COUNT(*) > 1) d;
INSERT INTO `_saas03_guard` SELECT 'comprobantes duplicate (tenant_id, tipo_doc, serie, correlativo)', COUNT(*) FROM (SELECT 1 FROM `comprobantes_electronicos` GROUP BY `tenant_id`, `tipo_doc`, `serie`, `correlativo` HAVING COUNT(*) > 1) d;
INSERT INTO `_saas03_guard` SELECT 'sri_series duplicate (tenant_id, tipo_doc, serie)', COUNT(*) FROM (SELECT 1 FROM `sri_series` GROUP BY `tenant_id`, `tipo_doc`, `serie` HAVING COUNT(*) > 1) d;
INSERT INTO `_saas03_guard` SELECT 'configuracion more than one row per tenant', COUNT(*) FROM (SELECT 1 FROM `configuracion` GROUP BY `tenant_id` HAVING COUNT(*) > 1) d;
INSERT INTO `_saas03_guard` SELECT 'usuarios without membership', COUNT(*) FROM `usuarios` u WHERE @seed_tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `tenant_memberships` m WHERE m.`user_id` = u.`id`);

-- ---------------------------------------------------------------------------------------
-- 6. Drop single-column FKs that become composite, then NOT NULL + indexes (one rebuild
--    per table). Parents first, so (id, tenant_id) exists before children reference it.
-- ---------------------------------------------------------------------------------------
ALTER TABLE `ai_conversations` DROP FOREIGN KEY `fk_ai_conv_socio`;
ALTER TABLE `ai_messages` DROP FOREIGN KEY `fk_ai_msg_conv`;
ALTER TABLE `asistencias` DROP FOREIGN KEY `asistencias_ibfk_1`;
ALTER TABLE `comprobantes_detalle` DROP FOREIGN KEY `fk_comp_detalle`;
ALTER TABLE `detalle_ventas` DROP FOREIGN KEY `detalle_ventas_ibfk_1`;
ALTER TABLE `detalle_ventas` DROP FOREIGN KEY `detalle_ventas_ibfk_2`;
ALTER TABLE `medidas` DROP FOREIGN KEY `medidas_ibfk_1`;
ALTER TABLE `movimientos_inventario` DROP FOREIGN KEY `movimientos_inventario_ibfk_1`;
ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_member`;
ALTER TABLE `productos` DROP FOREIGN KEY `productos_ibfk_1`;
ALTER TABLE `rutinas` DROP FOREIGN KEY `rutinas_ibfk_1`;
ALTER TABLE `ventas` DROP FOREIGN KEY `ventas_ibfk_1`;

ALTER TABLE `socios`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `dni`,
    ADD UNIQUE INDEX `uq_socios_tenant_dni`(`tenant_id`, `dni`),
    ADD UNIQUE INDEX `uq_socios_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `planes`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_planes_tenant`(`tenant_id`);
ALTER TABLE `categorias`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_categorias_tenant`(`tenant_id`),
    ADD UNIQUE INDEX `uq_categorias_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `cajas`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_cajas_tenant_estado`(`tenant_id`, `estado`),
    ADD UNIQUE INDEX `uq_cajas_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `gastos`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_gastos_tenant_fecha`(`tenant_id`, `fecha`);
ALTER TABLE `configuracion`
    MODIFY `id` INTEGER NOT NULL AUTO_INCREMENT,
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD UNIQUE INDEX `uq_configuracion_tenant`(`tenant_id`);
ALTER TABLE `sri_series`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `unq_tipo_serie`,
    ADD UNIQUE INDEX `unq_tipo_serie`(`tenant_id`, `tipo_doc`, `serie`);
ALTER TABLE `comprobantes_electronicos`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `unq_comprobante`,
    ADD UNIQUE INDEX `unq_comprobante`(`tenant_id`, `tipo_doc`, `serie`, `correlativo`),
    ADD UNIQUE INDEX `uq_comprobantes_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `comprobantes_detalle`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `idx_comp`,
    ADD INDEX `idx_comp`(`comprobante_id`, `tenant_id`),
    ADD INDEX `idx_comprobantes_detalle_tenant`(`tenant_id`);
ALTER TABLE `sri_log`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_sri_log_tenant`(`tenant_id`);
ALTER TABLE `suscripciones`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_suscripciones_tenant_fin`(`tenant_id`, `fecha_fin`);
ALTER TABLE `asistencias`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `socio_id`,
    ADD INDEX `socio_id`(`socio_id`, `tenant_id`),
    ADD INDEX `idx_asistencias_tenant_fecha`(`tenant_id`, `fecha_hora`);
ALTER TABLE `medidas`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `socio_id`,
    ADD INDEX `socio_id`(`socio_id`, `tenant_id`),
    ADD INDEX `idx_medidas_tenant`(`tenant_id`);
ALTER TABLE `rutinas`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `socio_id`,
    ADD INDEX `socio_id`(`socio_id`, `tenant_id`),
    ADD INDEX `idx_rutinas_tenant`(`tenant_id`);
ALTER TABLE `notifications`
    MODIFY `tenant_id` INTEGER NOT NULL,
    ADD INDEX `idx_notifications_member_tenant`(`member_id`, `tenant_id`),
    ADD INDEX `idx_notifications_tenant`(`tenant_id`);
ALTER TABLE `ai_conversations`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `idx_ai_conv_member`,
    ADD INDEX `idx_ai_conv_member`(`member_id`, `tenant_id`),
    ADD INDEX `idx_ai_conversations_tenant`(`tenant_id`),
    ADD UNIQUE INDEX `uq_ai_conversations_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `ai_messages`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `idx_ai_msg_conv`,
    ADD INDEX `idx_ai_msg_conv`(`conversation_id`, `tenant_id`),
    ADD INDEX `idx_ai_messages_tenant`(`tenant_id`);
ALTER TABLE `productos`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `categoria_id`,
    ADD INDEX `categoria_id`(`categoria_id`, `tenant_id`),
    ADD INDEX `idx_productos_tenant`(`tenant_id`),
    ADD UNIQUE INDEX `uq_productos_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `movimientos_inventario`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `idx_mov_inv_producto`,
    ADD INDEX `idx_mov_inv_producto`(`producto_id`, `tenant_id`),
    ADD INDEX `idx_mov_inv_tenant_created`(`tenant_id`, `created_at`);
ALTER TABLE `ventas`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `caja_id`,
    ADD INDEX `caja_id`(`caja_id`, `tenant_id`),
    ADD INDEX `idx_ventas_tenant_fecha`(`tenant_id`, `fecha`),
    ADD UNIQUE INDEX `uq_ventas_id_tenant`(`id`, `tenant_id`);
ALTER TABLE `detalle_ventas`
    MODIFY `tenant_id` INTEGER NOT NULL,
    DROP INDEX `producto_id`,
    DROP INDEX `venta_id`,
    ADD INDEX `producto_id`(`producto_id`, `tenant_id`),
    ADD INDEX `venta_id`(`venta_id`, `tenant_id`),
    ADD INDEX `idx_detalle_ventas_tenant`(`tenant_id`);

-- ---------------------------------------------------------------------------------------
-- 7. Foreign keys: direct tenant FK on every tenant-scoped table + composite FKs so a child
--    can never point to a parent of another tenant. Tenants are never cascade-deleted.
-- ---------------------------------------------------------------------------------------
ALTER TABLE `socios` ADD CONSTRAINT `fk_socios_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `planes` ADD CONSTRAINT `fk_planes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `categorias` ADD CONSTRAINT `fk_categorias_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `cajas` ADD CONSTRAINT `fk_cajas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `gastos` ADD CONSTRAINT `fk_gastos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `configuracion` ADD CONSTRAINT `fk_configuracion_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `sri_series` ADD CONSTRAINT `fk_sri_series_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `comprobantes_electronicos` ADD CONSTRAINT `fk_comprobantes_electronicos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `sri_log` ADD CONSTRAINT `fk_sri_log_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `suscripciones` ADD CONSTRAINT `fk_suscripciones_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `comprobantes_detalle`
    ADD CONSTRAINT `fk_comprobantes_detalle_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_comp_detalle` FOREIGN KEY (`comprobante_id`, `tenant_id`) REFERENCES `comprobantes_electronicos`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `asistencias`
    ADD CONSTRAINT `fk_asistencias_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`socio_id`, `tenant_id`) REFERENCES `socios`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `medidas`
    ADD CONSTRAINT `fk_medidas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `medidas_ibfk_1` FOREIGN KEY (`socio_id`, `tenant_id`) REFERENCES `socios`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `rutinas`
    ADD CONSTRAINT `fk_rutinas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`socio_id`, `tenant_id`) REFERENCES `socios`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `notifications`
    ADD CONSTRAINT `fk_notifications_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_notifications_member` FOREIGN KEY (`member_id`, `tenant_id`) REFERENCES `socios`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `ai_conversations`
    ADD CONSTRAINT `fk_ai_conversations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_ai_conv_socio` FOREIGN KEY (`member_id`, `tenant_id`) REFERENCES `socios`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `ai_messages`
    ADD CONSTRAINT `fk_ai_messages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_ai_msg_conv` FOREIGN KEY (`conversation_id`, `tenant_id`) REFERENCES `ai_conversations`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `productos`
    ADD CONSTRAINT `fk_productos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`, `tenant_id`) REFERENCES `categorias`(`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `movimientos_inventario`
    ADD CONSTRAINT `fk_movimientos_inventario_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`, `tenant_id`) REFERENCES `productos`(`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ventas`
    ADD CONSTRAINT `fk_ventas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`caja_id`, `tenant_id`) REFERENCES `cajas`(`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `detalle_ventas`
    ADD CONSTRAINT `fk_detalle_ventas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`, `tenant_id`) REFERENCES `ventas`(`id`, `tenant_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`, `tenant_id`) REFERENCES `productos`(`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- ---------------------------------------------------------------------------------------
-- 8. Tenant resolution triggers (compatibility layer until SAAS-04/05 pass tenant_id).
--    "No tenant" = NULL or 0: MariaDB hands an omitted NOT NULL column without default to
--    BEFORE INSERT as its implicit value 0, and tenant ids start at 1.
--    - Children: no tenant -> inherited from the parent row (always correct).
--    - Roots: no tenant -> the ONLY tenant, if exactly one exists ("legacy mode").
--      With 2+ tenants nothing is guessed: the insert fails on NOT NULL / FK (fail-closed).
--    - Optional parents: a parent from another tenant is rejected (SQLSTATE 45000).
--    BEFORE UPDATE immutability for the 4 optional-parent tables is fused into their
--    existing BU triggers below (one UPDATE trigger per table; see section 9).
-- ---------------------------------------------------------------------------------------
CREATE TRIGGER `trg_socios_tenant_bi` BEFORE INSERT ON `socios` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_planes_tenant_bi` BEFORE INSERT ON `planes` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_categorias_tenant_bi` BEFORE INSERT ON `categorias` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_cajas_tenant_bi` BEFORE INSERT ON `cajas` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_gastos_tenant_bi` BEFORE INSERT ON `gastos` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_configuracion_tenant_bi` BEFORE INSERT ON `configuracion` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_sri_series_tenant_bi` BEFORE INSERT ON `sri_series` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_comprobantes_electronicos_tenant_bi` BEFORE INSERT ON `comprobantes_electronicos` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1);
    END IF;
END;

CREATE TRIGGER `trg_comprobantes_detalle_tenant_bi` BEFORE INSERT ON `comprobantes_detalle` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `comprobantes_electronicos` WHERE `id` = NEW.`comprobante_id`);
    END IF;
END;

CREATE TRIGGER `trg_asistencias_tenant_bi` BEFORE INSERT ON `asistencias` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`socio_id`);
    END IF;
END;

CREATE TRIGGER `trg_medidas_tenant_bi` BEFORE INSERT ON `medidas` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`socio_id`);
    END IF;
END;

CREATE TRIGGER `trg_rutinas_tenant_bi` BEFORE INSERT ON `rutinas` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`socio_id`);
    END IF;
END;

CREATE TRIGGER `trg_notifications_tenant_bi` BEFORE INSERT ON `notifications` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`member_id`);
    END IF;
END;

CREATE TRIGGER `trg_ai_conversations_tenant_bi` BEFORE INSERT ON `ai_conversations` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`member_id`);
    END IF;
END;

CREATE TRIGGER `trg_ai_messages_tenant_bi` BEFORE INSERT ON `ai_messages` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `ai_conversations` WHERE `id` = NEW.`conversation_id`);
    END IF;
END;

CREATE TRIGGER `trg_productos_tenant_bi` BEFORE INSERT ON `productos` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `categorias` WHERE `id` = NEW.`categoria_id`);
    END IF;
END;

CREATE TRIGGER `trg_detalle_ventas_tenant_bi` BEFORE INSERT ON `detalle_ventas` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `ventas` WHERE `id` = NEW.`venta_id`);
    END IF;
END;

CREATE TRIGGER `trg_suscripciones_tenant_bi` BEFORE INSERT ON `suscripciones` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = COALESCE(
            (SELECT `tenant_id` FROM `socios` WHERE `id` = NEW.`socio_id`),
            (SELECT `tenant_id` FROM `planes` WHERE `id` = NEW.`plan_id`),
            (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1)
        );
    END IF;
    IF EXISTS (SELECT 1 FROM `socios` WHERE `id` = NEW.`socio_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: suscripciones.socio_id';
    END IF;
    IF EXISTS (SELECT 1 FROM `planes` WHERE `id` = NEW.`plan_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: suscripciones.plan_id';
    END IF;
END;

CREATE TRIGGER `trg_suscripciones_tenant_bu` BEFORE UPDATE ON `suscripciones` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: suscripciones';
    END IF;
    IF EXISTS (SELECT 1 FROM `socios` WHERE `id` = NEW.`socio_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: suscripciones.socio_id';
    END IF;
    IF EXISTS (SELECT 1 FROM `planes` WHERE `id` = NEW.`plan_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: suscripciones.plan_id';
    END IF;
END;

CREATE TRIGGER `trg_ventas_tenant_bi` BEFORE INSERT ON `ventas` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `cajas` WHERE `id` = NEW.`caja_id`);
    END IF;
    IF EXISTS (SELECT 1 FROM `socios` WHERE `id` = NEW.`socio_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: ventas.socio_id';
    END IF;
END;

CREATE TRIGGER `trg_ventas_tenant_bu` BEFORE UPDATE ON `ventas` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: ventas';
    END IF;
    IF EXISTS (SELECT 1 FROM `socios` WHERE `id` = NEW.`socio_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: ventas.socio_id';
    END IF;
END;

CREATE TRIGGER `trg_movimientos_inventario_tenant_bi` BEFORE INSERT ON `movimientos_inventario` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = (SELECT `tenant_id` FROM `productos` WHERE `id` = NEW.`producto_id`);
    END IF;
    IF EXISTS (SELECT 1 FROM `ventas` WHERE `id` = NEW.`venta_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: movimientos_inventario.venta_id';
    END IF;
END;

CREATE TRIGGER `trg_movimientos_inventario_tenant_bu` BEFORE UPDATE ON `movimientos_inventario` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: movimientos_inventario';
    END IF;
    IF EXISTS (SELECT 1 FROM `ventas` WHERE `id` = NEW.`venta_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: movimientos_inventario.venta_id';
    END IF;
END;

CREATE TRIGGER `trg_sri_log_tenant_bi` BEFORE INSERT ON `sri_log` FOR EACH ROW
BEGIN
    IF COALESCE(NEW.`tenant_id`, 0) = 0 THEN
        SET NEW.`tenant_id` = COALESCE(
            (SELECT `tenant_id` FROM `comprobantes_electronicos` WHERE `id` = NEW.`comprobante_id`),
            (SELECT MIN(`id`) FROM `tenants` HAVING COUNT(*) = 1)
        );
    END IF;
    IF EXISTS (SELECT 1 FROM `comprobantes_electronicos` WHERE `id` = NEW.`comprobante_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: sri_log.comprobante_id';
    END IF;
END;

CREATE TRIGGER `trg_sri_log_tenant_bu` BEFORE UPDATE ON `sri_log` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: sri_log';
    END IF;
    IF EXISTS (SELECT 1 FROM `comprobantes_electronicos` WHERE `id` = NEW.`comprobante_id` AND `tenant_id` <> NEW.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cross-tenant reference rejected: sri_log.comprobante_id';
    END IF;
END;

-- ---------------------------------------------------------------------------------------
-- 9. tenant_id is IMMUTABLE once assigned: a row never moves to another tenant. This also
--    protects optional relations against the PARENT moving (e.g. planes -> suscripciones),
--    which the child-side checks above cannot see. The 4 tables that already have a
--    BEFORE UPDATE trigger carry the same check inside it (one UPDATE trigger per table).
-- ---------------------------------------------------------------------------------------
CREATE TRIGGER `trg_socios_tenant_bu` BEFORE UPDATE ON `socios` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: socios';
    END IF;
END;

CREATE TRIGGER `trg_planes_tenant_bu` BEFORE UPDATE ON `planes` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: planes';
    END IF;
END;

CREATE TRIGGER `trg_categorias_tenant_bu` BEFORE UPDATE ON `categorias` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: categorias';
    END IF;
END;

CREATE TRIGGER `trg_cajas_tenant_bu` BEFORE UPDATE ON `cajas` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: cajas';
    END IF;
END;

CREATE TRIGGER `trg_gastos_tenant_bu` BEFORE UPDATE ON `gastos` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: gastos';
    END IF;
END;

CREATE TRIGGER `trg_configuracion_tenant_bu` BEFORE UPDATE ON `configuracion` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: configuracion';
    END IF;
END;

CREATE TRIGGER `trg_sri_series_tenant_bu` BEFORE UPDATE ON `sri_series` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: sri_series';
    END IF;
END;

CREATE TRIGGER `trg_comprobantes_electronicos_tenant_bu` BEFORE UPDATE ON `comprobantes_electronicos` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: comprobantes_electronicos';
    END IF;
END;

CREATE TRIGGER `trg_comprobantes_detalle_tenant_bu` BEFORE UPDATE ON `comprobantes_detalle` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: comprobantes_detalle';
    END IF;
END;

CREATE TRIGGER `trg_asistencias_tenant_bu` BEFORE UPDATE ON `asistencias` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: asistencias';
    END IF;
END;

CREATE TRIGGER `trg_medidas_tenant_bu` BEFORE UPDATE ON `medidas` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: medidas';
    END IF;
END;

CREATE TRIGGER `trg_rutinas_tenant_bu` BEFORE UPDATE ON `rutinas` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: rutinas';
    END IF;
END;

CREATE TRIGGER `trg_notifications_tenant_bu` BEFORE UPDATE ON `notifications` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: notifications';
    END IF;
END;

CREATE TRIGGER `trg_ai_conversations_tenant_bu` BEFORE UPDATE ON `ai_conversations` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: ai_conversations';
    END IF;
END;

CREATE TRIGGER `trg_ai_messages_tenant_bu` BEFORE UPDATE ON `ai_messages` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: ai_messages';
    END IF;
END;

CREATE TRIGGER `trg_productos_tenant_bu` BEFORE UPDATE ON `productos` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: productos';
    END IF;
END;

CREATE TRIGGER `trg_detalle_ventas_tenant_bu` BEFORE UPDATE ON `detalle_ventas` FOR EACH ROW
BEGIN
    IF NOT (NEW.`tenant_id` <=> OLD.`tenant_id`) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'tenant_id is immutable: detalle_ventas';
    END IF;
END;

DROP TABLE `_saas03_guard`;
