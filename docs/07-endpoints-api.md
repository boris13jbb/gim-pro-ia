# Endpoints API — NestJS

Base URL: `http://localhost:3000/api`

Autenticación: `Authorization: Bearer <access_token>` en endpoints protegidos.

---

## POST /auth/login

### Descripción
Inicia sesión staff con email y contraseña. Emite access + refresh token.

### Rol permitido
Público

### Body
```json
{
  "email": "admin@gym.com",
  "password": "123456"
}
```

### Respuesta exitosa (200)
```json
{
  "ok": true,
  "data": {
    "accessToken": "<jwt>",
    "refreshToken": "<jwt>",
    "user": {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@gym.com",
      "rol": "admin",
      "estado": "activo"
    }
  }
}
```

### Errores posibles
- `401` — credenciales incorrectas
- `401` — cuenta inhabilitada
- `429` — rate limit (10 intentos/min)

### Tablas afectadas
- `usuarios` (lectura)
- `auth_refresh_tokens` (insert refresh hasheado)

---

## POST /auth/refresh

### Descripción
Renueva tokens. Rota refresh token (invalida el anterior).

### Rol permitido
Público (requiere refresh token válido en body)

### Body
```json
{
  "refreshToken": "<jwt_refresh>"
}
```

### Respuesta exitosa (200)
Misma estructura que login (nuevo par de tokens).

### Errores posibles
- `401` — refresh inválido, expirado o revocado

---

## POST /auth/logout

### Descripción
Revoca el refresh token activo.

### Body
```json
{
  "refreshToken": "<jwt_refresh>"
}
```

### Respuesta exitosa (200)
```json
{
  "ok": true,
  "data": { "message": "Sesión cerrada correctamente" }
}
```

---

## GET /auth/me

### Descripción
Devuelve perfil del usuario autenticado.

### Rol permitido
Staff autenticado o socio autenticado

### Respuesta exitosa (200) — socio incluye `dni`, `photoUrl` (sin `password`)

### Errores posibles
- `401` — sin token o access expirado

---

## POST /auth/member/login

### Descripción
Login de socio para app móvil (email o DNI + contraseña).

### Rol permitido
Público

### Body
```json
{ "login": "1234567890", "password": "MiClave123" }
```

### Respuesta
JWT con `userType: member`, `role: socio`, `memberId`. Refresh en `auth_refresh_tokens.memberId`.

---

---

## GET /users

### Descripción
Lista usuarios staff del sistema.

### Rol permitido
`admin`

### Respuesta exitosa (200)
```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@gym.com",
      "rol": "admin",
      "estado": "activo"
    }
  ]
}
```

### Errores posibles
- `401` — no autenticado
- `403` — rol sin permiso

---

## POST /users

### Descripción
Crea usuario staff (password hasheado, no devuelto).

### Rol permitido
`admin`

---

## PATCH /users/:id

### Descripción
Actualiza nombre, email o rol.

### Rol permitido
`admin`

---

## PATCH /users/:id/status

### Descripción
Activa/inactiva usuario.

### Rol permitido
`admin`

---

## PATCH /users/:id/password

### Descripción
Cambia contraseña.

### Rol permitido
`admin`

---

## GET /health

### Descripción
Health check API + base de datos.

### Rol permitido
Público

---

## GET /members

### Descripción
Lista socios con paginación, búsqueda y filtro por estado.

### Rol permitido
`admin`, `recepcionista`, `entrenador`

### Query
`page`, `limit`, `search`, `estado`

---

## GET /members/:id/membership

### Descripción
Estado de membresía calculado en backend (`effectiveStatus`, `isMembershipValid`).

### Rol permitido
`admin`, `recepcionista`, `entrenador`

---

## POST /memberships

### Descripción
Crea membresía activa. Calcula `endDate` según `duracion_dias` del plan.

### Rol permitido
`admin`, `recepcionista`

### Body
```json
{
  "memberId": 3,
  "planId": 1,
  "startDate": "2026-07-02"
}
```

---

## PATCH /memberships/:id/cancel

### Descripción
Cancela membresía (marca `vencida`, compatible con PHP legacy).

