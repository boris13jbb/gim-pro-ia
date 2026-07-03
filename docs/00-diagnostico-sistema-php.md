# Diagnóstico del Sistema PHP — Gym System

**Fecha:** 2026-07-02  
**Fase:** 00 — Diagnóstico completo  
**Repositorio analizado:** `gym-system/`  
**Base de datos:** `ec_gym_system` (MySQL 8.x)  
**Estado:** Documentación completada — pendiente aprobación para Fase 01

---

## 1. Resumen ejecutivo

El sistema actual es una aplicación **PHP MVC monolítica** para administración de gimnasio. Usa enrutamiento manual vía `public/index.php`, sesiones PHP para autenticación y carrito POS, PDO para acceso a MySQL, y vistas PHP con Bootstrap 5.

Incluye módulos de socios, membresías (suscripciones), asistencias, inventario, POS, caja, gastos, reportes, facturación electrónica SRI (Ecuador), notificaciones WhatsApp, progreso físico, rutinas, carnets QR y mantenimiento de BD.

**No se modificó ningún archivo PHP durante este diagnóstico.**

---

## 2. Stack tecnológico actual

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP (sin framework) |
| Patrón | MVC manual |
| Base de datos | MySQL (`ec_gym_system`) |
| Acceso a datos | PDO |
| Autenticación | `$_SESSION` PHP |
| Frontend | PHP views + Bootstrap 5 + Font Awesome + Chart.js |
| PDF | FPDF (tickets, carnets, reportes) |
| QR | phpqrcode + extensión FPDF QR |
| Facturación | Librería propia SRI (`app/lib/sri/`) |
| Dependencias Composer | `dompdf/dompdf` ^3.1 |
| Servidor web | Apache + mod_rewrite (`.htaccess`) |
| Zona horaria | `America/Guayaquil` |

---

## 3. Estructura del proyecto

```
gym-system/
├── public/                    # Document root
│   ├── index.php              # Front controller + session_start
│   ├── .htaccess              # Rewrite a index.php?url=
│   ├── css/, img/, manual.html
│   └── seed_data.php, migrate_sri.php
├── app/
│   ├── config/
│   │   ├── Database.php       # Conexión PDO (credenciales hardcodeadas)
│   │   ├── migrate.php
│   │   └── migration_sri.sql
│   ├── controllers/           # 20 controladores
│   ├── models/                # 15 modelos
│   ├── views/                 # 35 vistas PHP
│   └── lib/
│       ├── Auth.php           # Helper roles/sesión
│       ├── WhatsAppNotifier.php
│       ├── sri/               # Facturación electrónica Ecuador
│       ├── fpdf/              # Generación PDF
│       └── phpqrcode/
├── vendor/                    # Composer (dompdf)
├── bk_basededatos.sql         # Backup estructura + datos demo
└── composer.json
```

---

## 4. Enrutamiento

### 4.1 Mecanismo

`public/index.php` parsea `$_GET['url']` con formato `{controlador}/{metodo}/{params...}`:

- Controlador: primer segmento → `{Ucfirst}Controller.php`
- Método: segundo segmento (default `index`)
- Parámetros: resto como argumentos de `call_user_func_array`

Si el controlador no existe → redirección a `/auth/index`.

### 4.2 Apache

`public/.htaccess` reescribe todas las URLs inexistentes a `index.php?url=$1`.

---

## 5. Inventario de controladores (20)

