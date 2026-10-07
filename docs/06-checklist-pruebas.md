# Checklist de Pruebas — Migración Gym System

## Fase 00 — Diagnóstico PHP

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Inventario controladores/modelos/vistas | Documentado | Sí | OK |
| Mapa BD 17+ tablas | Documentado | Sí | OK |
| Errores E01–E13 detectados | Documentado | Sí | OK |
| PHP sin modificaciones | Sin cambios en código | Sí | OK |

## Fase 01 — Backend base

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Servidor NestJS levanta | Sin errores | Sí | OK |
| Swagger `/api/docs` | HTTP 200 | Sí | OK |
| Health check | `database.status = up` | Sí | OK |
| ValidationPipe global | DTOs inválidos → 400 | Sí | OK |
| HttpExceptionFilter | `{ ok: false, error }` | Sí | OK |
| CORS / Helmet | Configurados en `main.ts` | Sí | OK |
| `.env.example` sin secretos reales | Placeholders | Sí | OK |
| `npm run build` | Compila sin errores | Sí | OK |
| `npm run lint` | 0 errores ESLint | Sí | OK (2026-07-02) |
| `npm run test:e2e` | 2 tests pasan | Sí | OK (2026-07-02) |

## Fase 02 — Auth JWT

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Login correcto | Tokens emitidos (201) | Sí | OK |
| Login incorrecto | 401 controlado | Sí | OK |
| Login usuario inactivo | 401 mensaje inhabilitada | Sí | OK |
| Access token válido `/auth/me` | 200 | Sí | OK |
| Token inválido | 401 | Sí | OK |
| Refresh token válido | Nuevo par (201) | Sí | OK |
| Refresh token inválido | 401 | Sí | OK |
| Logout | Refresh revocado | Sí | OK |
| Refresh tras logout | 401 | Sí | OK |
| Acceso sin token | 401 | Sí | OK |
| Admin en `GET /users` | 200 | Sí | OK |
| Recepcionista en `GET /users` | 403 | Sí | OK |
| Password oculto en respuestas | Sin campo `password` | Sí | OK |
| Refresh hasheado en BD | SHA-256 en `auth_refresh_tokens` | Sí | OK (código) |
| Crear usuario | 201 sin password en respuesta | Sí | OK |
| Editar usuario | 200 | Sí | OK |
| Cambiar estado | 200 | Sí | OK |
| Cambiar contraseña | 200 | Sí | OK |
| Access token expirado → refresh | 401 en `/me` | Sí | OK (JWT expirado en auditoría) |
| Staff inactivo en `/auth/refresh` | 401 cuenta inhabilitada | Sí | OK (corrección auditoría 2026-07-02) |
| Staff inactivo en `/auth/me` | 401 cuenta inhabilitada | Sí | OK (corrección auditoría 2026-07-02) |

### Script de auditoría

```bash
cd backend-nest
npm run audit:phase-02
```

Última ejecución auditoría: **21/21 OK** (`npm run audit:phase-02`).

## Fase 03 — Socios, planes y membresías

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Listar socios con paginación | 200 + `items`/`meta` | Sí | OK |
| Obtener socio por ID | 200 | Sí | OK |
| Estado membresía calculado | `effectiveStatus`, `isMembershipValid` | Sí | OK |
| Historial membresías socio | 200 array | Sí | OK |
| Crear socio | 201 | Sí | OK |
| DNI duplicado | 409 | Sí | OK |
| Editar socio | 200 | Sí | OK |
| Listar planes | 200 | Sí | OK |
| Crear plan (admin) | 201 | Sí | OK |
| Crear plan (recepcionista) | 403 | Sí | OK |
| Crear membresía | 201 + `endDate` calculada | Sí | OK |
| Cancelar membresía | 200, estado `vencida` | Sí | OK |
| Entrenador lista socios | 200 | Sí | OK |
| Entrenador en membresías | 403 | Sí | OK |
| Recepcionista crea socio | 201 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 03

```bash
cd backend-nest
npm run audit:phase-03
```

Última ejecución: **26/26 OK** (`npm run audit:phase-03`).

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Upload foto socio | 201 + `photoUrl` | Sí | OK |
| Export Excel membresías | .xlsx descargable | Sí | OK |
| Login socio app móvil | JWT `userType: member` | Sí | OK |
| `/auth/me` como socio | Perfil sin password | Sí | OK |
| Establecer password socio | PATCH `/members/:id/password` | Sí | OK |

## Fase 04 — Asistencias y QR

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Validar DNI con acceso | 200, `canAccess: true` | Sí | OK |
| Validar DNI inexistente | 200, `found: false` | Sí | OK |
| Registrar asistencia | 201 | Sí | OK |
| Sin membresía vigente | 400 | Sí | OK |
| Duplicado mismo día | 409 | Sí | OK |
| Listar asistencias hoy | 200 array | Sí | OK |
| Reporte por fechas | 200 + `totalVisits` | Sí | OK |
| Ranking top 5 | 200 + `leaders` | Sí | OK |
| Validar QR (DNI) | 200, `canAccess: true` | Sí | OK |
| Datos carnet digital | 200 + `qrPayload` | Sí | OK |
| Socio auto-registro duplicado | 409 | Sí | OK |
| Entrenador consulta hoy | 200 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 04

```bash
cd backend-nest
npm run audit:phase-04
```

Última ejecución: **15/15 OK** (`npm run audit:phase-04`).

