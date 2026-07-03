# Socios y membresías

**Estado:** Completada — pendiente aprobación para Fase 04

## Objetivo de la fase

Migrar gestión de **socios**, **planes** y **suscripciones/membresías** del PHP legacy a NestJS, con cálculo de estado de membresía en backend.

## Archivos PHP analizados

- `gym-system/app/controllers/SociosController.php`
- `gym-system/app/models/Socio.php`
- `gym-system/app/controllers/PlanesController.php`
- `gym-system/app/models/Plan.php`
- `gym-system/app/controllers/SuscripcionesController.php`
- `gym-system/app/models/Suscripcion.php`

## Tablas involucradas

- `socios`
- `planes`
- `suscripciones`

Sin cambios de esquema en tablas legacy (solo lectura/escritura vía Prisma).

## Reglas de negocio detectadas

- Socio: DNI único, estados `activo`/`inactivo`/`pendiente`
- Plan: solo admin CRUD; recepcionista lee para crear membresías
- Suscripción: `fecha_fin = fecha_inicio + plan.duracion_dias`
- Cancelar en PHP → `estado = vencida` (no borrar historial)
- Entrenador: acceso socios, **sin** suscripciones
- Membresía vigente: `estado activa` + `fecha_fin >= hoy` + socio activo
- Backend calcula `effectiveStatus` y sincroniza `activa` expirada → `vencida`

## Nuevos módulos NestJS creados

- `MembersModule` — CRUD socios + membresía calculada
- `PlansModule` — CRUD planes (admin) + lectura (recepcionista)
- `MembershipsModule` — listar, crear, cancelar membresías
- `membership-status.util.ts` — cálculo centralizado de estado

## Nuevas pantallas o funcionalidades Flutter

Ninguna (API lista para consumo).

## Endpoints creados

| Método | Ruta | Roles |
|--------|------|-------|
| GET | `/api/members` | admin, recepcionista, entrenador |
| GET | `/api/members/:id` | admin, recepcionista, entrenador |
| GET | `/api/members/:id/membership` | admin, recepcionista, entrenador |
| GET | `/api/members/:id/memberships` | admin, recepcionista, entrenador |
| POST | `/api/members` | admin, recepcionista |
| PATCH | `/api/members/:id` | admin, recepcionista |
| PATCH | `/api/members/:id/status` | admin, recepcionista |
| GET | `/api/plans` | admin, recepcionista |
| GET | `/api/plans/:id` | admin, recepcionista |
| POST | `/api/plans` | admin |
| PATCH | `/api/plans/:id` | admin |
| PATCH | `/api/plans/:id/status` | admin |
| GET | `/api/memberships` | admin, recepcionista |
| GET | `/api/memberships/:id` | admin, recepcionista |
| POST | `/api/memberships` | admin, recepcionista |
| PATCH | `/api/members/:id/password` | admin, recepcionista |
| POST | `/api/members/:id/photo` | admin, recepcionista |
| DELETE | `/api/members/:id/photo` | admin, recepcionista |
| GET | `/api/memberships/export/excel` | admin, recepcionista |
| POST | `/api/auth/member/login` | público |

## Cambios de base de datos

| Tabla | Cambio | Motivo |
|-------|--------|--------|
| `socios` | Columna `password` (nullable) | Login app móvil socio |
| `auth_refresh_tokens` | Columna `memberId` (nullable), `userId` nullable | Refresh tokens de socios |

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| GET `/api/members` | OK — paginación y búsqueda |
| GET `/api/members/:id` | OK |
| GET `/api/members/:id/membership` | OK — `effectiveStatus`, `isMembershipValid` |
| GET `/api/members/:id/memberships` | OK — historial |
| POST `/api/members` | OK — DNI único (409 si duplicado) |
| PATCH `/api/members/:id` | OK |
| GET `/api/plans` | OK |
| POST `/api/plans` | OK admin / 403 recepcionista |
| POST `/api/memberships` | OK — `endDate` calculada por `duracionDias` |
| PATCH `/api/memberships/:id/cancel` | OK — estado `vencida` |
| Entrenador GET `/members` | OK |
| Entrenador GET `/memberships` | 403 |
| `npm run build` | OK |
| `npm run audit:phase-03` | **26/26 OK** |

## Botones probados

N/A (API).

## Errores encontrados

- N/A en implementación

## Soluciones aplicadas

- Upload de foto diferido (campo `foto` opcional string; multipart en fase posterior)
- Estados `cancelada`/`suspendida` preparados en util; BD legacy usa `vencida` al cancelar

## Pendientes

Ninguno — resueltos en cierre Fase 03:

- ~~Upload multipart de foto socio~~ → `POST /members/:id/photo`
- ~~Export Excel suscripciones~~ → `GET /memberships/export/excel`
- ~~Login socio (`userType: member`)~~ → `POST /auth/member/login`

## Cómo hacer rollback

Eliminar módulos `members/`, `plans/`, `memberships/` del backend. PHP y BD intactos.

## Estado final de la fase

API de socios, planes y membresías operativa con permisos por rol y estado calculado en backend.

### Guía de prueba (Fase 03)

1. Login admin:
```bash
POST /api/auth/login
{ "email": "admin@gym.com", "password": "123456" }
```

2. Listar socios:
```bash
GET /api/members?search=camila&limit=5
Authorization: Bearer <token>
```

3. Estado membresía de un socio:
```bash
GET /api/members/3/membership
```

4. Crear membresía:
```bash
POST /api/memberships
{
  "memberId": 3,
  "planId": 1,
  "startDate": "2026-07-02"
}
```

5. Cancelar membresía:
```bash
PATCH /api/memberships/1/cancel
```

6. **Esperado:** `endDate` calculada automáticamente; `effectiveStatus` en respuestas.

7. **Login socio (app móvil):**
```bash
# Primero asignar contraseña (staff)
PATCH /api/members/3/password
{ "password": "MiClave123" }

POST /api/auth/member/login
{ "login": "1234567890", "password": "MiClave123" }
```

8. **Subir foto:**
```bash
POST /api/members/3/photo
Content-Type: multipart/form-data
photo: <archivo>
```

9. **Export Excel:**
```bash
GET /api/memberships/export/excel
Authorization: Bearer <token>
```

10. Auditoría automática:
```bash
npm run audit:phase-03
```
   Esperado: **26/26 OK**

11. Siguiente paso: aprobar **Fase 04 (Asistencias/QR)**.
