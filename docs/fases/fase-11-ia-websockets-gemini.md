# IA, Gemini y WebSockets

**Estado:** Slice 1 implementado (REST + chat Flutter) — pendiente aprobación para cerrar slice 1 / avanzar a WebSockets

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

## Pendientes (slice 2+)

- WebSocket gateway (`ai.message`, `ai.response.chunk`)
- `saveAiNote` como herramienta explícita
- Streaming en Flutter
- Voz (futuro)

## Cómo hacer rollback

1. Eliminar `AiAssistantModule` de `app.module.ts`
2. Eliminar carpeta `src/ai-assistant/`
3. DROP tablas `ai_messages`, `ai_conversations` (con respaldo)
4. Revertir pestaña Asistente en Flutter

## Estado final de la fase

**Slice 1:** REST + persistencia + chat Flutter — operativo con `GEMINI_API_KEY`.  
**Pendiente:** WebSockets, prueba manual con Gemini real, aprobación del usuario.