| Controlador | Métodos públicos | Rol requerido |
|---|---|---|
| `AuthController` | index, login, logout | Público / sesión |
| `HomeController` | index, filtrar | Login |
| `SociosController` | index, crear, guardar, editar, actualizar, cambiarEstado | Login |
| `SuscripcionesController` | index, crear, guardar, cancelar, exportarExcel | admin, recepcionista |
| `PlanesController` | index, crear, guardar, editar, actualizar, cambiarEstado | admin |
| `AsistenciaController` | index, validar, registrar, reporte, exportarPDF | Login |
| `CarnetController` | generar | Login |
| `ProgresoController` | ver, guardar_medida, guardar_rutina, eliminar_medida | Login |
| `InventarioController` | categorias, guardarCategoria, cambiarEstadoCategoria, productos, guardarProducto, cambiarEstadoProducto, ajusteStock | admin |
| `PosController` | index, agregarAlCarrito, eliminarDelCarrito, vaciarCarrito, actualizarCantidad, procesarVenta, historial, detalleVenta | admin, recepcionista (no entrenador) |
| `CajaController` | index, abrir, cerrar | admin, recepcionista |
| `GastosController` | index, crear, guardar, anular | admin |
| `ReportesController` | index, exportarExcel, exportarPDF | admin |
| `UsuariosController` | index, crear, guardar, editar, actualizar, cambiarEstado | admin |
| `ConfiguracionController` | index, actualizar, sri, actualizarSri, guardarSerie | Login / admin según vista |
| `FacturacionElectronicaController` | index, emitirSuscripcion, emitirVenta, notaCredito, ver, descargarXml, pdf, reintentar, logs, enviarCliente | admin, recepcionista |
| `ComprobanteController` | generar, cpe | Login |
| `TicketController` | ver, generar | Login |
| `NotificacionesController` | index, enviarAlerta, enviarTodos, guardarApiKey | admin, recepcionista |
| `MantenimientoController` | index, backup, restaurar, limpiar | admin |

---

## 6. Inventario de modelos (15)

| Modelo | Tabla(s) principal(es) | Responsabilidad |
|---|---|---|
| `Usuario` | usuarios | Auth, CRUD usuarios, hash bcrypt |
| `Socio` | socios | CRUD socios, vencimientos, WhatsApp key |
| `Plan` | planes | CRUD planes |
| `Suscripcion` | suscripciones | Membresías, cancelación |
| `Asistencia` | asistencias | Registro e informes |
| `Progreso` | medidas, rutinas | Medidas corporales y rutinas |
| `Categoria` | categorias | Categorías inventario |
| `Producto` | productos | Productos y stock |
| `Venta` | ventas, detalle_ventas | Ventas transaccionales POS |
| `Caja` | cajas | Apertura/cierre de caja |
| `Gasto` | gastos | Gastos operativos |
| `Reporte` | múltiples | Agregaciones financieras |
| `Dashboard` | múltiples | KPIs home |
| `Configuracion` | configuracion | Datos empresa, SRI, IVA |
| `ComprobanteElectronico` | comprobantes_electronicos, comprobantes_detalle, sri_* | Facturación SRI |

---

## 7. Inventario de vistas (35)

| Módulo | Vistas |
|---|---|
| auth | login.php |
| home | index.php |
| socios | index, crear, editar |
| suscripciones | index, crear |
| planes | index, crear, editar |
| asistencia | index, reporte |
| progreso | ver |
| inventario | categorias, productos |
| pos | index, historial, ticket |
| caja | apertura, cierre |
| gastos | index, crear |
| reportes | index |
| usuarios | index, crear, editar |
| configuracion | index, sri |
| facturacion | index, ver, logs |
| notificaciones | index |
| mantenimiento | index |
| inc | navbar, footer |

---

## 8. Uso de superglobales PHP

### 8.1 `$_SESSION`

| Clave | Uso |
|---|---|
| `user_id`, `user_name`, `user_rol` | Autenticación post-login |
| `acceso_denegado` | Flash mensaje permisos |
| `pos_cart` | Carrito POS en memoria de sesión |
| `error_message` | Mensajes de error (caja, POS) |

**Flujos dependientes de sesión:** login, todo el menú lateral, carrito POS, protección por rol.

### 8.2 `$_POST`

Formularios de login, CRUD socios/usuarios/planes, suscripciones, asistencia (validar/registrar), progreso, inventario, caja, gastos, POS checkout, configuración, SRI, notificaciones, mantenimiento, facturación (nota crédito), AJAX dashboard (`home/filtrar`).

### 8.3 `$_GET`

Filtros en historial ventas, reportes, asistencias, facturación electrónica, notificaciones (días), exportaciones PDF/Excel.

### 8.4 `$_FILES`

