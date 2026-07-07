# Fase 13 — Flutter app staff (post-cierre)

**Estado:** Completada — aprobada por usuario (continuar a Fase 14)

**Fecha inicio:** 2026-07-07

## Objetivo de la fase

Extender la app Flutter para **staff** (admin, recepcionista, entrenador), consumiendo la API NestJS existente. La app de socios (Fase 10) permanece intacta; login unificado con selector Socio/Staff.

## Alcance por slices

| Slice | Contenido | Estado |
|:-----:|-----------|:------:|
| 1 | Login staff, rutas protegidas, panel inicial + KPI asistencias hoy | ✅ Implementado |
| 2 | Socios/membresías (listado, alta básica, asignar plan) | ✅ Implementado |
| 3 | Asistencias staff (validar/registrar DNI/QR, listado hoy) | ✅ Implementado |
| 4 | POS/caja (recepcionista/admin) | ✅ Implementado |
| 5 | Reportes y usuarios (admin) | ✅ Implementado |

## Archivos PHP analizados

N/A — frontend. Referencia: módulos admin PHP legacy.

## Tablas involucradas

Lectura vía API existente: `usuarios`, `asistencias`, etc. Sin cambios de BD en slice 1.

## Reglas de negocio detectadas

- Staff usa `POST /auth/login` (email + contraseña); socio usa `POST /auth/member/login`.
- Mismo refresh/logout (`/auth/refresh`, `/auth/logout`).
- Rutas protegidas por rol en backend; Flutter solo navega según `userType` y `rol`.
- Staff **no** accede al shell de socio (IA, carnet, notificaciones `/events`).
- Socio **no** accede a rutas `/staff/*`.
- Asistencia staff: validar membresía vigente en backend antes de registrar (E02).
- QR del carnet codifica el DNI (compatible PHP).
- Evitar duplicados: un ingreso por socio por día (409 si repite).

## Módulos NestJS reutilizados

Sin cambios en backend slice 1–2: `auth`, `attendance`, `members`, `memberships`, `plans`.

## Pantallas Flutter

### Slice 1

| Pantalla | Ruta | API |
|----------|------|-----|
| Login dual Socio/Staff | `/login` | `POST /auth/member/login` o `POST /auth/login` |
| Panel staff | `/staff/home` | `GET /auth/me`, `GET /attendance/today` |

### Slice 2

| Pantalla | Ruta | API |
|----------|------|-----|
| Listado socios | `/staff/members` | `GET /members` |
| Detalle socio | `/staff/members/:id` | `GET /members/:id`, `GET /members/:id/membership`, `GET /members/:id/memberships` |
| Alta socio | `/staff/members/new` | `POST /members` (admin/recepcionista) |
| Asignar membresía | `/staff/members/:id/membership/new` | `GET /plans`, `POST /memberships` |

### Slice 3

| Pantalla | Ruta | API |
|----------|------|-----|
| Asistencias (QR / DNI / Hoy) | `/staff/attendance` | `POST /attendance/validate`, `POST /attendance/register`, `POST /attendance/scan`, `GET /attendance/today` |

### Slice 4

| Pantalla | Ruta | API |
|----------|------|-----|
| POS y caja | `/staff/pos` | `GET/POST /cash-registers/*`, `GET /products/active`, `POST /sales`, `GET /sales` |

### Slice 5

| Pantalla | Ruta | API |
|----------|------|-----|
| Reportes financieros | `/staff/reports` | `GET /reports/financial/summary`, `GET /reports/financial/movements` |
| Usuarios staff | `/staff/users` | `GET /users`, `PATCH /users/:id/status` |
| Alta usuario staff | `/staff/users/new` | `POST /users` |

## Archivos creados

### Slice 1
- `frontend-flutter/lib/core/models/auth_user.dart`
- `frontend-flutter/lib/features/staff/shell/staff_shell.dart`
- `frontend-flutter/lib/features/staff/home/staff_home_page.dart`