## Fase 05 — Progreso físico y rutinas

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Crear medida corporal | 201 | Sí | OK |
| Listar medidas + chart | 200 + `chart.labels` | Sí | OK |
| Crear rutina | 201 | Sí | OK |
| Rutina actual + historial | 200 | Sí | OK |
| Eliminar medida | 200 | Sí | OK |
| Recepcionista crear medida | 403 | Sí | OK |
| Socio `/body-progress/me` | 200 | Sí | OK |
| Socio ve otro socio | 403 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 05

```bash
npm run audit:phase-05
```

Última ejecución: **12/12 OK** (`npm run audit:phase-05`).

## Fase 06 — Inventario y productos

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 06 | Categorías | Crear | POST `/categories` | 201 | 201 | OK |
| 06 | Categorías | Listar | GET `/categories` | 200 | 200 | OK |
| 06 | Categorías | Listar activas | GET `/categories/active` | 200 | 200 | OK |
| 06 | Productos | Crear | POST `/products` | 201 + stock ≥ 0 | 201 stock=10 | OK |
| 06 | Productos | Detalle | GET `/products/:id` | 200 | 200 | OK |
| 06 | Productos | Activos POS | GET `/products/active` | 200 | 200 | OK |
| 06 | Inventario | Restar stock | POST `/inventory/adjustments` subtract | stock actualizado | 7 | OK |
| 06 | Inventario | Bloquear negativo | POST subtract excesivo | 400 | 400 | OK |
| 06 | Inventario | Sumar stock | POST `/inventory/adjustments` add | stock actualizado | 12 | OK |
| 06 | Productos | Stock bajo | GET `/products/low-stock` | 200 | 200 | OK |
| 06 | Productos | Desactivar | PATCH `/products/:id/status` | inactivo | inactivo | OK |
| 06 | Categorías | Recepcionista crear | POST `/categories` | 403 | 403 | OK |
| 06 | Inventario | Historial movimientos | GET `/inventory/movements` | 200 | 200 | OK |
| 06 | Inventario | Movimientos por producto | GET `/inventory/products/:id/movements` | ≥1 movimiento | 3+ | OK |
| 06 | Caja | Abrir caja | POST `/cash-registers/open` | 201 | 201 | OK |
| 06 | Ventas | Sin caja abierta | POST `/sales` | 400 | 400 | OK |
| 06 | Ventas | Registrar venta POS | POST `/sales` | 201 + stock | 201 stock=18 | OK |
| 06 | Ventas | Movimiento tipo sale | GET `/inventory/movements?saleId=` | sale auditado | OK | OK |
| 06 | Ventas | Stock insuficiente | POST `/sales` exceso | 400 | 400 | OK |
| 06 | Ventas | Producto duplicado en items | POST `/sales` misma línea 2× | 400 si stock total excede | OK (código) | OK |
| 06 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 06

```bash
npm run audit:phase-06
```

Última ejecución: **24/24 OK** (`npm run audit:phase-06`).

## Fase 07 — POS, ventas y caja

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 07 | Caja | Resumen sesión | GET `/cash-registers/current/summary` | 200 + expectedAmount | 200 | OK |
| 07 | POS | Registrar venta | POST `/sales` | 201 + items | 201 | OK |
| 07 | POS | Detalle venta | GET `/sales/:id` | 200 | 200 | OK |
| 07 | POS | Ticket | GET `/sales/:id/ticket` | 200 + ticketNumber | OK | OK |
| 07 | POS | Historial | GET `/sales?fromDate&toDate` | incluye venta | 200 | OK |
| 07 | Caja | Cierre con diferencia | POST `/cash-registers/close` | 201 | 201 | OK |
| 07 | Caja | Historial cajas | GET `/cash-registers/history` | 200 | 200 | OK |
| 07 | POS | Entrenador bloqueado | POST `/sales` | 403 | 403 | OK |
| 07 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 07

```bash
npm run audit:phase-07
```

Última ejecución: **16/16 OK** (`npm run audit:phase-07`).

## Fase 08 — Reportes y exportaciones

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 08 | Reportes | Summary financiero | GET `/reports/financial/summary` | 200 + KPIs | 200 | OK |
| 08 | Reportes | Movimientos | GET `/reports/financial/movements` | 200 + items | 200 | OK |
| 08 | Reportes | Export Excel | GET `/reports/financial/export/excel` | xlsx | 7134b | OK |
| 08 | Reportes | Export PDF | GET `/reports/financial/export/pdf` | %PDF | 1971b | OK |
| 08 | Reportes | Recepcionista bloqueado | GET `/reports/financial/summary` | 403 | 403 | OK |
| 08 | Asistencias | Export PDF | GET `/attendance/report/export/pdf` | %PDF | 1664b | OK |
| 08 | Asistencias | Export Excel | GET `/attendance/report/export/excel` | xlsx | 6830b | OK |
| 08 | POS | Ticket PDF | GET `/sales/:id/ticket/export/pdf` | %PDF | 1717b | OK |
| 08 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 08

```bash
npm run audit:phase-08
```

Última ejecución: **11/11 OK** (`npm run audit:phase-08`).

