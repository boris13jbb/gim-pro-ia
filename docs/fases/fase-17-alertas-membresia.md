# Fase 17 — Alertas proactivas de membresía por vencer

**Estado:** APROBADA Y CERRADA (2026-10-01)

**Fecha implementación:** 2026-07-07  
**Fecha validación API:** 2026-10-01  
**Fecha validación manual F17-09:** 2026-10-01  
**Rama:** `fix/fase-17-membership-alerts-dates`  
**Commit técnico:** `c4c5adc` — `fix: validate and stabilize membership alerts`

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

## Defectos corregidos en validación

### F17-D01 — Fechas DATE/UTC (ALTO) — PASS

**Problema:** columnas Prisma/MySQL `@db.Date` llegan como medianoche UTC; en Ecuador (UTC-5) desplazaban umbrales (p. ej. 3→2, 8→7).

**Corrección:** `fromPrismaDate()` + `localCalendarAsUtcDate()` en `backend-nest/src/common/utils/date.util.ts`, usados por `membership-alerts.service.ts`.

### F17-D02 — Race / idempotencia (MEDIO) — PASS

**Problema:** `notifyMember` fire-and-forget podía permitir carrera en `run` consecutivos.

**Corrección:** `await notifyMember()` en el job; `RealtimeService.notifyMember` retorna `Promise<void>`.

## Matriz final de validación

| ID | Prueba | Resultado |
|----|--------|-----------|
| F17-01 | Endpoint normal | PASS |
| F17-02 | Sin candidatos nuevos | PASS |
| F17-03 | Próxima a vencer | PASS |
| F17-04 | Membresía vencida | PASS |
| F17-05 | Idempotencia | PASS |
| F17-06 | No autenticado (401) | PASS |
| F17-07 | Sin permisos (403) | PASS |
| F17-08 | Fechas límite | PASS |
| F17-09 | Campana Flutter (manual) | PASS |
| F17-10 | Aislamiento A/B | PASS |
| F17-11 | Tests automatizados | PASS |
| F17-12 | Regresión | PASS |
| F17-13 | Build | PASS |

## F17-09 — Campana Flutter (validación manual)

**Estado:** PASS  
**Tipo:** Validación manual  
**Fecha:** 2026-10-01

### Entorno

- Flutter Web: `flutter run -d chrome --web-port=8888`
- URL: `http://localhost:8888`
- Backend: `http://127.0.0.1:3000`
- Branch: `fix/fase-17-membership-alerts-dates` @ `c4c5adc`

### Socio de prueba

- Nombre: Socio F17 Campana UI
- Login/DNI: `F17UI9001`
- Plan: Mensual Básico
- Fecha fin: `04/10/2026`
- Días restantes: 3

### Evidencia visual

| Ítem | Resultado |
|------|-----------|
| Badge visible | PASS — número 1 (antes de abrir) |
| Título | PASS — `Membresía por vencer` |
| Contenido | PASS |
| Nombre del plan | PASS — Mensual Básico |
| Días mostrados | PASS — 3 días |
| Icono | PASS — `Icons.event_busy` |
| Hora | PASS — 13:44 |
| Marcado como leído | PASS — badge a 0; `read_at` en BD |
| Persistencia | PASS — tras reload sigue 1 alerta |
| Duplicación | PASS — sin segunda alerta idéntica |
| Aislamiento | cubierto por F17-10 automatizado |

### Runs del job (escenario UI)

```text
Run #1: HTTP 201 — sent=1 skipped=0 expired=0
Run #2: HTTP 201 — sent=0 skipped=1 expired=0
```

Texto observado en campana:

> Membresía por vencer  
> Tu membresía del plan "Mensual Básico" vence en 3 días. Acércate a recepción para renovar.

## Pruebas automáticas / script

```bash
cd backend-nest
npm run build && npm run lint && npm test -- --passWithNoTests
npm run audit:phase-17   # requiere API en :3000
```

Validación API 2026-10-01: **18/18 PASS**.

## Rollback

1. Quitar `MembershipAlertsModule` y `ScheduleModule` de `app.module.ts`
2. Eliminar carpeta `src/membership-alerts/`
3. Revertir helpers DATE en `date.util.ts` si no se usan
4. Revertir `notifyMember` Promise si se desea el comportamiento previo
5. Revertir commit `c4c5adc` / rama `fix/fase-17-membership-alerts-dates`

## Resumen de cierre

| Criterio | Estado |
|----------|--------|
| Implementación revisada | OK |
| Endpoint probado | OK |
| Reglas / fechas / duplicados | OK (tras F17-D01/D02) |
| Auth / authz / aislamiento | OK |
| Tests automatizados / build | OK |
| Campana Flutter visual (F17-09) | PASS |
| Aprobación formal | APROBADA Y CERRADA |

**FASE 17 — APROBADA Y CERRADA**