### Rol permitido
`admin`, `recepcionista`

---

## GET /members/:id/memberships

### Descripción
Historial de membresías de un socio.

### Rol permitido
`admin`, `recepcionista`, `entrenador`

---

## GET /plans

### Descripción
Lista planes de membresía.

### Rol permitido
`admin`, `recepcionista`

---

## POST /members/:id/photo

### Descripción
Sube foto del socio (multipart, campo `photo`). Formatos: JPG, PNG, WEBP.

### Rol permitido
`admin`, `recepcionista`

---

## PATCH /members/:id/password

### Descripción
Establece contraseña de acceso app móvil del socio.

### Rol permitido
`admin`, `recepcionista`

---

## GET /memberships/export/excel

### Descripción
Exporta listado de membresías a Excel (.xlsx). Respuesta binaria (sin wrapper JSON).

### Rol permitido
`admin`, `recepcionista`

---

## POST /attendance/validate

### Descripción
Valida acceso por DNI sin registrar asistencia (paso 1 del flujo PHP).

### Rol permitido
`admin`, `recepcionista`, `entrenador`

### Body
```json
{ "dni": "1234567890" }
```

### Respuesta
`canAccess`, `isMembershipValid`, `effectiveStatus`, `member`, `daysRemaining`

---

## POST /attendance/register

### Descripción
Registra ingreso tras verificación. Revalida membresía vigente (corrige E02).

### Rol permitido
`admin`, `recepcionista`

### Body
```json
{ "memberId": 3, "method": "manual" }
```

### Errores
- `400` — socio inactivo o membresía no vigente
- `409` — ya registró asistencia hoy

---

## POST /attendance/scan

### Descripción
Valida DNI/QR y registra en un solo paso.

### Body
```json
{ "dni": "1234567890", "method": "qr" }
```

---

## POST /attendance/self

### Descripción
Socio registra su propia asistencia desde la app.

### Rol permitido
`socio` (JWT con `memberId`)

---

## GET /attendance/me

### Descripción
Historial de asistencias del socio autenticado en un período (default: mes actual).

### Query
`from`, `to` (YYYY-MM-DD, opcionales)

### Respuesta
`totalVisits`, `averageDaily`, `items[]` con `checkedInAt`, `method`

### Rol permitido
`socio`

---

## GET /attendance/today

### Descripción
Asistencias del día actual.

### Rol permitido
`admin`, `recepcionista`, `entrenador`

---

## GET /attendance/report

### Descripción
Reporte por rango. Query: `from`, `to`, `memberId` (opcional).

---

## GET /attendance/report/ranking

### Descripción
Top 5 socios con más visitas en el período.

---

## GET /attendance/report/export/excel

### Descripción
Exporta reporte de asistencias a Excel (`.xlsx`).

### Query
`from`, `to`, `memberId` (opcional)

### Rol permitido
`admin`, `recepcionista`, `entrenador`

### Respuesta
Archivo binario (`Content-Type: spreadsheetml.sheet`)

---

## GET /attendance/report/export/pdf

### Descripción
Exporta reporte de asistencias a PDF.

### Query
`from`, `to`, `memberId` (opcional)

### Rol permitido
`admin`, `recepcionista`, `entrenador`

### Respuesta
Archivo binario (`Content-Type: application/pdf`)

---

## POST /qr-access/validate

### Descripción
Valida payload QR (DNI del carnet legacy).

### Body
```json
{ "qrPayload": "1234567890" }
```

---

## GET /qr-access/members/:id/card

### Descripción
Datos para carnet digital: socio, `qrPayload` (DNI), estado de acceso.

### Rol permitido
`admin`, `recepcionista`, `entrenador`

---

## GET /qr-access/me/card

### Descripción
Socio autenticado: carnet digital propio con QR (DNI).

### Rol permitido
`socio`

### Respuesta exitosa (200)
Misma estructura que `GET /qr-access/members/:id/card`.

### Errores posibles
- `401` — sin token
- `403` — rol distinto de socio

---

## GET /members/me/membership