## Fase 09 — Facturación SRI (slice 1 consulta + slice 2 emisión)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 09 | SRI | Bandeja comprobantes | GET `/electronic-receipts` | 200 + items | 200 | OK |
| 09 | SRI | Config fiscal | GET `/sri-config` | 200 sin secretos | 200 | OK |
| 09 | SRI | Recepcionista bandeja | GET `/electronic-receipts` | 200 | 200 | OK |
| 09 | SRI | Recepcionista bloqueado config | GET `/sri-config` | 403 | 403 | OK |
| 09 | SRI | Entrenador bloqueado | GET `/electronic-receipts` | 403 | 403 | OK |
| 09 | SRI | Emitir desde membresía | POST `/electronic-receipts/issue/membership/:id` | AUTORIZADO | AUTORIZADO | OK |
| 09 | SRI | Emitir desde venta | POST `/electronic-receipts/issue/sale/:id` | AUTORIZADO | AUTORIZADO | OK |
| 09 | SRI | Membresía inexistente | POST issue/membership/99999999 | 404 | 404 | OK |
| 09 | SRI | RIDE PDF | GET `/electronic-receipts/:id/pdf` | 200 PDF | 200 | OK |
| 09 | SRI | Envío email SMTP | POST `.../send-email` | 201 sent:true | 201 | OK |
| 09 | SRI | Readiness producción | GET `/sri-config` | checks[] | 7 checks | OK |
| 09 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 09

```bash
npm run audit:phase-09
```

Última ejecución: **17/17 OK** (`npm run audit:phase-09`).

## Fase 10 — Flutter app socio

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | API | GET membresía propia | GET `/members/me/membership` | 200 socio | activa | OK |
| 10 | API | GET historial membresías | GET `/members/me/memberships` | 200 array | 1 | OK |
| 10 | API | GET carnet QR propio | GET `/qr-access/me/card` | 200 + qrPayload | DNI | OK |
| 10 | API | Staff bloqueado me/membership | GET con admin | 403 | 403 | OK |
| 10 | Flutter | Análisis estático | `flutter analyze` | sin errores | OK | OK |
| 10 | Flutter | Test widget | `flutter test` | 1/1 | 1/1 | OK |
| 10 | Flutter | Login UI | Pantalla login | navega a home | OK (Chrome) | OK |
| 10 | Flutter | CORS web :8080 | Login desde Chrome | 201 | corregido CORS | OK |
| 10 | Flutter | API ngrok (web-dist) | Login vía túnel | 200 health + login | API `:3000` + CORS ngrok | OK |
| 10 | API | GET asistencias propias | GET `/attendance/me` | 200 + items | OK | OK |
| 10 | API | POST asistencia propia | POST `/attendance/self` | 201 o 409 | 201 | OK |
| 10 | Flutter | Inicio resumen asistencias | Tab Inicio | visitas del mes | slice 2 | OK |
| 10 | Flutter | Historial membresías | Tab Perfil | lista planes | slice 2 | OK |
| 10 | Flutter | Dispositivo físico LAN | `API_BASE_URL=IP:3000` | conecta API | OK | OK |
| 10 | Flutter | Carnet QR | Tab Carnet | muestra QR | Pendiente manual | Pendiente |
| 10 | Flutter | Registrar asistencia | Botón inicio | 201 o 409 | Pendiente manual | Pendiente |

### Script de auditoría Fase 10 (API)

```bash
cd backend-nest
npm run audit:phase-10
```

Última ejecución: **13/13 OK** (`npm run audit:phase-10`).

**Estado Fase 10:** Aprobada (2026-07-03).

## Fase 11 — IA + Gemini (slice 1 REST)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 11 | API | Staff bloqueado IA | GET `/ai/conversations` admin | 403 | 403 | OK |
| 11 | API | Lista conversaciones socio | GET `/ai/conversations` | 200 array | 0 | OK |
| 11 | API | Chat sin GEMINI_API_KEY | POST `/ai/chat` | 503 | 503 | OK |
| 11 | API | Chat con Gemini | POST `/ai/chat` | 200 + reply | Pendiente con API key | Pendiente |
| 11 | Flutter | Tab Asistente | Navegación | pantalla chat | Implementado | Pendiente manual |
| 11 | Flutter | Enviar mensaje | Botón enviar | respuesta IA | Requiere GEMINI_API_KEY | Pendiente |
| 11 | Flutter | Análisis | `flutter analyze` | sin errores | OK | OK |
| 11 | Flutter | Test widget | `flutter test` | 1/1 | 1/1 | OK |

### Script de auditoría Fase 11 (API)

```bash
cd backend-nest
npm run audit:phase-11
```

Última ejecución: **6/6 OK** (`npm run audit:phase-11`, sin `GEMINI_API_KEY`).

## Fase 10 (UI) — Sistema visual global Material 3

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | Tema global | `flutter analyze` | — | sin errores nuevos | OK (solo avisos preexistentes) | OK |
| 10 | Todas las pantallas | Aplicar `AppTheme.light()` | — | estilo consistente heredado | OK (propagado por tema) | OK |
| 10 | Login | Ver AppBar/inputs/botón | — | inputs y botón con nuevo estilo | Pendiente manual | Pendiente |
| 10 | Inicio | Tarjetas y `NavigationBar` | — | tarjetas con borde, nav con indicador | Pendiente manual | Pendiente |
| 10 | Carnet QR | Fondo blanco del QR | — | QR legible sobre blanco | Conservado a propósito | OK |
| 10 | Perfil | Chips de estado / `ListTile` | — | chips y listas con nuevo estilo | Pendiente manual | Pendiente |
| 10 | Asistente IA | Burbujas y `MaterialBanner` | — | contraste correcto | Pendiente manual | Pendiente |

