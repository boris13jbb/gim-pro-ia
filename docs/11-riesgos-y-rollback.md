# Riesgos y Rollback

## Riesgos identificados (Fase 00)

| ID | Riesgo | Impacto | Mitigación |
|---|---|---|---|
| R01 | Bug caja E01 produce cierre incorrecto | Financiero | Corregir en NestJS Fase 07; no confiar en totales PHP actuales |
| R02 | Migración SRI rompe flujo fiscal | Legal/Fiscal | Fase 09 al final; ambiente pruebas; comparar con PHP |
| R03 | Pérdida datos en mantenimiento PHP | Crítico | No usar restaurar/limpiar en producción sin backup |
| R04 | Credenciales hardcodeadas | Seguridad | `.env` en NestJS; no commitear secretos |
| R05 | Dos sistemas escribiendo misma BD | Integridad | Fase por fase; un módulo a la vez; transacciones |
| R06 | Stock negativo | Inventario | Validación transaccional en NestJS |

## Plan de rollback general

1. Mantener PHP operativo sin modificaciones
2. Backup BD antes de cada fase: `mysqldump ec_gym_system > backup_fase_XX.sql`
3. Si NestJS falla: desactivar endpoints nuevos; PHP sigue operando
4. Migraciones Prisma: mantener script `down` documentado por fase
5. No eliminar tablas/columnas PHP hasta Fase 12

## Rollback Fase 00

No aplica — solo documentación, sin cambios en código.

## Rollback Fase 01

1. Detener proceso NestJS
2. Eliminar carpeta `backend-nest/` si se desea revertir por completo
3. PHP y BD legacy siguen operativos sin cambios

## Rollback Fase 02

1. Detener NestJS
2. Revocar tokens: `UPDATE auth_refresh_tokens SET revokedAt = NOW() WHERE revokedAt IS NULL;`
3. Opcional: `DROP TABLE auth_refresh_tokens;`
4. Eliminar módulos `src/auth/` y `src/users/` si se revierte código
5. PHP con sesiones sigue funcionando en paralelo

## Riesgos Fase 01–02 (mitigados)

| ID | Riesgo | Mitigación aplicada | Estado |
|----|--------|---------------------|--------|
| R07 | Rate limit login bloquea pruebas | `THROTTLE_LOGIN_LIMIT` configurable (default 30/min); script auditoría reutiliza tokens | ✅ Mitigado |
| R08 | Drift Prisma en BD importada | Usar `npm run db:push:legacy` (nunca `migrate reset`) | ✅ Mitigado |
| R09 | Secretos JWT placeholder en producción | `validateEnvOnBootstrap()` bloquea arranque si `NODE_ENV=production` y secretos inseguros | ✅ Mitigado |
| R10 | Access expirado sin prueba automática | `audit-phase-02.mjs` firma JWT expirado y valida 401 | ✅ Mitigado |
| R11 | Fase 03 en código sin cierre formal | Documentado en `fase-03` como **pendiente aprobación**; no bloquea cierre 00–02 | ✅ Documentado |

## BD legacy + Prisma (R08)

**Prohibido** en BD importada desde PHP: `prisma migrate reset` o `migrate dev` si pide reset.

**Flujo seguro para tablas nuevas NestJS:**

```bash
cd backend-nest
npm run db:push:legacy
npm run db:generate
```

Solo añade/ajusta tablas del schema sin borrar datos legacy.

## Rollback Fase 06 — movimientos_inventario

```sql
DROP TABLE IF EXISTS movimientos_inventario;
```

Revertir código: eliminar módulos `inventory/`, `cash-registers/`, `sales/` y relaciones en `schema.prisma`, luego `npm run db:generate`.

## Rollback Fases 07–11 (consolidado)

Cada fase es reversible sin afectar al legacy PHP. Detalle en cada `docs/fases/fase-XX-*.md`.

| Fase | Rollback resumido |
|:----:|-------------------|
| 07 POS/caja | Eliminar módulos `sales/`, `cash-registers/`; las ventas quedan sin registrar por NestJS (PHP intacto). |
| 08 Reportes | Solo lectura/exportación; eliminar módulo `reports/` sin impacto en datos. |
| 09 SRI | Usar solo ambiente de pruebas; no autorizar en producción hasta validar; eliminar `billing-sri/` revierte la emisión. |
| 10 Flutter | La app solo consume API; desinstalar/omitir el build no afecta backend ni BD. |
| 11 IA/WebSockets | Quitar `AiAssistantModule`, `WebsocketModule` y `RealtimeModule` de `app.module.ts`; opcional `DROP TABLE ai_messages, ai_conversations;` (con respaldo). El resto de la API sigue operando. |

## Riesgos tiempo real (Fase 11)

| ID | Riesgo | Mitigación | Estado |
|----|--------|------------|--------|
| R12 | Socket expone datos de otro socio | Sala `member:{id}` + JWT en handshake; `memberId` desde token firmado | ✅ Mitigado |
| R13 | Fallo de notificación rompe negocio | `RealtimeService` emite tras persistir y captura errores (no propaga) | ✅ Mitigado |
| R14 | API key Gemini filtrada | Solo en servidor; nunca en Flutter; validación de entorno | ✅ Mitigado |

## Cierre (Fase 12)

- No se elimina PHP legacy hasta período de operación en paralelo validado.
- Antes de producción: secretos JWT únicos, `NODE_ENV=production` (bloquea secretos inseguros), HTTPS, backups programados y `SWAGGER_ENABLED=false` si aplica.
- Rollback global: mantener PHP operativo y desactivar la API NestJS; la BD `ec_gym_system` no sufre cambios destructivos por la migración.
