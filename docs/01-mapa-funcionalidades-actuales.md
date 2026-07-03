# Mapa de Funcionalidades Actuales — Gym System PHP

**Fecha:** 2026-07-02  
**Fase:** 00

---

## 1. Mapa de rutas HTTP

Formato: `GET|POST /{controlador}/{metodo}/{parametros}`

### 1.1 Autenticación

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/auth/index` | GET | AuthController::index | Pantalla login; crea admin si BD vacía | usuarios |
| `/auth/login` | POST | AuthController::login | Valida credenciales, inicia sesión | usuarios |
| `/auth/logout` | GET | AuthController::logout | Destruye sesión | — |

### 1.2 Dashboard

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/home/index` | GET | HomeController::index | KPIs, gráficos, vencimientos, SRI | socios, suscripciones, ventas, gastos, comprobantes |
| `/home/filtrar` | POST | HomeController::filtrar | AJAX filtro vencimientos | suscripciones, socios, planes |

### 1.3 Socios

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/socios/index` | GET | SociosController::index | Listado socios | socios |
| `/socios/crear` | GET | SociosController::crear | Formulario nuevo socio | — |
| `/socios/guardar` | POST | SociosController::guardar | Crea socio + foto | socios |
| `/socios/editar/{id}` | GET | SociosController::editar | Formulario edición | socios |
| `/socios/actualizar` | POST | SociosController::actualizar | Actualiza socio + foto | socios |
| `/socios/cambiarEstado/{id}/{estado}` | GET | SociosController::cambiarEstado | Activa/inactiva socio | socios |

### 1.4 Suscripciones (membresías)

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/suscripciones/index` | GET | SuscripcionesController::index | Listado membresías | suscripciones, socios, planes |
| `/suscripciones/crear` | GET | SuscripcionesController::crear | Formulario nueva membresía | socios, planes |
| `/suscripciones/guardar` | POST | SuscripcionesController::guardar | Crea membresía activa | suscripciones |
| `/suscripciones/cancelar/{id}` | GET | SuscripcionesController::cancelar | Marca como vencida | suscripciones |
| `/suscripciones/exportarExcel` | GET | SuscripcionesController::exportarExcel | Exporta listado | suscripciones |

### 1.5 Planes

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/planes/index` | GET | PlanesController::index | Listado planes | planes |
| `/planes/crear` | GET | PlanesController::crear | Formulario plan | — |
| `/planes/guardar` | POST | PlanesController::guardar | Crea plan | planes |
| `/planes/editar/{id}` | GET | PlanesController::editar | Edita plan | planes |
| `/planes/actualizar` | POST | PlanesController::actualizar | Actualiza plan | planes |
| `/planes/cambiarEstado/{id}/{estado}` | GET | PlanesController::cambiarEstado | Activa/inactiva | planes |

### 1.6 Asistencias

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/asistencia/index` | GET | AsistenciaController::index | Pantalla control + historial hoy | asistencias, socios |
| `/asistencia/validar` | POST | AsistenciaController::validar | Valida DNI y membresía (no guarda) | socios, suscripciones |
| `/asistencia/registrar` | POST | AsistenciaController::registrar | Registra ingreso | asistencias |
| `/asistencia/reporte` | GET | AsistenciaController::reporte | Reporte + ranking | asistencias |
| `/asistencia/exportarPDF` | GET | AsistenciaController::exportarPDF | PDF asistencias | asistencias |

### 1.7 Carnet QR

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/carnet/generar/{id}` | GET | CarnetController::generar | PDF carnet con QR=DNI | socios, configuracion |

### 1.8 Progreso físico y rutinas

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/progreso/ver/{socio_id}` | GET | ProgresoController::ver | Perfil progreso + gráficos | socios, medidas, rutinas |
| `/progreso/guardar_medida` | POST | ProgresoController::guardar_medida | Nueva medida | medidas |
| `/progreso/guardar_rutina` | POST | ProgresoController::guardar_rutina | Nueva rutina (INSERT) | rutinas |
| `/progreso/eliminar_medida/{id}/{socio_id}` | GET | ProgresoController::eliminar_medida | Elimina medida | medidas |