## Fase 10 (UI) — Modo oscuro (Material 3)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | Tema | `flutter analyze` archivos modificados | — | sin issues | No issues found | OK |
| 10 | App | SO en oscuro | — | app en modo oscuro | Pendiente manual | Pendiente |
| 10 | App | SO en claro | — | app en modo claro | Pendiente manual | Pendiente |
| 10 | Login/Botones | Contraste texto/íconos en oscuro | — | legible | Tokens adaptativos | OK (por diseño) |
| 10 | Carnet QR | QR + chip acceso en oscuro | — | QR blanco legible, chip con contraste | `_AccessChip` translúcido | OK (por diseño) |
| 10 | Progreso | Ejes de gráfica en oscuro | — | números legibles | `fl_chart` estilo por defecto | Revisar manual |

## Fase 10 (UI) — Selector manual de tema en Perfil

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | Perfil | `flutter analyze` archivos tema | — | sin issues | No issues found | OK |
| 10 | Perfil | Seleccionar "Claro" | — | app cambia a claro al instante | Pendiente manual | Pendiente |
| 10 | Perfil | Seleccionar "Oscuro" | — | app cambia a oscuro al instante | Pendiente manual | Pendiente |
| 10 | Perfil | Seleccionar "Sistema" | — | app sigue el tema del SO | Pendiente manual | Pendiente |
| 10 | Perfil | Reiniciar app tras elegir tema | — | conserva la preferencia | Pendiente manual | Pendiente |

## Fase 10 (UI) — Rediseño estilo fitness oscuro (acento naranja)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | Tema | `flutter analyze` archivos modificados | — | sin issues | No issues found | OK |
| 10 | App | Primer arranque | — | abre en oscuro por defecto | Pendiente manual | Pendiente |
| 10 | Login/Home | Botón primario | — | píldora naranja, texto/spinner oscuro | Pendiente manual | Pendiente |
| 10 | Inicio/Perfil | Tarjetas y `NavigationBar` | — | tarjetas redondeadas, nav con acento naranja | Pendiente manual | Pendiente |
| 10 | Progreso | Gráficas en oscuro | — | líneas y ejes legibles | Ejes/rejilla adaptados al tema | OK (por diseño) |
| 10 | Progreso | `flutter analyze` gráficas | — | sin issues | No issues found | OK |
| 10 | Progreso | Ver gráficas Peso/% Grasa | — | línea naranja / celeste + relleno | Pendiente manual | Pendiente |

## Fase 10 (UI) — Icono y splash de marca

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | Icono | `dart run flutter_launcher_icons` | — | genera iconos multiplataforma | Successfully generated | OK |
| 10 | Splash | `dart run flutter_native_splash:create` | — | genera splash multiplataforma | Native splash complete | OK |
| 10 | Login | `flutter analyze` login | — | sin issues | No issues found | OK |
| 10 | Login | Ver logo de marca | — | mancuerna naranja centrada | Pendiente manual | Pendiente |
| 10 | Launcher | Icono en el dispositivo tras reinstalar | — | icono naranja/oscuro | Pendiente manual | Pendiente |
| 10 | Arranque | Splash al abrir la app | — | fondo oscuro + logo naranja | Pendiente manual | Pendiente |

## Fase 11 (Slice 2) — WebSockets: streaming del chat IA

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint/Evento | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|-----------------|-------------------|-------------------|--------|
| 11 | Backend | `npm run build` | — | compila sin errores | Build OK | OK |
| 11 | Backend | Lint archivos WS | — | sin errores | Sin errores | OK |
| 11 | Flutter | `flutter analyze` (chat/socket) | — | sin issues | No issues found | OK |
| 11 | Asistente | Enviar mensaje (socket) | `ai.message` | respuesta token a token | Requiere `GEMINI_API_KEY` | Pendiente manual |
| 11 | Asistente | Recibir fragmentos | `ai.response.chunk` | texto que crece en vivo | Pendiente manual | Pendiente |
| 11 | Asistente | Fin de respuesta | `ai.response.done` | conversación persistida | Pendiente manual | Pendiente |
| 11 | Seguridad | Conectar sin token / token inválido | handshake `/ai` | `ai.error` + desconexión | Pendiente manual | Pendiente |
| 11 | Seguridad | Socio no ve chats de otro | sala `member:{id}` | aislado por socio | Por diseño | OK (por diseño) |
| 11 | Resiliencia | Backend sin socket disponible | — | cae a REST `POST /ai/chat` | Fallback implementado | OK (por diseño) |

## Fase 11 (Slice 2b) — Gestión de conversaciones IA

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 11 | Asistente | Menú → Nuevo chat | — | pantalla vacía, sin `conversationId` | Implementado | Pendiente manual |
| 11 | Asistente | Menú → Archivar chat | PATCH `/ai/conversations/:id/status` | conversación pasa a `archived` | Implementado | Pendiente manual |
| 11 | Asistente | Menú → Eliminar chat | DELETE `/ai/conversations/:id` | conversación y mensajes borrados | Implementado | Pendiente manual |
| 11 | Asistente | Menú → Mis conversaciones | GET `/ai/conversations?status=active` | lista y abre chat seleccionado | Implementado | Pendiente manual |
| 11 | Asistente | Menú → Ver archivadas | GET `/ai/conversations?status=archived` | lista archivadas + restaurar | Implementado | Pendiente manual |
| 11 | API | Mensaje en chat archivado | POST `/ai/chat` | 400 — debe restaurar primero | Por diseño | Pendiente manual |
| 11 | Backend | `npm run build` | — | compila sin errores | Build OK | OK |

