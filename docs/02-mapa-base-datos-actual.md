# Mapa de Base de Datos Actual — ec_gym_system

**Fecha:** 2026-07-02  
**Motor:** MySQL 8.x  
**Charset:** utf8mb4  
**Fuente:** `gym-system/bk_basededatos.sql`, modelos PHP, `migration_sri.sql`

---

## 1. Diagrama de relaciones (resumen)

```
usuarios ──< cajas ──< ventas ──< detalle_ventas >── productos >── categorias
                │
socios ──< suscripciones >── planes
   │
   ├──< asistencias
   ├──< medidas
   ├──< rutinas
   └──< ventas (opcional socio_id)

comprobantes_electronicos ──< comprobantes_detalle
         │
         └── sri_log

configuracion (singleton id=1)
sri_series
gastos (independiente)
```

---

## 2. Tablas (17)

### 2.1 `usuarios`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| nombre | VARCHAR(100) | — |
| email | VARCHAR(100) UNIQUE | Login |
| password | VARCHAR(255) | bcrypt hash |
| rol | ENUM(admin, recepcionista, entrenador) | Control acceso |
| estado | ENUM(activo, inactivo) | Bloquea login si inactivo |

**Módulos:** Auth, Usuarios, Caja (cajero)

---

### 2.2 `socios`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| nombre | VARCHAR(100) | Requerido |
| dni | VARCHAR(20) UNIQUE | Búsqueda asistencia/QR |
| tipo_doc | VARCHAR(1) | 1=DNI, 6=RUC, etc. (fiscal) |
| direccion_fiscal | VARCHAR(255) | Facturación |
| email, telefono | VARCHAR | Contacto |
| whatsapp_api_key | VARCHAR(50) | ⚠️ Uso ambiguo (notificaciones) |
| fecha_registro | DATETIME | Auto |
| estado | ENUM(activo, inactivo, pendiente) | — |
| foto | VARCHAR(255) | Archivo en public/img/socios/ |

**FK referenciada por:** suscripciones, asistencias, medidas, rutinas, ventas

---

### 2.3 `planes`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| nombre | VARCHAR(50) | — |
| precio | DECIMAL(10,2) | Precio membresía |
| duracion_dias | INT | Cálculo fecha_fin |
| descripcion | TEXT | — |
| estado | ENUM(activo, inactivo) | — |

---

### 2.4 `suscripciones` (membresías)

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| socio_id | INT FK → socios | ON DELETE CASCADE |
| plan_id | INT FK → planes | — |
| fecha_inicio, fecha_fin | DATE | Vigencia |
| estado | ENUM(activa, vencida) | ⚠️ Sin cancelada/suspendida |
| tipo_comprobante | ENUM(boleta, factura, ninguno) | — |
| comprobante_id | INT | FK lógica a comprobantes |

**Regla negocio:** Acceso gym si `estado=activa` AND `fecha_fin >= CURDATE()`

---

### 2.5 `asistencias`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| socio_id | INT FK → socios | ON DELETE CASCADE |
| fecha_hora | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| metodo_ingreso | ENUM(manual,dni,qr,app) NULL | Agregado Fase 04 NestJS |

**Gaps para migración:** Falta `usuario_id` (staff que registró), índice anti-duplicado en BD

---

### 2.6 `medidas`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| socio_id | INT FK → socios | CASCADE |
| peso, grasa, cintura, brazo | DECIMAL(5,2) | Métricas corporales |
| fecha | DATE | Historial |

---

### 2.7 `rutinas`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| socio_id | INT FK → socios | CASCADE |
| dia1..dia6 | TEXT | Contenido por día |
| observaciones | TEXT | — |
| fecha_asignacion | DATETIME | Auto |

**Comportamiento:** INSERT por cada guardado; UI muestra última rutina

---

### 2.8 `categorias`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| nombre | VARCHAR(100) | — |
| estado | ENUM(activo, inactivo) | — |

---

### 2.9 `productos`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| categoria_id | INT FK → categorias | — |
| codigo | VARCHAR(50) | SKU |
| nombre | VARCHAR(150) | — |
| precio_compra, precio_venta | DECIMAL(10,2) | — |
| stock | INT DEFAULT 0 | Validado en NestJS (no negativo) |
| foto | VARCHAR(255) | public/img/productos/ |
| estado | ENUM(activo, inactivo) | Soft delete |

---

### 2.9.1 `movimientos_inventario` *(tabla nueva NestJS — Fase 06 ampliada)*

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| producto_id | INT FK → productos | Producto afectado |
| tipo | ENUM(manual_add, manual_subtract, sale, product_create, product_update) | Origen del movimiento |
| cantidad | INT | Siempre positiva |
| stock_anterior, stock_nuevo | INT | Auditoría |
| venta_id | INT FK → ventas NULL | Cuando tipo = sale |
| usuario_id | INT FK → usuarios NULL | Responsable |
| notas | VARCHAR(255) | Observación |
| created_at | DATETIME | Fecha del movimiento |

**Regla:** Toda entrada/salida de stock en NestJS debe registrar fila aquí.

---

### 2.10 `cajas`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| usuario_id | INT FK → usuarios | Cajero responsable |
| monto_inicial | DECIMAL(10,2) | Apertura |
| monto_final | DECIMAL(10,2) | Cierre |
| total_ventas | DECIMAL(10,2) | Acumulado en ventas POS |
| total_gastos | DECIMAL(10,2) | Del cierre |
| diferencia | DECIMAL(10,2) | Físico vs esperado |
| fecha_apertura, fecha_cierre | DATETIME | — |
| estado | ENUM(abierta, cerrada) | Una abierta por usuario |

