# Fase 17 — Alertas proactivas de membresía por vencer

**Estado:** Implementada — pendiente aprobación y prueba manual

**Fecha:** 2026-07-07

## Objetivo

Notificar automáticamente a los socios cuando su membresía activa está por vencer (7, 3, 1 y 0 días), usando el mismo flujo de Fase 16: persistencia en `notifications` + WebSocket `/events`.

## Backend NestJS

### Dependencia nueva

- `@nestjs/schedule` — job cron diario

### Módulo `membership-alerts/`

| Archivo | Responsabilidad |
|---------|-----------------|
| `membership-alerts.service.ts` | Busca membresías activas, calcula días restantes, evita duplicados |
| `membership-alerts.scheduler.ts` | Cron diario (default 08:00) |
| `membership-alerts.controller.ts` | `POST /membership-alerts/run` (admin, prueba manual) |
| `membership-alerts.config.ts` | Parseo de variables de entorno |

### Reglas de negocio

1. Solo membresías con `estado = activa` y socio activo
2. Umbrales configurables: `MEMBERSHIP_ALERT_DAYS` (default `7,3,1,0`)
3. Cada alerta usa `alertKey` en `data` para no repetir el mismo aviso el mismo día
4. El job también marca como `vencida` membresías con `fecha_fin` pasada y notifica al socio
5. Tipo de notificación: `membership.expiring` (por vencer) o `membership.updated` (ya vencida)

### Variables de entorno (`.env.example`)

```env
MEMBERSHIP_ALERTS_ENABLED=true
MEMBERSHIP_ALERTS_CRON=0 8 * * *
MEMBERSHIP_ALERT_DAYS=7,3,1,0
```

## Flutter (socio)

- Icono `membership.expiring` en campana (`Icons.event_busy`)
- Sin cambios de API cliente: reutiliza historial persistido y WebSocket

## Endpoints

| Método | Ruta | Rol | Uso |
|--------|------|-----|-----|
| POST | `/membership-alerts/run` | admin | Disparo manual del job (pruebas) |

## Pruebas automáticas

- `npm run build` + `npm run lint` → OK
- `flutter analyze` → 0 errores (avisos de estilo previos)
- `flutter test` → 1/1 OK

## Prueba manual sugerida

1. Crear o ajustar una membresía activa con `fecha_fin` = hoy + 3 días
2. Login admin → `POST /api/membership-alerts/run` (Swagger o curl)
3. Login socio → campana debe mostrar “Membresía por vencer”
4. Repetir `run` el mismo día → no debe duplicar (`skipped` > 0 en respuesta)
5. Ajustar `fecha_fin` a ayer → `run` marca vencida y notifica “Membresía vencida”

## Rollback

1. Quitar `MembershipAlertsModule` y `ScheduleModule` de `app.module.ts`
2. Eliminar carpeta `src/membership-alerts/`
3. Desinstalar `@nestjs/schedule` si no se usa en otro módulo
4. Revertir cambios en `notifications.service.ts` y tipo `membership.expiring`

## Estado final

Lista para prueba manual con `POST /membership-alerts/run`.