## Fase 11 (Slice 2c) — Llamada de voz en chat IA

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 11 | Asistente | Botón teléfono (iniciar) | STT local + `ai.message` | escucha, transcribe y envía | Implementado | Pendiente manual |
| 11 | Asistente | Respuesta hablada | TTS local | lee respuesta del asistente | Implementado | Pendiente manual |
| 11 | Asistente | Botón colgar (finalizar) | — | detiene micrófono y voz | Implementado | Pendiente manual |
| 11 | Flutter | `flutter analyze` (voz) | — | sin issues | No issues found | OK |

## Fase 11 (Slice 3) — WebSockets: notificaciones en tiempo real

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint/Evento | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|-----------------|-------------------|-------------------|--------|
| 11 | Backend | `npm run build` | — | compila sin errores | Build OK | OK |
| 11 | Backend | `npm run lint` | — | 0 errores | 0 errores | OK |
| 11 | Flutter | `flutter analyze` (nuevos/modificados) | — | sin issues | No issues found | OK |
| 11 | Notificaciones | Registrar asistencia del socio conectado | `notification` `attendance.registered` | SnackBar + badge en la app | — | Pendiente manual |
| 11 | Notificaciones | Crear membresía del socio | `notification` `membership.updated` | aviso "Membresía activada" | — | Pendiente manual |
| 11 | Notificaciones | Cancelar membresía del socio | `notification` `membership.updated` | aviso "Membresía cancelada" | — | Pendiente manual |
| 11 | Notificaciones | Abrir campana / marcar leídas | panel historial | badge vuelve a 0 | — | Pendiente manual |
| 11 | Seguridad | Conectar `/events` sin token / inválido | handshake `/events` | `notification.error` + desconexión | — | Pendiente manual |
| 11 | Seguridad | Socio no recibe avisos de otro | sala `member:{id}` | aislado por socio | Por diseño | OK (por diseño) |
| 11 | Resiliencia | Notificar con socio desconectado | `RealtimeService` | no rompe la asistencia/membresía | Por diseño | OK (por diseño) |

## Fase 12 — Cierre de migración (verificación)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 12 | Backend | `npm run build` | — | compila sin errores | Build OK | OK |
| 12 | Backend | `npm run lint` | — | 0 errores | 0 errores | OK |
| 12 | Backend | `npm run start:dev` (arranque) | — | Nest inicia (grafo de módulos válido) | App levanta | OK |
| 12 | Flutter | `flutter analyze` | — | sin issues nuevos | 14 avisos preexistentes, 0 nuevos | OK |
| 12 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 12 | Config | `.env.example` completo, sin secretos | — | todas las variables presentes | Verificado | OK |
| 12 | Docs | Cierre, manual, README, plan, riesgos | — | coherentes con el código | Actualizados | OK |
| 12 | IA/WebSockets | Prueba manual con `GEMINI_API_KEY` real | `/ai`, `/events` | streaming + notificaciones | — | Pendiente manual |

## Fase 13 (Slice 1) — Flutter app staff: login y panel inicial

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 13 | Flutter | `flutter analyze` | — | sin errores | 0 errores, 14 avisos preexistentes | OK |
| 13 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 13 | Login | Selector Socio / Staff | — | cambia título y flujo de login | — | Pendiente manual |
| 13 | Login | Iniciar sesión staff (admin) | `POST /auth/login` | tokens + redirect `/staff/home` | — | Pendiente manual |
| 13 | Staff home | Ver perfil y KPI asistencias hoy | `GET /auth/me`, `GET /attendance/today` | panel con datos reales | — | Pendiente manual |
| 13 | Staff | Cerrar sesión | `POST /auth/logout` | vuelve a `/login` | — | Pendiente manual |
| 13 | Seguridad | Socio intenta `/staff/home` | redirect | redirige a `/home` | Por diseño | OK (por diseño) |
| 13 | Seguridad | Staff intenta `/home` (socio) | redirect | redirige a `/staff/home` | Por diseño | OK (por diseño) |
| 13 | API | Timeouts Dio en Android | — | no aborta con 0ms | connect 15s / receive 30s | OK |

## Fase 13 (Slice 2) — Socios y membresías staff

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 13 | Flutter | `flutter analyze` (slice 2) | — | sin errores | 0 errores | OK |
| 13 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 13 | Staff nav | Pestaña Socios | — | listado con búsqueda | — | Pendiente manual |
| 13 | Socios | Buscar por nombre/DNI | `GET /members?search=` | resultados filtrados | — | Pendiente manual |
| 13 | Socios | Nuevo socio (admin/recep.) | `POST /members` | socio creado | — | Pendiente manual |
| 13 | Socios | Ver detalle | `GET /members/:id` | datos + membresía | — | Pendiente manual |
| 13 | Socios | Asignar plan | `POST /memberships` | membresía activa | — | Pendiente manual |
| 13 | Seguridad | Entrenador sin botón crear | UI | solo lectura socios | Por diseño | OK (por diseño) |

## Fase 13 (Slice 3) — Asistencias staff

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 13 | Flutter | `flutter analyze` (slice 3) | — | sin errores | 0 errores | OK |
| 13 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 13 | Asistencias | Escanear QR carnet | `POST /attendance/scan` | ingreso registrado | — | Pendiente manual |
| 13 | Asistencias | Validar DNI (paso 1) | `POST /attendance/validate` | preview socio/membresía | — | Pendiente manual |
| 13 | Asistencias | Confirmar ingreso DNI | `POST /attendance/register` | asistencia creada | — | Pendiente manual |
| 13 | Asistencias | Registro rápido DNI | `POST /attendance/scan` | un paso | — | Pendiente manual |
| 13 | Asistencias | Listado hoy | `GET /attendance/today` | lista con hora/método | — | Pendiente manual |
| 13 | Regla | Socio sin membresía vigente | validate/register | acceso denegado | Por diseño | OK (por diseño) |
| 13 | Regla | Duplicado mismo día | register/scan | 409 conflicto | Por diseño | OK (por diseño) |