### Descripción
Estado de membresía del socio autenticado (`effectiveStatus`, `isMembershipValid`).

### Rol permitido
`socio`

---

## GET /members/me/memberships

### Descripción
Historial de membresías del socio autenticado.

### Rol permitido
`socio`

---

*Ver `docs/fases/fase-04-asistencias-qr.md` para detalle de la fase.*

---

## GET /body-progress/members/:memberId/measurements

### Descripción
Historial de medidas corporales + series para gráficos (`chart.labels`, `chart.weight`, etc.).

### Rol permitido
`admin`, `recepcionista`, `entrenador`, `socio` (solo propio)

---

## POST /body-progress/members/:memberId/measurements

### Body
```json
{
  "measuredAt": "2026-07-02",
  "weight": 78.5,
  "bodyFat": 18.2,
  "waist": 82,
  "arm": 34
}
```

### Rol permitido
`admin`, `entrenador`

---

## DELETE /body-progress/measurements/:id

### Rol permitido
`admin`, `entrenador`

---

## GET /workout-routines/members/:memberId/current

### Descripción
Última rutina asignada al socio.

---

## POST /workout-routines/members/:memberId

### Body
```json
{
  "day1": "Press banca 4x12",
  "day2": "Sentadilla 4x10",
  "notes": "Descanso 90s"
}
```

### Rol permitido
`admin`, `entrenador`

---

## GET /categories

### Descripción
Listar categorías de productos. Filtro opcional `?status=activo|inactivo`.

### Rol permitido
`admin`

---

## GET /categories/active

### Rol permitido
`admin`, `recepcionista`

---

## POST /categories

### Body
```json
{ "name": "Suplementos" }
```

### Rol permitido
`admin`

---

## PATCH /categories/:id/status

### Body
```json
{ "status": "inactivo" }
```

---

## GET /products

### Descripción
Listar productos. Filtros: `?status=`, `?categoryId=`.

### Rol permitido
`admin`

---

## GET /products/active

### Descripción
Productos activos cuya categoría también está activa (preparado para POS Fase 07).

### Rol permitido
`admin`, `recepcionista`

---

## GET /products/low-stock

### Query
`?threshold=5` (opcional, default 5)

### Rol permitido
`admin`

---

## POST /products

### Body
```json
{
  "categoryId": 1,
  "code": "PROT-001",
  "name": "Proteína whey 1kg",
  "purchasePrice": 25.5,
  "salePrice": 39.99,
  "stock": 10
}
```

### Rol permitido
`admin`

---

## PATCH /products/:id/status

### Body
```json
{ "status": "inactivo" }
```

---

## POST /inventory/adjustments

### Descripción
Ajuste manual de stock. **No permite stock negativo** (corrige E03 PHP).

### Body
```json
{
  "productId": 1,
  "quantity": 5,
  "operation": "subtract"
}
```

`operation`: `add` | `subtract`

### Errores
| Código | Caso |
|--------|------|
| 400 | Stock insuficiente al restar |
| 404 | Producto no encontrado |

### Rol permitido
`admin`

---

## GET /inventory/movements

### Descripción
Historial auditado de movimientos de stock.

### Query
`productId`, `saleId`, `type`, `fromDate`, `toDate`, `limit`

### Rol permitido
`admin`

---

## GET /inventory/products/:productId/movements

### Rol permitido
`admin`

---

## GET /cash-registers/current/summary

### Descripción
Resumen de caja abierta: ventas POS, gastos y `expectedAmount`. **Corrige E01** (no usa suscripciones).

### Rol permitido
`admin`, `recepcionista`

---

## GET /cash-registers/history

### Rol permitido
`admin`, `recepcionista`

---

## POST /cash-registers/open

### Body
```json
{ "openingAmount": 50 }
```

---

## POST /cash-registers/close

### Body
```json
{ "closingAmount": 250.5 }
```

---

## GET /cash-registers/current

### Rol permitido
`admin`, `recepcionista`

---

## GET /sales

### Descripción
Historial de ventas POS.

### Query
`fromDate`, `toDate`, `search`, `limit`

