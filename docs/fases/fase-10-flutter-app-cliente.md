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

## Slice 4 — Sistema visual global (Material 3)

- `AppTheme.light()` reescrito como sistema visual único (colores de marca sobre `ColorScheme.fromSeed`, tipografía con jerarquía y estilos de componentes: AppBar, Card, inputs, botones, `NavigationBar`, `Chip`, `ListTile`, `SnackBar`, `Dialog`, `BottomSheet`, `Divider`, `FAB`, `ProgressIndicator`).
- Grises fijos reemplazados por tokens del tema (`onSurfaceVariant`) en `state_views.dart`, `membership_status_card.dart` y `login_page.dart`.
- El rediseño se hereda automáticamente en todas las pantallas vía `Theme.of(context)`; no se modificó lógica, navegación ni servicios.
- Documentación de referencia consultada con Context7 (`/flutter/website`, theming Material 3).
- Se conservan a propósito: verde/naranja de estado y fondo blanco del carnet QR (legibilidad del escáner).

## Slice 5 — Modo oscuro (Material 3)

- `AppTheme` refactorizado a un generador único `_buildTheme(colorScheme, scaffoldBackground)` que produce `light()` y `dark()` sin duplicar estilos.
- `app.dart`: `darkTheme: AppTheme.dark()` + `themeMode: ThemeMode.system` (respeta la preferencia del SO).
- Primeros planos con tokens adaptativos (`colorScheme.primary`); colores de marca fijos solo donde siempre son legibles (AppBar, botón primario, navegación, SnackBar, FAB).
- `qr_card_page.dart`: nuevo widget `_AccessChip` (verde/naranja translúcido + texto `onSurface`) legible en claro y oscuro; el QR se mantiene sobre fondo blanco.
- Pendiente menor: revisar contraste de números de ejes en gráficas `fl_chart` en modo oscuro.

## Slice 6 — Selector manual de tema en Perfil

- Nuevo `ThemeProvider` (estado) + `ThemeStorage` (persistencia con `flutter_secure_storage`, clave `app_theme_mode`).
- `app.dart`: carga la preferencia al iniciar y aplica `themeMode` vía `Consumer<ThemeProvider>`; temas cacheados.
- Perfil: tarjeta "Apariencia" con `SegmentedButton` (Sistema / Claro / Oscuro). La UI solo lee/dispara; la lógica vive en el provider.
- La preferencia persiste entre reinicios; por defecto sigue el sistema.

## Slice 7 — Rediseño estilo fitness oscuro (acento naranja)

- `AppTheme` rediseñado a estética fitness oscura (estilo FITFINITY): fondo casi negro neutro, tarjetas redondeadas, botones tipo píldora, AppBar integrada al fondo, tipografía marcada y acento naranja/ámbar (`#F5A524`).
- Oscuro como tema por defecto (primer arranque e inicial del provider); claro/sistema siguen disponibles en el selector de Perfil.
- Un único `_buildTheme` genera ambos temas (sin duplicar). Spinners dentro del botón naranja en tono oscuro para contraste.
- Sin cambios de lógica, navegación ni dependencias.

## Slice 8 — Gráficas de progreso adaptadas al tema

- `_MetricChart` (`fl_chart`) con rejilla, bordes y ejes basados en tokens del tema (legibles en claro y oscuro).
- Línea de "Peso" en acento naranja (`AppTheme.accent`) y "% Grasa" en celeste (`#4FC3F7`); relleno sutil bajo la curva.

## Slice 9 — Icono y splash de marca

- Logo de marca (mancuerna naranja `#F5A524` sobre `#0E0F13`) en `assets/branding/` (`icon.png` completo y `logo_mark.png` transparente).
- Icono generado con `flutter_launcher_icons` (Android con icono adaptativo, iOS, Web, Windows).
- Splash generado con `flutter_native_splash` (fondo oscuro + logo centrado, con modo noche y Android 12+).
- Login usa el logo de marca en lugar del ícono genérico.
- Regeneración: `dart run flutter_launcher_icons` y `dart run flutter_native_splash:create`.
- Nota: requiere detener `flutter run` y reinstalar para ver icono/splash.

## Pendientes (futuro)

- Push notifications
- Modo offline / caché
- UI staff/admin (fases posteriores)

## Cómo hacer rollback

- Backend: revertir `GET attendance/me` y `0.0.0.0` en main.ts
- Flutter: eliminar carpeta `frontend-flutter/`

## Estado final de la fase

**Slice 1 + 2 completados** — app socio funcional en red local. Requiere aprobación para cerrar Fase 10 o continuar slice 3.