## Fase 13 (Slice 4) — POS y caja staff

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 13 | Flutter | `flutter analyze` (slice 4) | — | sin errores | 0 errores | OK |
| 13 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 13 | POS | Abrir caja | `POST /cash-registers/open` | caja abierta | — | Pendiente manual |
| 13 | POS | Ver resumen caja | `GET /cash-registers/current/summary` | totales POS | — | Pendiente manual |
| 13 | POS | Cobrar venta | `POST /sales` | venta + stock descontado | — | Pendiente manual |
| 13 | POS | Venta sin caja | `POST /sales` | 400 error | Por diseño | OK (por diseño) |
| 13 | POS | Cerrar caja | `POST /cash-registers/close` | diferencia calculada | — | Pendiente manual |
| 13 | POS | Listado ventas hoy | `GET /sales` | historial del día | — | Pendiente manual |
| 13 | Seguridad | Entrenador en `/staff/pos` | UI | acceso denegado | Por diseño | OK (por diseño) |

## Fase 13 (Slice 5) — Reportes y usuarios admin staff

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 13 | Flutter | `flutter analyze` (slice 5) | — | sin errores | 0 errores | OK |
| 13 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 13 | Reportes | Ver KPIs período | `GET /reports/financial/summary` | ingresos, gastos, utilidad | — | Pendiente manual |
| 13 | Reportes | Cambiar rango fechas | query `fromDate`/`toDate` | recarga KPIs y movimientos | — | Pendiente manual |
| 13 | Reportes | Listar movimientos | `GET /reports/financial/movements` | ingresos/gastos del período | — | Pendiente manual |
| 13 | Usuarios | Listar staff | `GET /users` | lista usuarios sistema | — | Pendiente manual |
| 13 | Usuarios | Crear usuario | `POST /users` | usuario nuevo en lista | — | Pendiente manual |
| 13 | Usuarios | Activar/desactivar | `PATCH /users/:id/status` | cambio de estado | — | Pendiente manual |
| 13 | Seguridad | Recepcionista en `/staff/reports` | UI | acceso denegado | Por diseño | OK (por diseño) |
| 13 | Seguridad | Recepcionista en `/staff/users` | UI | acceso denegado | Por diseño | OK (por diseño) |
| 13 | Seguridad | Admin no puede desactivarse a sí mismo | UI | switch oculto en fila propia | Por diseño | OK (por diseño) |

## Fase 14 (Slice 1) — Exportación reportes staff

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 14 | Flutter | `flutter analyze` (slice 1) | — | sin errores | 0 errores | OK |
| 14 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 14 | Reportes | Exportar Excel | `GET /reports/financial/export/excel` | archivo .xlsx compartible | — | Pendiente manual |
| 14 | Reportes | Exportar PDF | `GET /reports/financial/export/pdf` | archivo .pdf compartible | — | Pendiente manual |

## Fase 14 (Slice 2) — Coaching staff (progreso y rutinas)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 14 | Detalle socio | Progreso y rutina | navegación | abre coaching | — | Pendiente manual |
| 14 | Coaching | Ver medidas y gráficos | `GET /body-progress/members/:id/measurements` | historial + charts | — | Pendiente manual |
| 14 | Coaching | Nueva medida | `POST /body-progress/members/:id/measurements` | medida en lista | — | Pendiente manual |
| 14 | Coaching | Eliminar medida | `DELETE /body-progress/measurements/:id` | medida removida | — | Pendiente manual |
| 14 | Coaching | Ver rutina actual | `GET /workout-routines/members/:id/current` | días y notas | — | Pendiente manual |
| 14 | Coaching | Asignar rutina | `POST /workout-routines/members/:id` | nueva versión visible | — | Pendiente manual |
| 14 | Seguridad | Recepcionista en coaching | UI | solo lectura (sin FAB) | Por diseño | OK (por diseño) |
| 14 | Seguridad | Entrenador sin permiso reportes | UI | sin módulo reportes | Por diseño | OK (por diseño) |

## Fase 15 — Facturación SRI staff Flutter

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 15 | Flutter | `flutter analyze` | — | sin errores | 0 errores | OK |
| 15 | Flutter | `flutter test` | — | pruebas OK | 1/1 OK | OK |
| 15 | SRI | Bandeja comprobantes | `GET /electronic-receipts` | lista filtrable | — | Pendiente manual |
| 15 | SRI | Detalle comprobante | `GET /electronic-receipts/:id` | líneas y estado | — | Pendiente manual |
| 15 | POS | Emitir factura venta | `POST /electronic-receipts/issue/sale/:id` | comprobante creado | — | Pendiente manual |
| 15 | Socios | Emitir factura membresía | `POST /electronic-receipts/issue/membership/:id` | comprobante creado | — | Pendiente manual |
| 15 | SRI | Descargar RIDE PDF | `GET /electronic-receipts/:id/pdf` | PDF compartible | — | Pendiente manual |
| 15 | SRI | Descargar XML | `GET /electronic-receipts/:id/xml` | XML compartible | — | Pendiente manual |
| 15 | SRI | Reintentar autorización | `POST /electronic-receipts/:id/retry` | estado actualizado | — | Pendiente manual |
| 15 | SRI | Nota de crédito | `POST /electronic-receipts/:id/credit-note` | NC emitida | — | Pendiente manual |
| 15 | SRI | Enviar email | `POST /electronic-receipts/:id/send-email` | correo enviado (SMTP) | — | Pendiente manual |
| 15 | Seguridad | Entrenador en `/staff/sri` | UI | acceso denegado | Por diseño | OK (por diseño) |
| 15 | Admin | Config readiness | `GET /sri-config` | tarjeta fiscal en bandeja | — | Pendiente manual |

