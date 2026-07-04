# IA, Gemini y WebSockets

**Estado:** Slice 1 (REST + chat Flutter), Slice 2 (WebSockets: streaming del chat IA) y Slice 3 (WebSockets: notificaciones en tiempo real al socio) implementados — pendiente prueba manual con `GEMINI_API_KEY` y aprobación

## Objetivo de la fase

Asistente IA para socios vía **Flutter → NestJS → Gemini → servicios internos → BD**. Sin API keys en cliente. Streaming por WebSockets en slice posterior.

## Archivos PHP analizados

N/A — funcionalidad nueva. Referencia de datos: módulos socio, asistencias, medidas, rutinas (PHP legacy).

## Tablas involucradas

| Tabla | Uso |
|-------|-----|
| `ai_conversations` | Conversaciones por socio |
| `ai_messages` | Mensajes user/assistant con metadata |
| `socios`, `suscripciones`, `asistencias`, `medidas`, `rutinas` | Contexto vía herramientas internas |

## Reglas de negocio detectadas

- Solo rol **socio** accede a `/api/ai/*`
- `memberId` desde JWT; sin acceso cruzado entre socios
- La IA usa **solo datos reales** inyectados por `AiToolsService` (no inventar)
- Sin diagnósticos médicos ni medicamentos
- `GEMINI_API_KEY` solo en servidor
- Límite diario de mensajes (`AI_DAILY_MESSAGE_LIMIT`, default 50)
- Rate limit en chat (`AI_THROTTLE_LIMIT`)

## Nuevos módulos NestJS creados

| Archivo | Descripción |
|---------|-------------|
| `ai-assistant/ai-assistant.module.ts` | Módulo principal |
| `ai-assistant/ai-chat.controller.ts` | REST `/ai/chat`, `/ai/conversations` |
| `ai-assistant/ai-chat.service.ts` | Persistencia y orquestación |
| `ai-assistant/gemini.service.ts` | SDK `@google/generative-ai` |
| `ai-assistant/ai-tools.service.ts` | Contexto: perfil, membresía, asistencias, progreso, rutina |
| `scripts/audit-phase-11.mjs` | Auditoría API |

## Herramientas internas (contexto)

- `getMemberProfile`
- `getMembershipStatus`
- `getAttendanceSummary`
- `getBodyProgress`
- `getCurrentWorkoutRoutine`

## Nuevas pantallas o funcionalidades Flutter

| Archivo | Descripción |
|---------|-------------|
| `features/ai/ai_chat_page.dart` | Chat con asistente (6ª pestaña) |
| `services/ai_service.dart` | Cliente REST `/ai/*` |
| `features/shell/app_shell.dart` | Tab "Asistente" |
| `routes/app_router.dart` | Ruta `/assistant` |

## Endpoints creados

| Método | Ruta | Rol | Descripción |
|--------|------|-----|-------------|
| GET | `/ai/conversations` | socio | Lista conversaciones |
| GET | `/ai/conversations/:id` | socio | Detalle con mensajes |
| POST | `/ai/chat` | socio | Enviar mensaje (REST) |

## Variables de entorno

```env
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
AI_DAILY_MESSAGE_LIMIT=50
AI_MAX_HISTORY_MESSAGES=12
AI_THROTTLE_LIMIT=20
AI_THROTTLE_TTL_MS=60000
```

## Cambios de base de datos

- Tablas `ai_conversations`, `ai_messages`
- Enum `ai_message_role` (user, assistant, system)
- `prisma db push` aplicado

## Pruebas realizadas

- `npm run build` OK
- `npm run audit:phase-11` → **6/6 OK** (sin `GEMINI_API_KEY` → 503 esperado)
- `flutter analyze` OK
- `flutter test` → 1/1 OK

## Botones probados

| Acción | Estado |
|--------|--------|
| Tab Asistente en navegación | Implementado — prueba manual pendiente |
| Enviar mensaje chat | Implementado — requiere `GEMINI_API_KEY` en servidor |

## Errores encontrados

- `TooManyRequestsException` no exportado en NestJS 11 → `HttpException` 429
- Prisma client sin regenerar tras schema → `npm run db:generate`

## Soluciones aplicadas

Correcciones anteriores; build y auditoría OK.

## Slice 2 — WebSockets (streaming del chat IA)

**Namespace:** `/ai` (socket.io). Autenticación por JWT en el handshake (`auth.token`), reutilizando `JWT_ACCESS_SECRET`. Solo socios activos; cada socket entra a la sala `member:{memberId}` (aislamiento por socio).

**Eventos:**

| Dirección | Evento | Payload |
|-----------|--------|---------|
| Cliente → Servidor | `ai.message` | `{ message: string, conversationId?: number }` |
| Servidor → Cliente | `ai.response.chunk` | `{ delta: string }` |
| Servidor → Cliente | `ai.response.done` | `{ conversationId: number, message: {...} }` |
| Servidor → Cliente | `ai.error` | `{ message: string }` |

