# Gym Pro IA — Sistema de Gimnasio

Migración del sistema PHP MVC (`gym-system/`) hacia **NestJS** (backend) + **Flutter** (frontend).

## Estructura

- `backend-nest/` — API NestJS con JWT, Prisma y módulos de negocio
- `gym-system/` — Sistema PHP legacy (solo local, excluido del repositorio)
- `docs/` — Documentación de migración por fases

## Requisitos

- Node.js 20+
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

## Estado de migración

Consultar `docs/04-plan-migracion-fases.md` y `docs/05-bitacora-migracion.md`.