## Fase 16 — Notificaciones persistentes socio

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 16 | Backend | `npm run build` + lint | — | OK | OK | OK |
| 16 | Flutter | `flutter analyze` / `test` | — | OK | 0 errores / 1/1 | OK |
| 16 | BD | `npm run db:push:legacy` | tabla `notifications` | tabla creada | — | Pendiente manual |
| 16 | Socio | Abrir app (historial) | `GET /notifications` | lista persistida | — | Pendiente manual |
| 16 | Socio | Registrar asistencia | WS + BD | notificación en campana tras reconectar | — | Pendiente manual |
| 16 | Socio | Abrir campana | `PATCH /notifications/read-all` | badge en 0 | — | Pendiente manual |
| 16 | Seguridad | Staff en `/notifications` | API | 403 | Por diseño | OK (por diseño) |

## Fase 17 — Alertas membresía por vencer

**Estado global:** APROBADA Y CERRADA (2026-10-01) — rama `fix/fase-17-membership-alerts-dates` @ `c4c5adc`

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 17 | Backend | `npm run build` + lint + test | — | OK | OK (2026-10-01) | OK |
| 17 | Script | `npm run audit:phase-17` | varios | 18/18 PASS | 18/18 PASS | OK |
| 17 | Admin | Ejecutar job manual | `POST /membership-alerts/run` | `{ sent, skipped, expired }` | `sent=4 skipped=0 expired=1` (escenario controlado) | OK |
| 17 | Seguridad | Sin token | `POST /membership-alerts/run` | 401 | 401 | OK |
| 17 | Seguridad | Recepcionista | `POST /membership-alerts/run` | 403 | 403 | OK |
| 17 | Socio | Umbrales 7/3/1/0 | campana/API | `membership.expiring` | 1 por umbral; 2/8/30 sin alerta | OK |
| 17 | Socio | Segundo run mismo día | — | sin duplicado (`skipped` > 0) | `sent=0 skipped=4` | OK |
| 17 | Socio | Membresía vencida ayer + run | API | `membership.updated` + estado vencida | OK | OK |
| 17 | Seguridad | Aislamiento A/B | `GET /notifications` | A no ve B | PASS | OK |
| 17 | Flutter | Campana visual (F17-09) | UI | badge + listado | PASS manual 2026-10-01 (badge 1, título/contenido/plan/3 días/icono/hora; Run#1 sent=1; Run#2 skipped=1) | OK |
| 17 | Cron | Job 08:00 (prod) | scheduler | alertas automáticas | código revisado; horario depende del servidor | OK (por diseño; no forzado en validación) |

### Matriz F17 (cierre)

| ID | Resultado |
|----|-----------|
| F17-01 … F17-08 | PASS |
| F17-09 Campana Flutter | PASS (manual) |
| F17-10 … F17-13 | PASS |
| F17-D01 fechas DATE/UTC | PASS (corregido) |
| F17-D02 await notifyMember | PASS (corregido) |

## Fase 18 — Salida de ngrok (LAN)

**Estado global:** READY FOR REVIEW — rama `feature/fase-18-salir-ngrok` (commit pendiente de autorización)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 18 | Script | `.\scripts\run-lan-dev.ps1` | — | Detecta IP + health LAN | IP `192.168.100.140` + health 200 | OK |
| 18 | API | Health localhost | `GET /api/health` | 200 | 200 | OK |
| 18 | API | Health LAN | `GET http://IP:3000/api/health` | 200 | 200 | OK |
| 18 | API | CORS Origin LAN `:8888` | header ACAO | origen LAN permitido | `allow-origin=http://IP:8888` | OK |
| 18 | Backend | Jest CORS | `cors.config.spec` | 5 tests | 5/5 PASS | OK |
| 18 | Backend | `npm run build` | — | OK | OK | OK |
| 18 | Backend | `npm run audit:phase-18` | — | PASS | 6/6 (login SKIP) | OK |
| 18 | Flutter | `ApiConfig` REST=WS host | — | mismo host | Por diseño | OK (por diseño) |
| 18 | Scripts | ngrok marcado LEGACY | — | no flujo principal | Documentado | OK |
| 18 | Tunnel | `start-cloudflare-tunnel.ps1` sin token | — | PENDIENTE CONFIG EXTERNA | exit 2; cloudflared OK | OK (esperado) |
| 18 | Socio | Login por LAN | `POST /auth/member/login` | 200 + tokens | — | Pendiente manual |
| 18 | Flutter Web | Chrome `:8888` LAN | UI | login/home | — | Pendiente manual |
| 18 | Android | App física LAN | UI | login/home | — | Pendiente manual |
| 18 | WS | `/events` por LAN | handshake JWT | conecta | — | Pendiente manual |
| 18 | WS | `/ai` por LAN | chat | turno texto | — | Pendiente manual |
| 18 | F17 | Notificación/campana | `/events` + API | sin regresión | Sin cambios F17; smoke pendiente | Pendiente manual |
| 18 | Docs | `PLAN-SALIR-DE-NGROK.md` | — | permanece `??` | `??` | OK |

### SAAS-02 — Fundaciones

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|---|
| SAAS-02 | Prisma | `npx prisma validate` | — | válido | válido | OK |
| SAAS-02 | Migraciones | `npm run db:validate-migrations` | — | baseline aplica sobre base vacía, sin drift inesperado | 23 tablas, 21 FK, 5 únicos; 1 diferencia equivalente aceptada | OK |
| SAAS-02 | Migraciones | Drift solo lectura contra la BD real | — | solo `sri_ambiente` | solo `sri_ambiente` | OK |
| SAAS-02 | Backup | `npm run db:backup:rehearsal` | — | PASS (filas y checksums iguales) | PASS | OK |
| SAAS-02 | Restore | Guarda de nombre (`assertDisposableName`) | — | rechaza nombres sin `gim_test_` | rechaza | OK |
| SAAS-02 | Harness | `npm run test:integration` | — | 5/5 | 5/5 | OK |
| SAAS-02 | Backend | `npx jest` | — | sin regresión | 6/6 | OK |
| SAAS-02 | Backend | `npm run test:e2e` (base desechable) | `GET /api/health` y otros | sin regresión | 2/2 | OK |
| SAAS-02 | Backend | `npm run build` | — | OK | OK | OK |
| SAAS-02 | Backend | ESLint sin `--fix` | — | sin problemas nuevos | 1 error y 3 warnings preexistentes | OK (preexistente) |
| SAAS-02 | CI | `backend-ci.yml` parseo YAML | — | válido, sin secretos ni deploy | válido | OK |
| SAAS-02 | CI | Ejecución en GitHub | — | verde | — | Pendiente (requiere push) |
| SAAS-02 | Limpieza | `SHOW DATABASES LIKE 'gim\_test\_%'` | — | vacío | vacío | OK |

### SAAS-03 — Fundación multi-tenant de la base de datos

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|---|
| SAAS-03 | Prisma | `npx prisma validate` + `prisma generate` | — | válido | válido | OK |
| SAAS-03 | Migraciones | `npm run db:validate-migrations` | — | 0001 + 0002 en base vacía, sin drift inesperado, 0 tenants | 25 tablas, 44 FK, 16 únicos, 42 triggers; solo `sri_ambiente` | OK |
| SAAS-03 | Inmutabilidad | `UPDATE tenant_id` A→B en las 21 tablas (casos A–F) | — | rechazado; filas conservan tenant A | 26/26 integración (incluye A–F) | OK |
| SAAS-03 | Inmutabilidad | UPDATE de otras columnas (socios, planes, productos, ventas, comprobantes) | — | permitido | PASS | OK |
| SAAS-03 | Fail-closed | tenant_id inexistente (raíz e hijo) | — | rechazado | PASS | OK |
| SAAS-03 | Triggers | conteo post-0002 | — | 42 (21 BI + 21 BU) | 42 | OK |
| SAAS-03 | Backfill | `npm run db:tenant:rehearsal` (fixtures) | — | filas preservadas, 1 tenant, memberships = usuarios, 0 violaciones | PASS | OK |
| SAAS-03 | Backfill | `npm run db:tenant:rehearsal -- --from-backup` (copia real) | — | ídem sobre datos reales | PASS (588 filas, 23 memberships) | OK |
| SAAS-03 | Fail-fast | Escenario negativo (2 filas `configuracion`) | — | preflight bloquea y el guard aborta el deploy | bloquea y aborta | OK |
| SAAS-03 | Compatibilidad | Insert legado sin `tenant_id` (1 tenant) | — | raíz → tenant inicial; hijo → tenant del padre | OK | OK |
| SAAS-03 | Backup | `npm run db:backup:rehearsal` | — | PASS con triggers | PASS | OK |
| SAAS-03 | Integridad | `npm run test:integration` (pruebas 1–10 + A/B) | — | 17/17 | 17/17 | OK |
| SAAS-03 | A/B | Mismo `dni` en tenants A y B / duplicado en A | — | permitido / rechazado | permitido / rechazado | OK |
| SAAS-03 | A/B | FK cruzada (`asistencias`, `productos`, `suscripciones`) | — | rechazada | rechazada | OK |
| SAAS-03 | Backend | `npx jest` | — | sin regresión | 6/6 | OK |
| SAAS-03 | Backend | `npm run test:e2e` (base desechable 0001 + 0002) | `GET /api`, `GET /api/health` | sin regresión | 2/2 | OK |
| SAAS-03 | Backend | `npm run build` | — | OK | OK | OK |
| SAAS-03 | Backend | ESLint sin `--fix` | — | sin problemas nuevos | 1 error y 3 warnings preexistentes | OK (preexistente) |
| SAAS-03 | Seguridad | `ec_gym_system` (solo lectura en `information_schema`) | — | sin `tenants`, sin `tenant_id`, sin triggers | intacta | OK |
| SAAS-03 | CI | Ejecución en GitHub con el paso multi-tenant | — | verde | — | Pendiente (requiere push) |
| SAAS-03 | Login/asistencia | Login de socio por DNI y validación de acceso por DNI (manual) | `POST /api/auth/member/login`, `POST /api/attendance/validate`, `POST /api/attendance/scan` | sin cambios | — | Pendiente manual |
| SAAS-03 | Limpieza | Bases `gim_test_%` | — | ninguna | ninguna | OK |
