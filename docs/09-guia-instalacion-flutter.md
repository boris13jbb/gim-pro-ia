# Guía de Instalación — Flutter

**Estado:** Fase 10 — slice 1 operativo

## Requisitos

- Flutter SDK estable 3.44+
- Android Studio / VS Code con extensiones Flutter
- API NestJS corriendo (ver `docs/08-guia-instalacion-backend.md`)

## Pasos

1. Clonar o abrir el monorepo `gim_pro_ia`
2. Levantar backend:

```bash
cd backend-nest
cp .env.example .env
npm install
npx prisma migrate deploy
npm run start:dev
```

3. Instalar dependencias Flutter:

```bash
cd frontend-flutter
flutter pub get
```

4. Ejecutar app:

```bash
flutter run -d windows
```

## Variables

| Variable | Descripción |
|----------|-------------|
| `API_BASE_URL` (dart-define) | URL base API, ej. `http://192.168.1.10:3000/api` |

## Android

- `usesCleartextTraffic=true` habilitado para desarrollo HTTP local
- En producción usar HTTPS y quitar cleartext

## Documentación relacionada

- `frontend-flutter/README.md`
- `docs/fases/fase-10-flutter-app-cliente.md`
- `docs/07-endpoints-api.md`
