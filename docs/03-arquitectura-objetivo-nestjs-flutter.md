# Arquitectura Objetivo — NestJS + Flutter

**Estado:** Actualizado (Fases 01–02 completadas)

## Principios

- Lógica de negocio 100% en NestJS
- Flutter solo consume API REST + WebSockets
- PHP permanece como legacy de referencia
- Misma BD MySQL central (`ec_gym_system`) en fases iniciales
- Prisma como ORM preferido

## Estructura objetivo

```
gim_pro_ia/
├── gym-system/          # Legacy PHP (no modificar sin aprobación)
├── backend-nest/        # API NestJS (Fase 01+)
├── frontend-flutter/    # App socio (Fase 10+)
└── docs/                # Documentación migración
```

## Flujo de datos

```
Flutter App  ──HTTP/JWT──►  NestJS API  ──Prisma──►  MySQL
                │                │
                └── WebSocket ───┘──► Gemini (Fase 11, solo backend)
```

Ver reglas del proyecto en `.cursor/rules/`.