### 1.9 Inventario

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/inventario/categorias` | GET | InventarioController::categorias | Listado categorías | categorias |
| `/inventario/guardarCategoria` | POST | InventarioController::guardarCategoria | Crea/edita categoría | categorias |
| `/inventario/cambiarEstadoCategoria/{id}/{estado}` | GET | InventarioController::cambiarEstadoCategoria | Activa/inactiva | categorias |
| `/inventario/productos` | GET | InventarioController::productos | Listado productos | productos, categorias |
| `/inventario/guardarProducto` | POST | InventarioController::guardarProducto | Crea/edita producto + foto | productos |
| `/inventario/cambiarEstadoProducto/{id}/{estado}` | GET | InventarioController::cambiarEstadoProducto | Activa/inactiva | productos |
| `/inventario/ajusteStock` | POST | InventarioController::ajusteStock | Suma/resta stock manual | productos |

### 1.10 POS y ventas

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/pos/index` | GET | PosController::index | Interfaz POS | productos, categorias, socios, cajas |
| `/pos/agregarAlCarrito` | POST | PosController::agregarAlCarrito | AJAX agregar ítem | $_SESSION pos_cart |
| `/pos/eliminarDelCarrito` | POST | PosController::eliminarDelCarrito | AJAX quitar ítem | $_SESSION |
| `/pos/vaciarCarrito` | POST | PosController::vaciarCarrito | Vacía carrito | $_SESSION |
| `/pos/actualizarCantidad` | POST | PosController::actualizarCantidad | AJAX cambia cantidad | $_SESSION, productos |
| `/pos/procesarVenta` | POST | PosController::procesarVenta | Checkout transaccional | ventas, detalle_ventas, productos, cajas |
| `/pos/historial` | GET | PosController::historial | Historial ventas filtrado | ventas |
| `/pos/detalleVenta/{id}` | GET | PosController::detalleVenta | JSON detalle venta | detalle_ventas |

### 1.11 Tickets

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/ticket/ver/{id}` | GET | TicketController::ver | Vista ticket post-venta | ventas, detalle_ventas |
| `/ticket/generar/{id}` | GET | TicketController::generar | PDF ticket | ventas |

### 1.12 Caja

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/caja/index` | GET | CajaController::index | Apertura o cierre según estado | cajas |
| `/caja/abrir` | POST | CajaController::abrir | Abre caja con monto inicial | cajas |
| `/caja/cerrar` | POST | CajaController::cerrar | Cierra caja con diferencia | cajas |

### 1.13 Gastos

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/gastos/index` | GET | GastosController::index | Listado gastos | gastos |
| `/gastos/crear` | GET | GastosController::crear | Formulario gasto | — |
| `/gastos/guardar` | POST | GastosController::guardar | Registra gasto | gastos |
| `/gastos/anular` | POST | GastosController::anular | Anula gasto | gastos |

### 1.14 Reportes

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/reportes/index` | GET | ReportesController::index | Dashboard financiero | múltiples |
| `/reportes/exportarExcel` | GET | ReportesController::exportarExcel | Excel movimientos | múltiples |
| `/reportes/exportarPDF` | GET | ReportesController::exportarPDF | PDF financiero | múltiples |

### 1.15 Usuarios

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/usuarios/index` | GET | UsuariosController::index | Listado usuarios | usuarios |
| `/usuarios/crear` | GET | UsuariosController::crear | Formulario usuario | — |
| `/usuarios/guardar` | POST | UsuariosController::guardar | Crea usuario | usuarios |
| `/usuarios/editar/{id}` | GET | UsuariosController::editar | Edita usuario | usuarios |
| `/usuarios/actualizar` | POST | UsuariosController::actualizar | Actualiza usuario | usuarios |
| `/usuarios/cambiarEstado/{id}/{estado}` | GET | UsuariosController::cambiarEstado | Activa/inactiva | usuarios |

### 1.16 Configuración

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/configuracion/index` | GET | ConfiguracionController::index | Datos empresa | configuracion |
| `/configuracion/actualizar` | POST | ConfiguracionController::actualizar | Actualiza empresa + logo | configuracion |
| `/configuracion/sri` | GET | ConfiguracionController::sri | Config fiscal SRI | configuracion, sri_series |
| `/configuracion/actualizarSri` | POST | ConfiguracionController::actualizarSri | Guarda SRI + certificado | configuracion |
| `/configuracion/guardarSerie` | POST | ConfiguracionController::guardarSerie | Series documentos | sri_series |

