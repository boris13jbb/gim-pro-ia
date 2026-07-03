# Fase 10 — Flutter app cliente (socio)

**Estado:** Slice 1 implementado — pendiente aprobación

## Objetivo de la fase

App Flutter para socios: login JWT, perfil, membresía, carnet QR, progreso físico, rutina y registro de asistencia. Solo consume API NestJS; sin lógica de negocio en cliente.

## Archivos PHP analizados

N/A — fase de frontend. Referencia: flujos socio en PHP legacy (carnet, consultas).

## Tablas involucradas

Lectura vía API: `socios`, `suscripciones`, `planes`, `medidas`, `rutinas`, `asistencias`.

## Reglas de negocio detectadas

- Socio solo accede a **su propio** `memberId` (JWT + `assertMemberResourceAccess` en backend)
- Membresía y acceso calculados en NestJS; Flutter solo muestra
- QR del carnet = DNI del socio (paridad PHP)
- Asistencia propia solo con membresía vigente (`POST /attendance/self`)
- Tokens en almacenamiento seguro; refresh automático en 401

## Nuevos módulos NestJS creados (complemento Fase 10)

| Archivo | Descripción |
|---------|-------------|
| `members/member-self.controller.ts` | `GET /members/me/membership`, `GET /members/me/memberships` |
| `qr-access.controller.ts` | `GET /qr-access/me/card` (socio) |

## Nuevas pantallas o funcionalidades Flutter

| Pantalla | Ruta | API |
|----------|------|-----|
| Login | `/login` | `POST /auth/member/login` |
| Inicio + membresía + asistencia | `/home` | `GET /members/me/membership`, `POST /attendance/self` |
| Carnet QR | `/qr` | `GET /qr-access/me/card` |
| Progreso físico | `/progress` | `GET /body-progress/me` |
| Rutina | `/workout` | `GET /workout-routines/me/current` |
| Perfil + logout | `/profile` | `GET /auth/me`, `POST /auth/logout` |

## Endpoints creados (backend complemento)

| Método | Ruta | Rol |
|--------|------|-----|
| GET | `/api/members/me/membership` | socio |
| GET | `/api/members/me/memberships` | socio |
| GET | `/api/qr-access/me/card` | socio |

## Cambios de base de datos

Ninguno.

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| `npm run build` (backend) | OK |
| `npm run audit:phase-10` | 11/11 OK |
| `flutter analyze` | OK (solo info/warnings menores) |
| `flutter test` | 1/1 OK |

## Botones probados

| Pantalla | Acción | Estado |
|----------|--------|--------|
| Login | Iniciar sesión | Pendiente prueba manual UI |
| Inicio | Registrar asistencia | Pendiente prueba manual UI |
| Perfil | Cerrar sesión | Pendiente prueba manual UI |
| Navegación inferior | 5 tabs | Pendiente prueba manual UI |

## Errores encontrados

- Endpoints de membresía y carnet QR no existían para rol `socio` → creados en slice 1

## Soluciones aplicadas

- `MemberSelfController` con rutas `me/*` antes del controller staff
- `GET /qr-access/me/card` con `memberId` desde JWT
- App Flutter con Provider + go_router + dio + flutter_secure_storage

## Pendientes (slice 2+)

- UI staff/admin en Flutter (opcional, fases posteriores)
- Push notifications
- Modo offline / caché
- Pruebas widget/integration más amplias
- Icono y splash personalizados

## Cómo hacer rollback

- Backend: eliminar `member-self.controller.ts` y ruta `me/card` en qr-access
- Flutter: eliminar carpeta `frontend-flutter/`

## Estado final de la fase

**Slice 1 completado** — app socio funcional contra API local. Requiere aprobación del usuario para cerrar Fase 10 o continuar slice 2.
