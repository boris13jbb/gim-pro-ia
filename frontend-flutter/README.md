# Iron Gym — App Flutter (Socios + Staff)

App móvil para socios y staff del gimnasio. Consume la API NestJS con JWT; no contiene lógica de negocio crítica.

## Requisitos

- Flutter 3.44+ / Dart 3.12+
- API NestJS en ejecución (`backend-nest/`)
- MySQL/MariaDB activo (backend requiere BD)

## Instalación

```bash
cd frontend-flutter
flutter pub get
```

## Configuración API

Por defecto:

| Plataforma | URL base |
|------------|----------|
| Windows / Web / iOS sim | `http://localhost:3000/api` |
| Android emulador | `http://10.0.2.2:3000/api` |

**Dispositivo físico Android:** `10.0.2.2` no funciona. Usa la IP de tu PC en la misma red Wi‑Fi:

```bash
# Opción rápida (IP en dart_defines.physical_device.json)
flutter run --dart-define-from-file=dart_defines.physical_device.json

# O desde la raíz del repo:
..\scripts\run-android-physical.ps1

# Manual:
flutter run --dart-define=API_HOST=192.168.x.x
```

Verifica tu IP con `ipconfig` (IPv4 de Wi‑Fi). Teléfono y PC deben estar en la **misma red**.

## Ejecutar

```bash
# Backend (otra terminal)
cd ../backend-nest
npm run start:dev

# App
cd frontend-flutter
flutter run -d windows
# o: flutter run -d chrome
# o: flutter run   (Android/iOS)
```

## APK para compartir (ngrok)

Para generar APKs livianas por arquitectura apuntando al túnel ngrok activo:

```powershell
# Terminal 1: backend
cd backend-nest
npm run start:dev

# Terminal 2: túnel público
ngrok http 3000

# Terminal 3: build APK (desde la raíz del repo)
.\scripts\build-apk-ngrok.ps1
```

Salida en `apk-dist/`:
- `IronGym-ngrok-arm64.apk` — celulares modernos (recomendada)
- `IronGym-ngrok-arm32.apk` — celulares antiguos 32 bits
- `IronGym-ngrok-x86_64.apk` — emulador Android en PC

Opciones:

```powershell
.\scripts\build-apk-ngrok.ps1 -UniversalApk          # incluye APK universal (~71 MB)
.\scripts\build-apk-ngrok.ps1 -ApiUrl "https://..."  # URL manual si ngrok no expone API local
.\scripts\build-apk-ngrok.ps1 -SkipHealthCheck       # omite verificación de puerto/health
```

## Flutter Web con ngrok

### Desarrollo local (Chrome en tu PC, API por ngrok)

```powershell
.\scripts\web-ngrok.ps1
```

Abre `http://localhost:8080` y usa la API pública de ngrok.

### Compartir web por internet (cualquier navegador)

```powershell
# Terminal 1 — Backend
cd backend-nest
npm run start:dev

# Terminal 2 — Túneles API (3000) + Web (8080)
.\scripts\start-ngrok-gym.ps1

# Terminal 3 — Build + servidor web local
.\scripts\web-ngrok.ps1 -Mode Share
```

Luego:
1. Reinicia el backend si el script actualizó `CORS_ORIGINS`.
2. Abre la URL **web** que muestra ngrok (puerto 8080).
3. La API pública es la URL **api** de ngrok (puerto 3000).

Salida estática en `web-dist/` si necesitas desplegar en otro hosting.

### Comando manual (sin script)

```powershell
cd frontend-flutter
flutter run -d chrome --web-port=8080 --dart-define=API_BASE_URL=https://TU-URL.ngrok-free.app/api
```

Build para producción:

```powershell
flutter build web --dart-define=API_BASE_URL=https://TU-URL.ngrok-free.app/api
```

## Funcionalidades (Fases 10–11 — Socios)

- Login socio (`POST /auth/member/login`)
- Perfil (`GET /auth/me`)
- Membresía (`GET /members/me/membership`)
- Carnet QR (`GET /qr-access/me/card`)
- Progreso físico con gráficos (`GET /body-progress/me`)
- Rutina actual (`GET /workout-routines/me/current`)
- Registrar asistencia (`POST /attendance/self`)
- Asistente IA: chat REST + **streaming por WebSocket** (`/ai`)
- **Notificaciones en tiempo real** (`/events`) + **historial persistido** (`GET /notifications`): asistencia y membresía, campana con sincronización de leídas
- Tema claro/oscuro/sistema (persistente)
- Refresh token automático en 401
- Tokens en `flutter_secure_storage`

## Funcionalidades (Fase 13 — Staff, slices 1–5)

- Login dual en `/login`: selector **Socio** | **Staff**
- Staff usa `POST /auth/login` (admin, recepcionista, entrenador)
- Panel staff en `/staff/home` con perfil y KPI asistencias de hoy (`GET /attendance/today`)
- Navegación inferior staff: **Inicio** | **Socios** | **Asistencias**
- Listado de socios con búsqueda y paginación (`GET /members`)
- Detalle de socio con membresía vigente e historial
- Alta de socio (`POST /members`) — admin y recepcionista
- Asignar membresía/plan (`POST /memberships` + `GET /plans`) — admin y recepcionista
- **Asistencias staff** (`/staff/attendance`): escaneo QR, validar/registrar DNI, listado del día
- **POS y caja** (`/staff/pos`): abrir/cerrar caja, venta con carrito, descuento, métodos de pago
- **Reportes financieros** (`/staff/reports`, solo admin): KPIs, movimientos y **exportación Excel/PDF**
- **Usuarios staff** (`/staff/users`, solo admin): listar, crear y activar/desactivar usuarios
- **Coaching** (desde detalle de socio): progreso físico y rutina — admin/entrenador gestionan; recepcionista solo lectura
- **Facturación SRI** (`/staff/sri`): bandeja, detalle, emitir desde venta/membresía, PDF/XML, email, reintento y nota de crédito
- Rutas protegidas: socio no entra a `/staff/*`; staff no usa shell de socio (IA, carnet, WS `/events`)

## Estructura

```text
lib/
  core/         # config, models, theme
  services/     # API, auth, dominio
  providers/    # estado (Provider)
  features/     # pantallas por módulo (auth, member, staff, ...)
  routes/       # go_router
  widgets/      # componentes reutilizables
```

## Pruebas

```bash
flutter analyze
flutter test
```

## Credenciales de prueba

**Socio:** cuenta con contraseña de app móvil (`PATCH /members/:id/password` desde recepción/admin).

**Staff:** usuarios del sistema (`admin@gym.com`, recepcionista, entrenador) con contraseña configurada en BD.
