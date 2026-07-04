# Gym Pro IA — Sistema de Gimnasio

Migración del sistema PHP MVC (`gym-system/`) hacia **NestJS** (backend) + **Flutter** (frontend).

## Estructura

- `backend-nest/` — API NestJS con JWT, Prisma, IA (Gemini) y WebSockets
- `frontend-flutter/` — App Flutter para socios (chat IA, notificaciones en tiempo real)
- `gym-system/` — Sistema PHP legacy (solo local, excluido del repositorio)
- `docs/` — Documentación de migración por fases

## Requisitos

- Node.js 20+
- Flutter 3.44+ (app socio)
- MySQL/MariaDB
- PHP 8+ (solo para legacy)

## Inicio rápido (backend)

```bash
cd backend-nest
cp .env.example .env
npm install
npx prisma migrate deploy
npm run start:dev
```

Ver `docs/08-guia-instalacion-backend.md` para detalles completos.

## Inicio rápido (Flutter — socios)

```bash
cd frontend-flutter
flutter pub get
flutter run -d windows
```

Ver `docs/09-guia-instalacion-flutter.md`. Requiere API en `http://localhost:3000`.

## Comandos de ejecución

Ver **`docs/13-comandos-ejecucion.md`** — referencia completa (instalación, backend, Flutter, auditorías, Git).

## Estado de migración

Fases 00–11 implementadas (backend completo + app de socios + IA/WebSockets); Fase 12 (cierre) en curso, pendiente de aprobación final. Detalle en `docs/04-plan-migracion-fases.md`, `docs/fases/fase-12-cierre-migracion.md` y `docs/05-bitacora-migracion.md`.