Fotos de socios, productos, logo empresa, certificado SRI `.p12`, restauración backup SQL.

---

## 9. Reglas de negocio detectadas

### 9.1 Autenticación y roles

- Roles en BD: `admin`, `recepcionista`, `entrenador`
- Contraseñas con `password_hash` / `password_verify`
- Usuario inactivo no puede iniciar sesión
- Si no hay usuarios, se crea admin por defecto (`admin@irongym.com` / `123456`)
- `Auth::requerirRol()` centraliza control; muchos controladores validan manualmente
- Entrenador: acceso limitado (sin caja, POS, suscripciones, facturación)

### 9.2 Socios y membresías

- Socio con DNI único, estados: `activo`, `inactivo`, `pendiente`
- Suscripción: `fecha_fin = fecha_inicio + plan.duracion_dias`
- Estados suscripción en BD: solo `activa` y `vencida`
- Cancelar suscripción → cambia estado a `vencida` (no `cancelada`)
- No hay validación de membresía duplicada activa al crear

### 9.3 Asistencias

- Flujo en 2 pasos: `validar` (consulta) → `registrar` (guarda)
- Validación de acceso: socio existe + suscripción `activa` con `fecha_fin >= hoy`
- **No valida** estado del socio (`inactivo`) en validación
- **Registrar no revalida** membresía en backend (solo recibe `socio_id` por POST)
- QR del carnet codifica el **DNI** del socio (no token opaco)
- Tabla `asistencias` solo guarda `socio_id` y `fecha_hora` (sin método ni usuario)

### 9.4 POS, ventas y caja

- Requiere caja abierta del usuario para vender
- Carrito en `$_SESSION['pos_cart']`
- Stock validado en UI/AJAX antes de agregar; venta usa transacción PDO
- Venta atómica: cabecera + detalle + descuento stock + suma a `cajas.total_ventas`
- Métodos de pago: efectivo, tarjeta, transferencia
- Descuento aplicado en controlador: `total = max(0, bruto - descuento)`
- **Bug crítico:** `Caja::obtenerTotalesSesion()` suma precios de **suscripciones**, no ventas POS ni tabla `ventas`
- Cierre de caja calcula diferencia: `monto_fisico - saldo_esperado`

### 9.5 Inventario

- Productos con categoría, código, precios, stock, foto, estado
- Ajuste manual suma/resta stock **sin validar stock negativo** en `Producto::actualizarStock()`
- No existe tabla de movimientos de inventario

### 9.6 Progreso y rutinas

- Medidas: peso, grasa, cintura, brazo por fecha (historial)
- Rutinas: INSERT por cada guardado; la vista muestra la última (`ORDER BY id DESC LIMIT 1`)
- Historial de rutinas existe en BD pero no hay UI de versiones

### 9.7 Facturación SRI

- Módulo en `app/lib/sri/`: XmlBuilder, XmlSigner, SriClient, FacturadorSri
- Tablas: `comprobantes_electronicos`, `comprobantes_detalle`, `sri_log`, `sri_series`
- Orígenes: suscripción, venta, manual
- IVA configurable (`iva_tasa`, `incluye_iva`)
- Mezcla terminología SUNAT (Perú) en columnas legacy (`estado_sunat`, ubigeo LIMA) con SRI Ecuador

### 9.8 Reportes

- Ingresos = suscripciones + ventas POS − gastos
- Exportación Excel (HTML table) y PDF (FPDF)

### 9.9 Notificaciones

- Alertas WhatsApp a socios con membresía por vencer
- API Key configurable global y por socio (`whatsapp_api_key` en tabla socios — uso confuso)

---

## 10. Errores e inconsistencias detectados (documentar antes de corregir)

