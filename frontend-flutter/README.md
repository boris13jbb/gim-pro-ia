# Iron Gym — App Flutter (Socios)

App móvil para socios del gimnasio. Consume la API NestJS con JWT; no contiene lógica de negocio crítica.

## Requisitos

- Flutter 3.44+ / Dart 3.12+
- API NestJS en ejecución (`backend-nest/`)

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

Sobrescribir (dispositivo físico en la misma red):

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.100:3000/api
```

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

## Funcionalidades (Fase 10 — slice 1)

- Login socio (`POST /auth/member/login`)
- Perfil (`GET /auth/me`)
- Membresía (`GET /members/me/membership`)
- Carnet QR (`GET /qr-access/me/card`)
- Progreso físico (`GET /body-progress/me`)
- Rutina actual (`GET /workout-routines/me/current`)
- Registrar asistencia (`POST /attendance/self`)
- Refresh token automático en 401
- Tokens en `flutter_secure_storage`

## Estructura

```text
lib/
  core/         # config, models, theme
  services/     # API, auth, dominio
  providers/    # estado (Provider)
  features/     # pantallas por módulo
  routes/       # go_router
  widgets/      # componentes reutilizables
```

## Pruebas

```bash
flutter analyze
flutter test
```

## Credenciales de prueba

Usar un socio creado en el sistema con contraseña de app móvil (recepción/admin: `PATCH /members/:id/password`).