### Rol permitido
`admin`, `recepcionista`

---

## GET /sales/:id

### Descripción
Detalle de venta con ítems, cajero y socio.

---

## GET /sales/:id/ticket

### Descripción
Payload de ticket para impresión.

---

## GET /sales/:id/ticket/export/pdf

### Descripción
Descarga ticket de venta en PDF formato térmico 80mm.

### Rol permitido
`admin`, `recepcionista`

### Respuesta
Archivo binario (`Content-Type: application/pdf`)

---

## POST /sales

### Descripción
Venta POS transaccional: cabecera, detalle, descuento de stock (movimiento `sale`) y acumulado en caja.

### Body
```json
{
  "items": [{ "productId": 1, "quantity": 2 }],
  "discount": 0,
  "paymentMethod": "efectivo"
}
```

### Errores
| Código | Caso |
|--------|------|
| 400 | Sin caja abierta |
| 400 | Stock insuficiente |
| 400 | Descuento mayor al total |

### Rol permitido
`admin`, `recepcionista`

---

*Ver `docs/fases/fase-07-pos-ventas-caja.md` para detalle de la fase.*

---

## GET /reports/financial/summary

### Descripción
KPIs financieros del período: ingresos membresías, POS, gastos, utilidad, socios activos y series para gráficos.

### Query
`fromDate`, `toDate` (default: mes actual)

### Rol permitido
`admin`

### Respuesta ejemplo
```json
{
  "ok": true,
  "data": {
    "membershipIncome": 150,
    "posIncome": 89,
    "totalIncome": 239,
    "totalExpenses": 50,
    "netProfit": 189,
    "activeMembers": 42,
    "charts": {
      "incomeByMonth": [{ "month": "2026-07", "total": 239 }],
      "salesByPaymentMethod": [],
      "newMembersByMonth": []
    }
  }
}
```

---

## GET /reports/financial/movements

### Descripción
Listado de movimientos (membresías, ventas POS, gastos) ordenados por fecha.

### Query
`fromDate`, `toDate`, `limit` (default 200)

### Rol permitido
`admin`

---

## GET /reports/financial/export/excel

### Descripción
Exporta reporte financiero a Excel.

### Rol permitido
`admin`

---

## GET /reports/financial/export/pdf

### Descripción
Exporta reporte financiero a PDF.

### Rol permitido
`admin`

### Errores
| Código | Caso |
|--------|------|
| 403 | Rol distinto de admin |

---

*Ver `docs/fases/fase-08-reportes.md` para detalle de la fase.*

---

## GET /electronic-receipts

### Descripción
Bandeja de comprobantes electrónicos SRI.

### Query
`fromDate`, `toDate`, `documentType` (`01`, `04`), `status`, `limit`

### Rol permitido
`admin`, `recepcionista`

---

## GET /electronic-receipts/:id

### Descripción
Detalle del comprobante con líneas e importes fiscales.

---

## GET /electronic-receipts/:id/logs

### Descripción
Historial técnico de llamadas SOAP al SRI.

---

## GET /electronic-receipts/:id/xml

### Descripción
Descarga XML firmado o autorizado.

### Respuesta
Archivo `application/xml`

---

## GET /sri-config

### Descripción
Configuración fiscal pública: RUC, ambiente, series activas, readiness de certificado. **No expone claves ni contraseñas.**

### Rol permitido
`admin`

---

## POST /electronic-receipts/issue/membership/:membershipId

### Descripción
Emite factura electrónica (`01`) desde una suscripción sin `comprobante_id`.

### Rol permitido
`admin`, `recepcionista`

### Respuesta exitosa
```json
{
  "ok": true,
  "data": {
    "ok": true,
    "receiptId": 18,
    "code": "AUTORIZADO",
    "description": "Autorizado (firma o SRI en modo pruebas/simulación)",
    "simulated": true
  }
}
```

### Errores
- `404` — Suscripción no encontrada
- `400` — Ya tiene comprobante asociado

---

## POST /electronic-receipts/issue/sale/:saleId

### Descripción
Emite factura electrónica desde una venta POS sin `comprobante_id`.

