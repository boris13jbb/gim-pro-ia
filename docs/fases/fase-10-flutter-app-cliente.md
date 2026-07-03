# Fase 10 — Flutter app cliente (socio)

**Estado:** Aprobada (2026-07-03) — slices 1–3 completados

## Objetivo de la fase

App Flutter para socios: login JWT, perfil, membresía, carnet QR, progreso físico, rutina, asistencias e historial. Solo consume API NestJS; sin lógica de negocio en cliente.

## Archivos PHP analizados

N/A — fase de frontend. Referencia: flujos socio en PHP legacy (carnet, consultas).

## Tablas involucradas

Lectura vía API: `socios`, `suscripciones`, `planes`, `medidas`, `rutinas`, `asistencias`.

## Reglas de negocio detectadas

- Socio solo accede a **su propio** `memberId` (JWT + `assertMemberResourceAccess` en backend)
- Membresía y acceso calculados en NestJS; Flutter solo muestra
- QR del carnet = DNI del socio (paridad PHP)
- Asistencia propia solo con membresía vigente (`POST /attendance/self`)
- Historial de asistencias del mes (`GET /attendance/me`)
- Tokens en almacenamiento seguro; refresh automático en 401

## Módulos NestJS (complemento Fase 10)

| Archivo | Descripción |
|---------|-------------|
| `members/member-self.controller.ts` | `GET /members/me/membership`, `GET /members/me/memberships` |
| `qr-access.controller.ts` | `GET /qr-access/me/card` (socio) |
| `attendance.controller.ts` | `GET /attendance/me`, `POST /attendance/self` |
| `main.ts` | Escucha en `0.0.0.0` para acceso LAN desde dispositivo físico |

## Pantallas Flutter

| Pantalla | Ruta | API |
|----------|------|-----|
| Login | `/login` | `POST /auth/member/login` |
| Inicio + membresía + asistencia + resumen mes | `/home` | `GET /members/me/membership`, `GET /attendance/me`, `POST /attendance/self` |
| Carnet QR | `/qr` | `GET /qr-access/me/card` |
| Progreso físico | `/progress` | `GET /body-progress/me` |
| Rutina | `/workout` | `GET /workout-routines/me/current` |
| Perfil + historial membresías + logout | `/profile` | `GET /auth/me`, `GET /members/me/memberships`, `POST /auth/logout` |

## Endpoints backend (socio)

| Método | Ruta | Rol |
|--------|------|-----|
| GET | `/api/members/me/membership` | socio |
| GET | `/api/members/me/memberships` | socio |
| GET | `/api/qr-access/me/card` | socio |
| GET | `/api/attendance/me` | socio |
| POST | `/api/attendance/self` | socio |
| GET | `/api/body-progress/me` | socio |
| GET | `/api/workout-routines/me/current` | socio |

## Cambios de base de datos

Ninguno.

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| `npm run build` (backend) | OK |
| `npm run audit:phase-10` | **13/13 OK** |
| `flutter analyze` | OK (solo info/warnings menores) |
| `flutter test` | 1/1 OK |

## Slice 2 — mejoras

- Resumen de asistencias del mes en pantalla Inicio
- Historial de membresías en Perfil
- `GET /attendance/me` en backend
- API escucha en `0.0.0.0` para red local (dispositivo físico)
- Mensajes de error de conexión con hint de `API_BASE_URL`

## Ejecución en dispositivo físico

```bash
cd frontend-flutter
flutter run --dart-define=API_BASE_URL=http://IP_DE_TU_PC:3000/api
```

Backend debe estar activo (`npm run start:dev`) en la misma red.

## Slice 3 — UX y navegación

- `AppBar` con título por pestaña en `AppShell`
- Pantalla `AttendanceHistoryPage` (`/attendance-history`) con historial completo del mes
- Enlace “Ver historial” desde Inicio
- Login muestra URL de API activa (útil en web/dispositivo)

## Pendientes (futuro)

- Push notifications
- Modo offline / caché
- Icono y splash personalizados
- UI staff/admin (fases posteriores)

## Cómo hacer rollback

- Backend: revertir `GET attendance/me` y `0.0.0.0` en main.ts
- Flutter: eliminar carpeta `frontend-flutter/`

## Estado final de la fase

**Slice 1 + 2 completados** — app socio funcional en red local. Requiere aprobación para cerrar Fase 10 o continuar slice 3.