| # | Severidad | Descripción | Ubicación |
|---|---|---|---|
| E01 | **Crítica** | Cierre de caja calcula ventas sumando suscripciones, no ventas POS | `app/models/Caja.php` → `obtenerTotalesSesion()` |
| E02 | Alta | `registrar` asistencia no valida membresía vigente ni socio activo | `AsistenciaController::registrar()` |
| E03 | Alta | Ajuste/resta de stock puede dejar valores negativos | `Producto::actualizarStock()` |
| E04 | Alta | Credenciales BD hardcodeadas (`root`, password vacío) | `app/config/Database.php` |
| E05 | Alta | Admin por defecto con contraseña débil `123456` | `Usuario::crearAdmin()` |
| E06 | Media | Mezcla configuración Perú (SUNAT/ubigeo/PEN) y Ecuador (SRI/IVA) | `configuracion`, comprobantes |
| E07 | Media | Estados membresía incompletos (`cancelada`, `suspendida` no existen) | `suscripciones.estado` |
| E08 | Media | Sin protección CSRF en formularios POST | Global |
| E09 | Media | Sin tabla `inventario_movimientos` para auditoría | BD |
| E10 | Media | `asistencias` sin campos método de ingreso ni usuario registrador | BD |
| E11 | Baja | Variable `$diasRestantes` puede quedar indefinida si no hay sub activa | `AsistenciaController::validar()` |
| E12 | Baja | No hay rol `socio` en usuarios (requerido para app Flutter futura) | `usuarios.rol` |
| E13 | Baja | Descuento en venta no valida que sea ≤ total bruto | `PosController::procesarVenta()` |

---

## 11. Módulos críticos — no tocar al inicio

| Prioridad | Módulo | Motivo |
|---|---|---|
| 🔴 Máxima | Facturación SRI | Impacto fiscal, certificados, secuenciales |
| 🔴 Máxima | POS + Ventas + Stock | Transacciones financieras e inventario |
| 🔴 Máxima | Caja | Cuadre financiero (tiene bug E01) |
| 🟠 Alta | Suscripciones / membresías | Base de acceso y asistencias |
| 🟠 Alta | Mantenimiento BD | Restaurar/limpiar puede borrar datos |
| 🟡 Media | Reportes | Depende de datos correctos de ventas/caja |
| 🟢 Baja | Progreso, rutinas, notificaciones | Menor impacto operativo inmediato |

---

## 12. Orden recomendado de migración

Ver detalle completo en `docs/04-plan-migracion-fases.md`. Resumen:

1. **Fase 01** — Backend NestJS base (sin tocar PHP)
2. **Fase 02** — Auth JWT + usuarios + roles
3. **Fase 03** — Socios, planes, membresías
4. **Fase 04** — Asistencias + QR
5. **Fase 05** — Progreso + rutinas
6. **Fase 06** — Inventario + productos
7. **Fase 07** — POS + ventas + caja (corregir E01 en NestJS)
8. **Fase 08** — Reportes
9. **Fase 09** — Facturación SRI (último módulo crítico fiscal)
10. **Fase 10** — Flutter app socio
11. **Fase 11** — IA Gemini + WebSockets
12. **Fase 12** — Cierre y comparación PHP vs nuevo stack

---

## 13. Dependencias externas y configuración

| Servicio | Estado actual |
|---|---|
| MySQL local | `localhost`, BD `ec_gym_system` |
| SRI Ecuador | Config en `configuracion` + certificado `.p12` |
| WhatsApp API | `WhatsAppNotifier.php` — requiere API key |
| Gemini / IA | No implementado |
| WebSockets | No implementado |

---

## 14. Archivos PHP clave para referencia en migración

```
public/index.php
app/config/Database.php
app/lib/Auth.php
app/controllers/*.php (20 archivos)
app/models/*.php (15 archivos)
app/lib/sri/*.php (5 archivos)
bk_basededatos.sql
```

---

## 15. Conclusión Fase 00

El sistema PHP es **funcional y amplio**, con lógica de negocio distribuida entre controladores y modelos, fuerte dependencia de sesión PHP, y módulo fiscal SRI parcialmente adaptado desde un origen SUNAT/Perú.

La migración debe preservar el comportamiento actual **corrigiendo los bugs documentados (E01–E13) en NestJS**, no en PHP, salvo que se autorice explícitamente.

**Estado:** ✅ Diagnóstico completado — **esperando aprobación para Fase 01**