### 1.17 Facturación electrónica SRI

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/facturacionelectronica/index` | GET | FacturacionElectronicaController::index | Bandeja comprobantes | comprobantes_electronicos |
| `/facturacionelectronica/emitirSuscripcion/{id}` | GET | emitirSuscripcion | Factura membresía | comprobantes_* |
| `/facturacionelectronica/emitirVenta/{id}` | GET | emitirVenta | Factura/boleta venta | comprobantes_* |
| `/facturacionelectronica/notaCredito/{id}` | POST | notaCredito | Nota de crédito | comprobantes_* |
| `/facturacionelectronica/ver/{id}` | GET | ver | Detalle comprobante | comprobantes_* |
| `/facturacionelectronica/descargarXml/{id}` | GET | descargarXml | Descarga XML | comprobantes_electronicos |
| `/facturacionelectronica/pdf/{id}` | GET | pdf | RIDE PDF | comprobantes_* |
| `/facturacionelectronica/reintentar/{id}` | GET | reintentar | Reenvío SRI | sri_log |
| `/facturacionelectronica/logs/{id}` | GET | logs | Logs SRI | sri_log |
| `/facturacionelectronica/enviarCliente/{id}` | GET | enviarCliente | Envía al cliente | — |

### 1.18 Comprobantes legacy

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/comprobante/generar/{id}` | GET | ComprobanteController::generar | PDF comprobante suscripción | suscripciones |
| `/comprobante/cpe/{id}` | GET | ComprobanteController::cpe | CPE legacy | suscripciones |