### Rol permitido
`admin`, `recepcionista`

---

## POST /electronic-receipts/:id/credit-note

### Descripción
Emite nota de crédito (`04`) sobre factura autorizada.

### Body
```json
{
  "reasonCode": "01",
  "reasonDescription": "Anulación de la operación"
}
```

### Rol permitido
`admin`, `recepcionista`

---

## POST /electronic-receipts/:id/retry

### Descripción
Reintenta consulta de autorización SRI por clave de acceso.

### Rol permitido
`admin`, `recepcionista`

---

## GET /electronic-receipts/:id/pdf

### Descripción
Genera y descarga el RIDE (representación impresa) del comprobante electrónico con QR de clave de acceso SRI Ecuador.

### Respuesta
Archivo `application/pdf`

### Rol permitido
`admin`, `recepcionista`

---

## POST /electronic-receipts/:id/send-email

### Descripción
Envía por correo el RIDE (PDF) y el XML al cliente. Requiere SMTP configurado en el servidor.

### Body (opcional)
```json
{ "email": "cliente@ejemplo.com" }
```

### Respuesta exitosa
```json
{
  "ok": true,
  "data": {
    "sent": true,
    "recipient": "cliente@ejemplo.com",
    "messageId": "<...>",
    "attachments": ["RUC-01-001001-000000001.pdf", "RUC-01-001001-000000001.xml"]
  }
}
```

