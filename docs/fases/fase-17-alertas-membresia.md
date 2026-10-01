# Fase 17 — Alertas proactivas de membresía por vencer

**Estado:** Validada en backend/API (2026-10-01) — pendiente confirmación visual campana Flutter y aprobación formal de cierre

**Fecha implementación:** 2026-07-07  
**Fecha validación:** 2026-10-01

## Objetivo

Notificar automáticamente a los socios cuando su membresía activa está por vencer (7, 3, 1 y 0 días), usando el mismo flujo de Fase 16: persistencia en `notifications` + WebSocket `/events`.

## Backend NestJS

### Dependencia

- `@nestjs/schedule` — job cron diario

### Módulo `membership-alerts/`

| Archivo | Responsabilidad |
|---------|-----------------|
| `membership-alerts.service.ts` | Busca membresías activas, calcula días restantes, evita duplicados |
| `membership-alerts.scheduler.ts` | Cron diario (default 08:00) |
| `membership-alerts.controller.ts` | `POST /membership-alerts/run` (admin, prueba manual) |
| `membership-alerts.config.ts` | Parseo de variables de entorno |

### Contrato real — `POST /api/membership-alerts/run`

| Campo | Valor |
|-------|--------|
| Method | `POST` |
| Path | `/api/membership-alerts/run` |
| Auth | JWT Bearer obligatorio |
| Authorization | Solo rol `admin` (`403` si otro rol) |
| Body | vacío |
| Response 201 (wrapper proyecto) | `{ sent, skipped, expired }` |
| Sin token | `401` |
| Side effects | Inserta filas en `notifications`; puede marcar `suscripciones.estado=vencida` |

### Reglas de negocio (verificadas)

1. Solo membresías con `estado = activa` y socio activo
2. Umbrales configurables: `MEMBERSHIP_ALERT_DAYS` (default **`7,3,1,0`** — valor real en código/config)
3. Cada alerta usa `alertKey` en `data` para no repetir el mismo aviso el mismo día
4. El job también marca como `vencida` membresías con `fecha_fin` **anterior a hoy** (calendario local) y notifica `membership.updated`
5. Tipo por vencer: `membership.expiring`

### Corrección 2026-10-01 (ALTO)

Prisma/MySQL `@db.Date` llega como medianoche UTC. En Ecuador (UTC-5) eso desplazaba umbrales.

**Fix:** `fromPrismaDate()` + `localCalendarAsUtcDate()` en `date.util.ts`, usados por el job. Además `await notifyMember()` para idempotencia.

### Variables de entorno (`.env.example`)

```env
MEMBERSHIP_ALERTS_ENABLED=true
MEMBERSHIP_ALERTS_CRON=0 8 * * *
MEMBERSHIP_ALERT_DAYS=7,3,1,0
```

## Flutter (socio)

- Icono `membership.expiring` en campana (`Icons.event_busy`)
- Reutiliza historial persistido (`GET /notifications`) y WebSocket `/events`
- Aislamiento: `memberId` solo desde JWT (validado en API)

## Pruebas automáticas / script

```bash
cd backend-nest
npm run build && npm run lint && npm test -- --passWithNoTests
npm run audit:phase-17   # requiere API en :3000
```

Validación 2026-10-01: **18/18 PASS** (auth, roles, umbrales, vencidas, idempotencia, aislamiento).

## Prueba manual UI (campana)

1. Admin: `POST /api/membership-alerts/run` (o script) con membresía a 3 días
2. Login socio → campana con badge → listado “Membresía por vencer”
3. Segundo `run` mismo día → sin duplicado
4. Membresía vencida ayer → “Membresía vencida”

## Rollback

1. Quitar `MembershipAlertsModule` y `ScheduleModule` de `app.module.ts`
2. Eliminar carpeta `src/membership-alerts/`
3. Revertir helpers DATE en `date.util.ts` si no se usan
4. Revertir `notifyMember` Promise si se desea el comportamiento previo

## Resumen de cierre (pendiente aprobación usuario)

| Criterio | Estado |
|----------|--------|
| Implementación revisada | OK |
| Endpoint probado | OK |
| Reglas / fechas / duplicados | OK (tras fix) |
| Auth / authz / aislamiento | OK |
| Tests automatizados / build | OK |
| Campana Flutter visual | Pendiente evidencia manual |
| Aprobación formal | Pendiente usuario |