### Slice 2
- `frontend-flutter/lib/core/models/paged_members.dart`
- `frontend-flutter/lib/core/models/plan.dart`
- `frontend-flutter/lib/core/staff_permissions.dart`
- `frontend-flutter/lib/services/plan_service.dart`
- `frontend-flutter/lib/services/membership_service.dart`
- `frontend-flutter/lib/widgets/membership_status_chip.dart`
- `frontend-flutter/lib/features/staff/members/staff_members_page.dart`
- `frontend-flutter/lib/features/staff/members/staff_member_detail_page.dart`
- `frontend-flutter/lib/features/staff/members/staff_create_member_page.dart`
- `frontend-flutter/lib/features/staff/members/staff_create_membership_page.dart`

### Slice 3
- `frontend-flutter/lib/core/models/attendance_staff.dart`
- `frontend-flutter/lib/features/staff/attendance/staff_attendance_page.dart`
- `frontend-flutter/lib/features/staff/attendance/staff_attendance_panels.dart`

### Slice 4
- `frontend-flutter/lib/core/models/product.dart`
- `frontend-flutter/lib/core/models/cash_register.dart`
- `frontend-flutter/lib/core/models/sale.dart`
- `frontend-flutter/lib/services/product_service.dart`
- `frontend-flutter/lib/services/cash_register_service.dart`
- `frontend-flutter/lib/services/sales_service.dart`
- `frontend-flutter/lib/features/staff/pos/staff_pos_page.dart`
- `frontend-flutter/lib/features/staff/pos/staff_pos_panels.dart`

### Slice 5
- `frontend-flutter/lib/core/models/financial_report.dart`
- `frontend-flutter/lib/core/models/staff_user.dart`
- `frontend-flutter/lib/services/reports_service.dart`
- `frontend-flutter/lib/services/staff_users_service.dart`
- `frontend-flutter/lib/features/staff/reports/staff_reports_page.dart`
- `frontend-flutter/lib/features/staff/users/staff_users_page.dart`
- `frontend-flutter/lib/features/staff/users/staff_create_user_page.dart`

## Archivos modificados

### Slice 1
- `auth_service.dart`, `auth_provider.dart`, `login_page.dart`, `app_router.dart`
- `attendance_service.dart` (`fetchTodayCount`)

### Slice 2
- `member_service.dart` (métodos staff)
- `staff_shell.dart` (navegación inferior Inicio/Socios)
- `staff_home_page.dart` (atajo a socios)
- `app_router.dart`, `app.dart`

### Slice 3
- `attendance_service.dart` (validate, register, scan, listado hoy)
- `staff_shell.dart` (pestaña Asistencias)
- `staff_home_page.dart` (atajo a asistencias)
- `pubspec.yaml` (`mobile_scanner`)
- `android/.../AndroidManifest.xml` (permiso cámara)
- `ios/Runner/Info.plist` (NSCameraUsageDescription)

### Slice 4
- `staff_permissions.dart` (`canUsePos`)
- `staff_home_page.dart` (atajo POS)
- `app_router.dart`, `app.dart`

### Slice 5
- `staff_permissions.dart` (`canManageAdminModules`)
- `api_client.dart` (`patchData`)
- `staff_home_page.dart` (atajos Reportes y Usuarios solo admin)
- `app_router.dart`, `app.dart` (providers `ReportsService`, `StaffUsersService`)

## Pruebas realizadas

- `flutter analyze` → 0 errores (slice 1–5)
- `flutter test` → 1/1 OK
- Pendiente manual: reportes KPI/movimientos, alta usuario, activar/desactivar usuario.

## Botones probados

- Pendiente manual: Reportes desde inicio admin, filtro fechas, listado movimientos.
- Pendiente manual: Usuarios → nuevo usuario → activar/desactivar con switch.

## Errores encontrados

Ninguno en implementación (pendiente prueba manual slice 5).

## Pendientes

- Prueba manual completa Fase 13 en dispositivo (slices 1–5).
- Exportación Excel/PDF de reportes (endpoints existen en API; no implementado en Flutter aún).
- Módulo entrenador: rutinas y progreso físico desde staff (fuera de alcance slice 5).

## Cómo hacer rollback

Revertir archivos Flutter de la fase 13 por slice; el backend no cambió en esta fase.

## Estado final de la fase

**Fase 13 completa (slices 1–5).** Lista para prueba manual integral con cuenta admin. **Solicitar aprobación antes de nuevas fases o commits.**