### 1.19 Notificaciones WhatsApp

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/notificaciones/index` | GET | NotificacionesController::index | Socios por vencer | socios, suscripciones |
| `/notificaciones/enviarAlerta/{socio_id}` | GET | enviarAlerta | Alerta individual | — |
| `/notificaciones/enviarTodos` | POST | enviarTodos | Alerta masiva | — |
| `/notificaciones/guardarApiKey` | POST | guardarApiKey | Guarda API key WhatsApp | configuracion |

### 1.20 Mantenimiento

| Ruta | Método | Controlador | Descripción | Tablas |
|---|---|---|---|---|
| `/mantenimiento/index` | GET | MantenimientoController::index | Panel mantenimiento | — |
| `/mantenimiento/backup` | GET | MantenimientoController::backup | Descarga SQL backup | todas |
| `/mantenimiento/restaurar` | POST | MantenimientoController::restaurar | Restaura BD desde SQL | todas |
| `/mantenimiento/limpiar` | POST | MantenimientoController::limpiar | Limpia datos operativos | múltiples |

---

## 2. Menú lateral por rol

| Ítem menú | admin | recepcionista | entrenador |
|---|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ |
| Asistencia | ✅ | ✅ | ✅ |
| Socios | ✅ | ✅ | ✅ |
| Caja | ✅ | ✅ | ❌ |
| Suscripciones | ✅ | ✅ | ❌ |
| POS | ✅ | ✅ | ❌ |
| Notificaciones | ✅ | ✅ | ❌ |
| Historial ventas | ✅ | ✅ | ❌ |
| Facturación electrónica | ✅ | ✅ | ❌ |
| Planes | ✅ | ❌ | ❌ |
| Gastos | ✅ | ❌ | ❌ |
| Inventario / Categorías | ✅ | ❌ | ❌ |
| Reportes | ✅ | ❌ | ❌ |
| Rep. Asistencias | ✅ | ❌ | ❌ |
| Usuarios | ✅ | ❌ | ❌ |
| Mantenimiento | ✅ | ❌ | ❌ |
| Config. SRI | ✅ | ❌ | ❌ |

---

## 3. Inventario de botones y acciones por pantalla

| Pantalla | Botón / Acción | Ruta destino | Tabla(s) afectada(s) | Validaciones clave |
|---|---|---|---|---|
| Login | Iniciar sesión | POST `/auth/login` | usuarios (lectura) | Email, password, estado activo |
| Login | Cerrar sesión | GET `/auth/logout` | — | Sesión activa |
| Socios / listado | Nuevo socio | `/socios/crear` | — | Login |
| Socios / listado | Carnet QR | `/carnet/generar/{id}` | socios (lectura) | Login |
| Socios / listado | Progreso | `/progreso/ver/{id}` | medidas, rutinas (lectura) | Login |
| Socios / listado | Editar | `/socios/editar/{id}` | socios (lectura) | Login |
| Socios / listado | Activar/Inactivar | `/socios/cambiarEstado/{id}/{estado}` | socios | Login |
| Socios / crear | Guardar | POST `/socios/guardar` | socios | DNI único, $_FILES foto |
| Asistencia | Validar DNI | POST `/asistencia/validar` | socios, suscripciones (lectura) | Membresía activa + fecha_fin |
| Asistencia | Registrar ingreso | POST `/asistencia/registrar` | asistencias | socio_id (⚠️ sin revalidación) |
| Suscripciones | Nueva | `/suscripciones/crear` | — | Rol ≠ entrenador |
| Suscripciones | Guardar | POST `/suscripciones/guardar` | suscripciones | Calcula fecha_fin por plan |
| Suscripciones | Cancelar | `/suscripciones/cancelar/{id}` | suscripciones | → estado vencida |
| Suscripciones | Emitir factura SRI | `/facturacionelectronica/emitirSuscripcion/{id}` | comprobantes_* | Rol recepción |
| POS | Agregar producto | POST `/pos/agregarAlCarrito` | $_SESSION | stock > 0, caja abierta |
| POS | Cobrar | POST `/pos/procesarVenta` | ventas, detalle_ventas, productos, cajas | Caja abierta, carrito no vacío |
| POS | Vaciar carrito | POST `/pos/vaciarCarrito` | $_SESSION | — |
| Caja | Abrir caja | POST `/caja/abrir` | cajas | monto_inicial |
| Caja | Cerrar caja | POST `/caja/cerrar` | cajas | Calcula diferencia (⚠️ ventas mal calculadas E01) |
| Inventario / productos | Ajustar stock | POST `/inventario/ajusteStock` | productos | ⚠️ puede quedar negativo |
| Inventario / productos | Desactivar | `/inventario/cambiarEstadoProducto/{id}/inactivo` | productos | admin |
| Reportes | Exportar Excel | GET `/reportes/exportarExcel` | múltiples (lectura) | admin, rango fechas |
| Reportes | Exportar PDF | GET `/reportes/exportarPDF` | múltiples (lectura) | admin |
| Facturación | Reintentar SRI | `/facturacionelectronica/reintentar/{id}` | comprobantes, sri_log | Rol recepción |
| Facturación | Nota de crédito | POST `/facturacionelectronica/notaCredito/{id}` | comprobantes_* | Motivo NC |
| Mantenimiento | Backup | `/mantenimiento/backup` | todas (lectura) | admin |
| Mantenimiento | Restaurar | POST `/mantenimiento/restaurar` | todas | ⚠️ destructivo |
| Mantenimiento | Limpiar datos | POST `/mantenimiento/limpiar` | múltiples | ⚠️ destructivo |

*Listado completo de botones a expandir en `docs/06-checklist-pruebas.md` durante cada fase de migración.*

---

## 4. Flujos críticos end-to-end

### 4.1 Venta POS

```
Abrir caja → POS (verifica caja) → Agregar productos (sesión) → Cobrar (POST)
→ Venta::registrarVenta (transacción) → Ticket → Opcional emitir SRI
```

### 4.2 Control de acceso gym

```
Ingresar DNI → validar (consulta membresía) → Mostrar perfil
→ Operador confirma → registrar (INSERT asistencias)
```

### 4.3 Nueva membresía

```
Crear socio → Crear suscripción (plan + fechas) → Opcional factura SRI
```

### 4.4 Cierre de caja

```
caja/index (si abierta) → muestra totales (⚠️ bug E01)
→ POST cerrar con monto físico → diferencia guardada
```

---

## 5. Funcionalidades no implementadas (gap para migración)

- App móvil socio (login propio, rol `socio`)
- API REST / JSON centralizada
- Refresh token / JWT
- Historial movimientos inventario
- Validación QR por API (solo PDF con DNI)
- Chat IA / Gemini
- WebSockets / notificaciones push
- Recuperación de contraseña
- Auditoría de acciones de usuario

---

## 6. Prioridad de migración por funcionalidad

| Orden | Funcionalidad PHP | Módulo NestJS futuro |
|---|---|---|
| 1 | Auth + usuarios + roles | auth, users, roles |
| 2 | Socios + planes + suscripciones | members, plans, memberships |
| 3 | Asistencias + carnet QR | attendance, qr-access |
| 4 | Progreso + rutinas | body-progress, workout-routines |
| 5 | Categorías + productos + stock | categories, products, inventory |
| 6 | Caja + POS + ventas | cash-register, sales, pos |
| 7 | Gastos | (módulo gastos o cash-register) |
| 8 | Reportes | reports |
| 9 | Configuración + SRI | billing-sri, config |
| 10 | Notificaciones WhatsApp | notifications |
| 11 | Mantenimiento | audit-logs + scripts admin |