**⚠️ Bug E01:** Vista de cierre usa suma de suscripciones, no `ventas`

---

### 2.11 `ventas`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| caja_id | INT FK → cajas | Requerido |
| socio_id | INT FK → socios | Opcional |
| total | DECIMAL(10,2) | Post-descuento |
| descuento | DECIMAL(10,2) | DEFAULT 0 |
| metodo_pago | ENUM(efectivo, tarjeta, transferencia) | — |
| tipo_comprobante | ENUM(boleta, factura, ninguno) | — |
| cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion | VARCHAR | Datos receptor fiscal |
| fecha | DATETIME | Auto |
| comprobante_id | INT | FK lógica SRI |

---

### 2.12 `detalle_ventas`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| venta_id | INT FK → ventas | ON DELETE CASCADE |
| producto_id | INT FK → productos | — |
| cantidad | INT | — |
| precio_unitario, subtotal | DECIMAL(10,2) | — |

---

### 2.13 `gastos`

| Campo | Tipo | Reglas |
|---|---|---|
| id | INT PK AI | — |
| descripcion | VARCHAR(255) | — |
| monto | DECIMAL(10,2) | — |
| fecha | DATE | — |
| estado | ENUM(creado, anulado) | Soft anulación |
| motivo_anulacion | VARCHAR(255) | — |

**Nota:** No vinculado a `caja_id` en BD (solo por fecha en cálculo caja)

---

### 2.14 `configuracion` (singleton)

| Campo | Tipo | Uso |
|---|---|---|
| nombre_sistema, ruc, razon_social, direccion, telefono, email, logo | — | Empresa |
| ubigeo, departamento, provincia, distrito | — | ⚠️ Legacy Perú |
| sunat_* | — | ⚠️ Legacy SUNAT (no usado activamente) |
| igv_tasa, incluye_igv | — | Legacy |
| sri_ambiente | ENUM(1,2) | 1=Pruebas, 2=Producción |
| sri_establecimiento, sri_punto_emision | VARCHAR(3) | Secuenciales |
| sri_certificado_p12, sri_certificado_clave | VARCHAR | Certificado digital |
| iva_tasa, incluye_iva | — | IVA Ecuador |

---

### 2.15 `comprobantes_electronicos`

Cabecera fiscal SRI. Campos clave:

- `origen_tipo`: suscripcion | venta | manual
- `origen_id`: ID del origen
- `tipo_doc`: 01 Factura, 03 Boleta, 07 NC, etc.
- `serie`, `correlativo`, `clave_acceso`
- Totales: gravadas, igv, total, descuentos
- XML: `xml_firmado`, `sri_authorization_xml`, `cdr_zip`
- Estados: `estado_sri`, `estado_sunat` (legacy)

**UNIQUE:** (tipo_doc, serie, correlativo)

---

### 2.16 `comprobantes_detalle`

Líneas del comprobante con IVA por línea, tipo afectación Cat.07.

---

### 2.17 `sri_log`

Auditoría de llamadas al SRI: request/response XML, códigos, mensajes.

---

### 2.18 `sri_series`

Control de secuenciales por tipo documento y serie (establecimiento + punto emisión).

---

## 3. Tablas que NO existen (recomendadas para NestJS)

| Tabla propuesta | Propósito |
|---|---|
| `inventario_movimientos` | Auditoría entradas/salidas stock |
| `refresh_tokens` | Auth JWT |
| `audit_logs` | Acciones críticas usuario |
| `usuarios_socios` | Vincular login app socio con tabla socios |
| `ai_chat_history` | Historial IA (Fase 11) |

---

## 4. Índices existentes

- FK indexes en todas las relaciones
- `comprobantes_electronicos`: idx_estado, idx_fecha, idx_origen
- `sri_log`: idx_comp_log, idx_accion
- `sri_series`: unq_tipo_serie
- `socios.dni`: UNIQUE
- `usuarios.email`: UNIQUE

---

## 5. Reglas de integridad actuales

| Regla | ¿En BD? | ¿En PHP? |
|---|---|---|
| Stock no negativo | ❌ | Parcial (solo POS AJAX) |
| Venta requiere caja abierta | ❌ | ✅ PosController |
| Membresía vigente para asistencia | ❌ | Parcial (solo validar, no registrar) |
| DNI único socio | ✅ UNIQUE | ✅ |
| Email único usuario | ✅ UNIQUE | ✅ |
| CASCADE delete socio → asistencias/medidas | ✅ | — |

---

## 6. Estrategia de migración de BD

1. **Fase 01–02:** NestJS conecta a la misma BD `ec_gym_system` sin cambiar esquema
2. **Nuevas tablas:** Solo vía migraciones Prisma documentadas (refresh_tokens, movimientos, etc.)
3. **No renombrar** tablas/columnas existentes hasta Fase 12
4. **Respaldo obligatorio** antes de cualquier ALTER: `mantenimiento/backup` o `mysqldump`
5. **Corrección E01:** En NestJS calcular ventas desde tabla `ventas`, no suscripciones

---

## 7. Variables de conexión actuales (PHP)

```php
// app/config/Database.php — ⚠️ Migrar a .env en NestJS
host: localhost
db_name: ec_gym_system
username: root
password: (vacío)
timezone: America/Guayaquil
```

---

## 8. Resumen de volúmenes demo (backup)

| Tabla | ~Registros demo |
|---|---|
| socios | 10 |
| suscripciones | 10 |
| asistencias | 30 |
| ventas | 25 |
| detalle_ventas | 50 |
| productos | 10 |
| cajas | 7 (1 abierta) |
| comprobantes_electronicos | 10 |
| usuarios | 1 (admin) |