### Errores
- `400` — Cliente sin email y no se envió alternativo
- `503` — SMTP no configurado (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`)

### Rol permitido
`admin`, `recepcionista`

---

## GET /sri-config (campos adicionales)

### Campos de readiness
- `productionReady` — `true` si ambiente producción y certificado/datos fiscales OK
- `readinessChecks` — lista de validaciones (RUC, P12, clave, SMTP, ambiente)
- `sriEndpoints` — URLs celcer (pruebas) o cel (producción)
- `smtpConfigured` — indica si el envío por email está habilitado

---

*Ver `docs/fases/fase-09-facturacion-sri.md` para detalle de la fase.*

---

## Fase 11 — Asistente IA (socio)

### Flujo obligatorio

```text
Flutter → NestJS (/api/ai/chat) → Gemini → AiToolsService → BD
```

La API key de Gemini **nunca** va en Flutter.

---

## GET /ai/conversations

### Descripción
Lista conversaciones del socio autenticado (más recientes primero).

### Rol permitido
`socio`

### Respuesta exitosa
```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "title": "¿Cómo está mi membresía?",
      "createdAt": "2026-07-03T06:00:00.000Z",
      "updatedAt": "2026-07-03T06:01:00.000Z",
      "lastMessage": {
        "role": "assistant",
        "content": "...",
        "createdAt": "2026-07-03T06:01:00.000Z"
      }
    }
  ]
}
```

### Errores
- `401` — Sin token o token inválido
- `403` — No es socio o token de socio inválido

---

## GET /ai/conversations/:id

### Descripción
Detalle de una conversación con todos los mensajes.

### Rol permitido
`socio` (solo conversaciones propias)

### Errores
- `403` — Conversación de otro socio
- `404` — Conversación no encontrada

---

## POST /ai/chat

### Descripción
Envía un mensaje al asistente IA. Crea conversación si no se envía `conversationId`.

### Body
```json
{
  "message": "¿Cuántas veces fui este mes?",
  "conversationId": 1
}
```

### Respuesta exitosa
```json
{
  "ok": true,
  "data": {
    "conversationId": 1,
    "reply": "Según tus registros...",
    "message": {
      "id": 4,
      "role": "assistant",
      "content": "Según tus registros...",
      "metadata": { "model": "gemini-2.0-flash", "toolsUsed": ["getMemberProfile", "..."] },
      "createdAt": "2026-07-03T06:01:00.000Z"
    }
  }
}
```

### Errores
- `401` / `403` — Auth
- `429` — Límite diario (`AI_DAILY_MESSAGE_LIMIT`) o rate limit
- `503` — `GEMINI_API_KEY` no configurada en servidor

### Rol permitido
`socio`

---

*Ver `docs/fases/fase-11-ia-websockets-gemini.md` para detalle de la fase.*

---

## WebSocket — Asistente IA (streaming)

Transporte: **socket.io**. Base: la misma URL de la API **sin** `/api` (los namespaces cuelgan del host raíz). Namespace: **`/ai`**.

### Autenticación
JWT **access token** en el handshake (`auth.token`), verificado con `JWT_ACCESS_SECRET` (mismo secreto que la API REST). Solo **socios activos**; el `memberId` se toma del token, nunca del cliente. Token ausente/inválido/vencido o socio inactivo → se emite `ai.error` y se desconecta el socket.

Aislamiento: cada socket entra a la sala `member:{memberId}`; no recibe datos de otros socios.

### Eventos

| Dirección | Evento | Payload |
|-----------|--------|---------|
| Cliente → Servidor | `ai.message` | `{ "message": string (1..4000), "conversationId"?: number }` |
| Servidor → Cliente | `ai.response.chunk` | `{ "delta": string }` (fragmento de texto) |
| Servidor → Cliente | `ai.response.done` | `{ "conversationId": number, "message": { id, role, content, metadata, createdAt } }` |
| Servidor → Cliente | `ai.error` | `{ "message": string }` |

### Reglas de negocio (compartidas con REST)
- Mismo **límite diario** (`AI_DAILY_MESSAGE_LIMIT`) y validación de **propiedad** de la conversación.
- La respuesta completa se persiste igual que en REST (`ai_messages`).
- Si Gemini falla, se elimina el mensaje del socio para no romper el historial y se emite `ai.error`.

### Ejemplo (cliente)
```txt
connect  →  /ai   (auth: { token: <access_token> })
emit     →  ai.message { "message": "¿Cuántas veces fui este mes?" }
on       ←  ai.response.chunk { "delta": "Según " }
on       ←  ai.response.chunk { "delta": "tus registros..." }
on       ←  ai.response.done  { "conversationId": 1, "message": { ... } }
```

Respaldo: si el socket no conecta, Flutter usa `POST /api/ai/chat` (REST) automáticamente.

---

## WebSocket — Notificaciones en tiempo real (socio)

Transporte: **socket.io**. Base: la misma URL de la API **sin** `/api`. Namespace: **`/events`**.

### Autenticación
Idéntica al namespace `/ai`: JWT **access token** en el handshake (`auth.token`), verificado con `JWT_ACCESS_SECRET`. Solo **socios activos**; el `memberId` proviene del token. Token ausente/inválido/vencido o socio inactivo → se emite `notification.error` y se desconecta el socket.

Aislamiento: cada socket entra a la sala `member:{memberId}`; solo recibe sus propias notificaciones.

### Eventos

| Dirección | Evento | Payload |
|-----------|--------|---------|
| Servidor → Cliente | `notification` | `{ "type": string, "title": string, "body": string, "data"?: object, "createdAt": string ISO }` |
| Servidor → Cliente | `notification.error` | `{ "message": string }` (fallo de handshake, antes de desconectar) |

`type` actuales: `attendance.registered` (check-in del socio) y `membership.updated` (membresía activada o cancelada).

### Origen (acciones de dominio reales)

| Acción backend | `type` emitido |
|----------------|----------------|
| Registrar asistencia (`POST /api/attendance` / escaneo QR/DNI) | `attendance.registered` |
| Crear membresía (`POST /api/memberships`) | `membership.updated` |
| Cancelar membresía (`PATCH /api/memberships/:id/cancel`) | `membership.updated` |

La notificación se emite **después** de persistir la acción y nunca interrumpe el flujo de negocio.

### Ejemplo (cliente)
```txt
connect  →  /events   (auth: { token: <access_token> })
on       ←  notification { "type": "attendance.registered", "title": "Asistencia registrada", "body": "Tu ingreso...", "createdAt": "..." }
```

Sin variables de entorno ni tablas nuevas: reutiliza `CORS_ORIGINS` y `JWT_ACCESS_SECRET`.

---

*Swagger interactivo: `GET /api/docs`*
