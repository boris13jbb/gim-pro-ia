# Fase 16 — Notificaciones persistentes (socio)

**Estado:** ✅ Aprobada (2026-07-07)

**Fecha:** 2026-07-07

## Objetivo

Persistir las notificaciones del socio en BD y exponerlas por REST, complementando WebSocket `/events`. El historial sobrevive al cierre de la app y a desconexiones.

## Cambios de base de datos

Nueva tabla `notifications` (Prisma):

| Columna | Tipo | Uso |
|---------|------|-----|
| `id` | INT PK | Identificador |
| `member_id` | INT FK → `socios` | Dueño |
| `type` | VARCHAR(60) | ej. `attendance.registered` |
| `title` | VARCHAR(160) | Título |
| `body` | VARCHAR(500) | Mensaje |
| `data` | JSON | Metadata opcional |
| `read_at` | DATETIME NULL | Marca de lectura |
| `created_at` / `updated_at` | DATETIME | Auditoría |

**Aplicar en BD:** `npm run db:push:legacy` (desde `backend-nest/`).

## Backend NestJS

- Módulo `notifications/` con CRUD de lectura/marcado para socio
- `RealtimeService` persiste antes de emitir por WebSocket
- Endpoints: `GET /notifications`, `GET /notifications/unread-count`, `PATCH /notifications/read-all`, `PATCH /notifications/:id/read`

## Flutter

- `NotificationsService` consume API REST
- `RealtimeNotificationsService.loadPersisted()` al iniciar shell socio
- `markAllReadPersisted()` sincroniza lectura con servidor
- Modelo `AppNotification` con `id` e `isRead`

## Pruebas

- `npm run build` + `npm run lint` → OK
- `flutter analyze` → 0 errores
- `flutter test` → 1/1 OK
- Pendiente: `db:push:legacy` + prueba manual socio

## Rollback

1. Revertir código backend/Flutter Fase 16
2. `DROP TABLE notifications` (solo si no hay datos productivos que conservar)

## Pendiente post-Fase 16

- ~~Alertas proactivas de membresía por vencer (cron/scheduler)~~ → **Fase 17**

## Estado final

Lista para migración BD y prueba manual.