**Backend (nuevos/modificados):**

| Archivo | Descripción |
|---------|-------------|
| `websocket/websocket.module.ts` | Módulo WS (reutiliza `AiChatService` y `MembersService`) |
| `websocket/ai-chat.gateway.ts` | Gateway `/ai`: auth handshake, salas, streaming |
| `websocket/ws-auth.service.ts` | Verifica JWT del socket y valida socio activo |
| `ai-assistant/gemini.service.ts` | `generateReplyStream()` (usa `sendMessageStream`) |
| `ai-assistant/ai-chat.service.ts` | `streamMessage()` reutilizando la lógica REST (límite diario, propiedad, persistencia) |

**Flutter (nuevos/modificados):**

| Archivo | Descripción |
|---------|-------------|
| `services/ai_socket_service.dart` | Cliente socket.io del asistente (streaming) |
| `features/ai/ai_chat_page.dart` | Respuesta token a token; respaldo REST si el socket no conecta |
| `core/config/api_config.dart` | `socketBaseUrl` (API sin `/api`) |
| `app.dart` | Provider de `AiSocketService` |

**Dependencias:** backend `@nestjs/websockets`, `@nestjs/platform-socket.io`, `socket.io`; Flutter `socket_io_client`.

**Sin cambios de BD.** Reutiliza `ai_conversations`/`ai_messages`.

## Slice 3 — WebSockets (notificaciones en tiempo real al socio)

**Namespace:** `/events` (socket.io). Mismo handshake JWT que `/ai` (reutiliza `WsAuthService`, ahora en `WsAuthModule`). Cada socket entra a la sala `member:{memberId}`; las notificaciones se emiten solo a esa sala (aislamiento por socio).

**Evento del servidor al cliente:** `notification`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `type` | string | `attendance.registered` \| `membership.updated` |
| `title` | string | Título corto del aviso |
| `body` | string | Detalle del aviso |
| `data` | objeto | Datos del evento (ids, fechas, estado) |
| `createdAt` | string ISO | Momento de emisión (lo agrega el servidor) |

Evento de error de handshake: `notification.error` `{ message }` (antes de desconectar).

**Origen de los eventos (acciones de dominio reales):**

| Acción backend | Evento emitido |
|----------------|----------------|
| `AttendanceService.register()` (check-in) | `attendance.registered` |
| `MembershipsService.create()` | `membership.updated` (activada) |
| `MembershipsService.cancel()` | `membership.updated` (cancelada) |

**Backend (nuevos/modificados):**

| Archivo | Descripción |
|---------|-------------|
| `websocket/realtime.gateway.ts` | Gateway `/events`: auth handshake, salas, `emitToMember` |
| `websocket/realtime.service.ts` | `notifyMember()` — API que usan los servicios de dominio |
| `websocket/realtime.module.ts` | Módulo (exporta `RealtimeService`; sin ciclos) |
| `websocket/ws-auth.module.ts` | `WsAuthService` reutilizable por ambos gateways |
| `websocket/ws-token.util.ts` | Extracción de token + helpers tipados de `socket.data` |
| `websocket/types/realtime-notification.type.ts` | Tipo `RealtimeNotification` |
| `attendance/*`, `memberships/*` | Emiten notificaciones tras persistir |
| `app.module.ts` | Registra `RealtimeModule` |

**Flutter (nuevos/modificados):**

| Archivo | Descripción |
|---------|-------------|
| `core/models/app_notification.dart` | Modelo de la notificación recibida |
| `services/realtime_notifications_service.dart` | Cliente socket.io `/events` (ChangeNotifier: lista + no leídas) |
| `features/shell/app_shell.dart` | SnackBar por evento + campana con badge + panel de historial |
| `app.dart` | Provider `RealtimeNotificationsService` |

**Regla:** notificar nunca rompe el flujo de negocio; se emite después de guardar y los errores se ignoran (registrados en log).

**Sin cambios de BD ni nuevas variables de entorno.** Reutiliza `CORS_ORIGINS` y `JWT_ACCESS_SECRET`.

## Pendientes (futuro)

- `saveAiNote` como herramienta explícita
- Persistir notificaciones (tabla `notifications`) para historial entre sesiones
- Alertas proactivas de membresía por vencer (job programado)
- Voz (futuro)

## Cómo hacer rollback

1. Eliminar `AiAssistantModule` de `app.module.ts`
2. Eliminar carpeta `src/ai-assistant/`
3. DROP tablas `ai_messages`, `ai_conversations` (con respaldo)
4. Revertir pestaña Asistente en Flutter

## Estado final de la fase

**Slice 1:** REST + persistencia + chat Flutter — operativo con `GEMINI_API_KEY`.  
**Pendiente:** WebSockets, prueba manual con Gemini real, aprobación del usuario.
