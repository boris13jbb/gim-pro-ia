# Fase 14 — Extensiones Flutter staff (post-Fase 13)

**Estado:** Completada — aprobada por usuario (continuar a Fase 15)

**Fecha inicio:** 2026-07-07

## Objetivo de la fase

Completar funcionalidades staff pendientes tras Fase 13: exportación de reportes financieros y módulo de coaching (progreso físico + rutinas) para admin/entrenador.

## Alcance por slices

| Slice | Contenido | Estado |
|:-----:|-----------|:------:|
| 1 | Exportar reportes Excel/PDF desde `/staff/reports` | ✅ Implementado |
| 2 | Progreso y rutinas por socio (coaching staff) | ✅ Implementado |

## Módulos NestJS reutilizados

Sin cambios en backend: `reports` (export), `body-progress`, `workout-routines`.

## Pantallas Flutter

### Slice 1

| Pantalla | Ruta / acción | API |
|----------|---------------|-----|
| Reportes — exportar | Botones en `/staff/reports` | `GET /reports/financial/export/excel`, `GET /reports/financial/export/pdf` |

### Slice 2

| Pantalla | Ruta | API |
|----------|------|-----|
| Coaching socio | `/staff/members/:id/coaching` | `GET /body-progress/members/:id/measurements`, `GET /workout-routines/members/:id/current` |
| Nueva medida | `/staff/members/:id/coaching/measurement/new` | `POST /body-progress/members/:id/measurements` |
| Asignar rutina | `/staff/members/:id/coaching/routine/new` | `POST /workout-routines/members/:id` |

## Archivos creados

### Slice 1
- `frontend-flutter/lib/core/models/downloaded_file.dart`
- `frontend-flutter/lib/utils/file_export_helper.dart`

### Slice 2
- `frontend-flutter/lib/features/staff/coaching/staff_member_coaching_page.dart`
- `frontend-flutter/lib/features/staff/coaching/staff_add_measurement_page.dart`
- `frontend-flutter/lib/features/staff/coaching/staff_assign_routine_page.dart`

## Archivos modificados

### Slice 1
- `api_client.dart` (`downloadFile`, `deleteData`)
- `reports_service.dart` (export Excel/PDF)
- `staff_reports_page.dart` (botones exportar)
- `pubspec.yaml` (`path_provider`, `share_plus`)

### Slice 2
- `staff_permissions.dart` (`canViewCoaching`, `canManageCoaching`)
- `body_progress_service.dart`, `workout_service.dart` (métodos staff)
- `staff_member_detail_page.dart` (atajo coaching)
- `staff_home_page.dart` (entrenador → socios)
- `app_router.dart` (rutas coaching)

## Reglas de negocio

- Exportación solo admin (403 en API para otros roles).
- Ver progreso/rutina: admin, recepcionista, entrenador.
- Registrar medidas / asignar rutina: admin, entrenador.
- Eliminar medida: admin, entrenador (con confirmación en UI).
- Cada rutina nueva conserva historial (INSERT en backend).

## Pruebas realizadas

- `flutter analyze` → 0 errores
- `flutter test` → 1/1 OK
- Pendiente manual: exportar y compartir archivos; coaching en dispositivo.

## Estado final de la fase

Lista para prueba manual. **Solicitar aprobación antes de nuevas fases.**
