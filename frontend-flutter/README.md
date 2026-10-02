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

## Configuración API (Fase 18 — LAN primero)

REST y WebSocket (`/events`, `/ai`) usan el **mismo host** vía `ApiConfig`
(`API_BASE_URL` o `API_HOST`). No mezclar LAN + ngrok.

| Plataforma | Default |
|------------|----------|
| Windows / Web / iOS sim | `http://localhost:3000/api` |
| Android emulador | `http://10.0.2.2:3000/api` |
| Android / Web en LAN | `--dart-define=API_BASE_URL=http://IP-LAN:3000/api` |

### Flujo principal: LAN (sin ngrok)

Desde la raíz del repo:

```powershell
# Detecta IP Wi‑Fi, verifica health e imprime comandos
.\scripts\run-lan-dev.ps1

# Backend (otra terminal si no está activo)
cd backend-nest
npm run start:dev

# Flutter Web en :8888 contra API LAN
.\scripts\run-lan-dev.ps1 -StartWeb

# Android físico (misma Wi‑Fi)
.\scripts\run-android-physical.ps1 -PcIp <IP-LAN>
# o:
cd frontend-flutter
flutter run --dart-define=API_BASE_URL=http://<IP-LAN>:3000/api
```

También válido:

```bash
flutter run --dart-define=API_HOST=<IP-LAN>
flutter run --dart-define-from-file=dart_defines.physical_device.json
```

`dart_defines.physical_device.json` es local (gitignored). Teléfono y PC en la **misma Wi‑Fi**.

## Ejecutar (local puro)

```bash
# Backend (otra terminal)
cd ../backend-nest
npm run start:dev

# App
cd frontend-flutter
flutter run -d windows
# o: flutter run -d chrome --web-port=8888
# o: flutter run   (Android/iOS)
```

## APK release sin ngrok (recomendado)

```powershell
# Backend activo en LAN
cd backend-nest
npm run start:dev

# Desde la raíz — sustituye <IP-LAN>
.\scripts\build-apk-release.ps1 -ApiBaseUrl "http://<IP-LAN>:3000/api"
```

Salida en `apk-dist/`:
- `IronGym-lan-arm64.apk` — celulares modernos
- `IronGym-lan-arm32.apk` — 32 bits
- `IronGym-lan-x86_64.apk` — emulador

## Cloudflare Tunnel (opcional, URL estable)

Requiere `cloudflared` + token creado en el dashboard (no se crea desde el repo):

```powershell
$env:CLOUDFLARE_TUNNEL_TOKEN = '<token>'
.\scripts\start-cloudflare-tunnel.ps1
# Luego:
flutter run --dart-define=API_BASE_URL=https://tu-hostname/api
```

Sin token: el script reporta `PENDIENTE DE CONFIGURACIÓN EXTERNA`. LAN sigue siendo válido.

## ngrok (LEGACY / opcional)

Solo si aún necesitas el flujo antiguo. Preferir LAN o Cloudflare Tunnel.

```powershell
.\scripts\start-ngrok-gym.ps1
.\scripts\build-apk-ngrok.ps1
.\scripts\web-ngrok.ps1
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
