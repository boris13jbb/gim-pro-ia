# Estructura del Proyecto — Gym Pro IA

**Última actualización:** 2026-07-07  
**Estado:** Fases 00–17 implementadas; Fase 12 (cierre) en curso.

Documento de referencia con el árbol de carpetas, módulos y responsabilidades del monorepo de migración **PHP → NestJS + Flutter**.

---

## 1. Vista general del monorepo

```text
gim_pro_ia/
├── .cursor/rules/          # Reglas Cursor (migración, negocio, JWT, nombres)
├── .vscode/                # Configuración del editor
├── backend-nest/           # API NestJS — lógica de negocio central
├── frontend-flutter/       # App Flutter — socios y staff
├── gym-system/             # Sistema PHP legacy (solo referencia local)
├── docs/                   # Documentación oficial de migración
├── README.md
├── package.json
└── tsconfig.json
```

| Carpeta | Rol |
|---------|-----|
| `backend-nest/` | API REST, JWT, Prisma, SRI, IA (Gemini), WebSockets |
| `frontend-flutter/` | Cliente multiplataforma (socio + panel staff) |
| `gym-system/` | Referencia PHP MVC; no modificar sin aprobación |
| `docs/` | Diagnóstico, fases, endpoints, bitácora, guías |

---

## 2. Arquitectura y flujo de datos

```text
Flutter App  ──HTTP/JWT──►  NestJS API  ──Prisma──►  MySQL (ec_gym_system)
                │                │
                └── WebSocket ───┘──► Gemini / Z.AI / Ollama (solo backend)
```

### Principios

- Toda la **lógica de negocio** vive en NestJS.
- Flutter **solo consume** API REST y WebSockets.
- La IA **nunca** se llama desde Flutter directamente.
- Autenticación con **JWT** (access + refresh token).
- Roles: `admin`, `recepcionista`, `entrenador`, `socio`.

Ver también: `docs/03-arquitectura-objetivo-nestjs-flutter.md`.

---

## 3. Backend — `backend-nest/`

### 3.1 Raíz del backend

```text
backend-nest/
├── prisma/
│   └── schema.prisma           # Esquema Prisma (tablas legacy + nuevas)
├── scripts/                    # Auditorías por fase e import legacy
│   ├── audit-phase-02.mjs … audit-phase-11.mjs
│   ├── db-push-legacy.mjs
│   └── import-legacy-db.mjs
├── public/
│   └── sri/xml/                # XML de comprobantes (ambiente pruebas)
├── src/                        # Código fuente NestJS
├── .env.example
├── nest-cli.json
├── package.json
├── prisma.config.ts
└── README.md
```

### 3.2 Módulos en `backend-nest/src/`

```text
src/
├── main.ts
├── app.module.ts
├── app.controller.ts
├── app.service.ts
│
├── auth/                       # Login, refresh, logout, guards, estrategias JWT
├── users/                      # Usuarios del sistema (staff)
├── members/                    # Socios del gimnasio
├── plans/                      # Planes de membresía
├── memberships/                # Suscripciones / membresías
├── attendance/                 # Registro de asistencias
├── qr-access/                  # Validación y carnet QR
├── body-progress/              # Progreso físico y medidas corporales
├── workout-routines/           # Rutinas de entrenamiento
├── categories/                 # Categorías de productos
├── products/                   # Catálogo de productos
├── inventory/                  # Stock y movimientos de inventario
├── cash-registers/             # Apertura, cierre y cuadre de caja
├── sales/                      # Ventas POS y tickets
├── reports/                    # Reportes financieros y exportación
├── billing-sri/                # Facturación electrónica Ecuador (SRI)
├── ai-assistant/               # Chat IA (Gemini / Z.AI / Ollama + herramientas)
├── notifications/              # Notificaciones persistentes en BD
├── membership-alerts/          # Alertas automáticas de membresía (cron)
├── websocket/                  # Gateways WebSocket (chat IA + tiempo real)
├── subscriptions/              # Módulo auxiliar de suscripciones
│
├── database/                   # PrismaService y utilidades BD
├── health/                     # Health check (`/api/health`)
├── config/                     # Throttle, variables de entorno
└── common/                     # Filtros, interceptors, decoradores, utils
```

### 3.3 Estructura típica de un módulo NestJS

```text
nombre-modulo/
├── nombre-modulo.module.ts
├── nombre-modulo.controller.ts
├── nombre-modulo.service.ts
├── dto/                        # CreateXDto, UpdateXDto, queries
├── types/                      # Tipos internos (opcional)
├── constants/                  # Constantes de dominio (opcional)
└── services/                   # Servicios auxiliares (opcional)
```

### 3.4 Módulos registrados en `app.module.ts`

| Módulo | Dominio |
|--------|---------|
| `AuthModule` | Autenticación JWT |
| `UsersModule` | Usuarios staff |
| `MembersModule` | Socios |
| `PlansModule` | Planes |
| `MembershipsModule` | Membresías |
| `AttendanceModule` | Asistencias |
| `QrAccessModule` | QR / carnet |
| `BodyProgressModule` | Medidas corporales |
| `WorkoutRoutinesModule` | Rutinas |
| `CategoriesModule` | Categorías |
| `ProductsModule` | Productos |
| `InventoryModule` | Inventario |
| `CashRegistersModule` | Caja |
| `SalesModule` | Ventas POS |
| `ReportsModule` | Reportes |
| `BillingSriModule` | Facturación SRI |
| `AiAssistantModule` | Asistente IA |
| `WebsocketModule` | WebSockets base |
| `RealtimeModule` | Notificaciones en tiempo real |
| `NotificationsModule` | Notificaciones persistentes |
| `MembershipAlertsModule` | Alertas de vencimiento |

### 3.5 Modelos principales en Prisma (`prisma/schema.prisma`)

| Modelo Prisma | Concepto |
|---------------|----------|
| `socios` | Socio del gimnasio |
| `usuarios` | Usuario staff |
| `suscripciones` | Membresía activa/histórica |
| `planes` | Plan de membresía |
| `asistencias` | Registro de asistencia |
| `productos` | Producto |
| `categorias` | Categoría |
| `movimientos_inventario` | Entradas/salidas de stock |
| `ventas` / `detalle_ventas` | Venta POS |
| `cajas` | Sesión de caja |
| `medidas` | Medidas corporales |
| `rutinas` | Rutina de entrenamiento |
| `comprobantes_electronicos` | Factura electrónica SRI |
| `sri_series` / `sri_log` | Secuenciales y logs SRI |
| `auth_refresh_tokens` | Refresh tokens hasheados |
| `ai_conversations` / `ai_messages` | Historial chat IA |
| `notifications` | Notificaciones del usuario |

---

## 4. Frontend — `frontend-flutter/`

### 4.1 Raíz del frontend

```text
frontend-flutter/
├── lib/                        # Código Dart principal
├── android/                    # Proyecto Android
├── ios/                        # Proyecto iOS
├── windows/                    # Proyecto Windows
├── linux/                      # Proyecto Linux
├── macos/                      # Proyecto macOS
├── web/                        # Build web
├── assets/branding/            # Iconos y logo
├── tool/                       # Scripts de utilidad (branding)
└── pubspec.yaml
```

### 4.2 Estructura de `lib/`

```text
lib/
├── main.dart                   # Punto de entrada
├── app.dart                    # MaterialApp, providers, tema
│
├── core/
│   ├── config/
│   │   └── api_config.dart     # URL base de la API
│   ├── models/                 # Modelos/DTOs compartidos
│   ├── theme/
│   │   └── app_theme.dart
│   └── staff_permissions.dart  # Permisos UI por rol (solo visual)
│
├── providers/
│   ├── auth_provider.dart      # Estado de sesión
│   └── theme_provider.dart     # Tema claro/oscuro
│
├── routes/
│   └── app_router.dart         # Rutas nombradas y guards
│
├── services/                   # Capa de acceso a API (sin reglas de negocio)
│   ├── api_client.dart         # Cliente HTTP + interceptores JWT
│   ├── auth_service.dart
│   ├── auth_storage.dart       # Almacenamiento seguro de tokens
│   ├── member_service.dart
│   ├── membership_service.dart
│   ├── plan_service.dart
│   ├── attendance_service.dart
│   ├── body_progress_service.dart
│   ├── workout_service.dart
│   ├── product_service.dart
│   ├── sales_service.dart
│   ├── cash_register_service.dart
│   ├── reports_service.dart
│   ├── billing_sri_service.dart
│   ├── sri_config_service.dart
│   ├── staff_users_service.dart
│   ├── ai_service.dart
│   ├── ai_socket_service.dart
│   ├── notifications_service.dart
│   └── realtime_notifications_service.dart
│
├── features/                   # Pantallas por dominio
│   ├── auth/
│   ├── home/
│   ├── profile/
│   ├── qr/
│   ├── attendance/
│   ├── body_progress/
│   ├── workout/
│   ├── ai/
│   ├── shell/                  # Navegación socio
│   └── staff/                  # Panel administrativo
│       ├── shell/
│       ├── home/
│       ├── members/
│       ├── attendance/
│       ├── pos/
│       ├── reports/
│       ├── users/
│       ├── coaching/
│       └── sri/
│
└── widgets/                    # Componentes reutilizables
    ├── state_views.dart
    ├── membership_status_chip.dart
    └── sri_status_chip.dart
```

### 4.3 Features — App socio

| Carpeta | Pantalla / función |
|---------|-------------------|
| `auth/` | Login (staff y socio) |
| `home/` | Inicio, resumen de membresía |
| `profile/` | Perfil del socio |
| `qr/` | Carnet QR de acceso |
| `attendance/` | Historial de asistencias |
| `body_progress/` | Progreso físico |
| `workout/` | Rutina asignada |
| `ai/` | Chat con asistente IA |
| `shell/` | Bottom navigation socio |

### 4.4 Features — Panel staff

| Carpeta | Pantalla / función |
|---------|-------------------|
| `staff/home/` | Dashboard staff |
| `staff/members/` | Listado, alta, detalle y membresías |
| `staff/attendance/` | Registro y consulta de asistencias |
| `staff/pos/` | Punto de venta y caja |
| `staff/reports/` | Reportes y exportación |
| `staff/users/` | Gestión de usuarios staff |
| `staff/coaching/` | Medidas y asignación de rutinas |
| `staff/sri/` | Comprobantes electrónicos SRI |
| `staff/shell/` | Navegación staff por rol |

---

## 5. Documentación — `docs/`

```text
docs/
├── 00-diagnostico-sistema-php.md
├── 01-mapa-funcionalidades-actuales.md
├── 02-mapa-base-datos-actual.md
├── 03-arquitectura-objetivo-nestjs-flutter.md
├── 04-plan-migracion-fases.md
├── 05-bitacora-migracion.md
├── 06-checklist-pruebas.md
├── 07-endpoints-api.md
├── 08-guia-instalacion-backend.md
├── 09-guia-instalacion-flutter.md
├── 10-decisiones-tecnicas.md
├── 11-riesgos-y-rollback.md
├── 12-manual-funcionalidades.md
├── 13-comandos-ejecucion.md
├── 14-estructura-proyecto.md      # Este documento
└── fases/
    ├── fase-00-diagnostico.md
    ├── fase-01-backend-base.md
    ├── fase-02-auth-usuarios-roles.md
    ├── fase-03-socios-membresias.md
    ├── fase-04-asistencias-qr.md
    ├── fase-05-progreso-rutinas.md
    ├── fase-06-inventario-productos.md
    ├── fase-07-pos-ventas-caja.md
    ├── fase-08-reportes.md
    ├── fase-09-facturacion-sri.md
    ├── fase-10-flutter-app-cliente.md
    ├── fase-11-ia-websockets-gemini.md
    ├── fase-12-cierre-migracion.md
    ├── fase-13-flutter-app-staff.md
    ├── fase-14-flutter-staff-extensiones.md
    ├── fase-15-flutter-staff-sri.md
    ├── fase-16-notificaciones-persistentes.md
    └── fase-17-alertas-membresia.md
```

---

## 6. Legacy PHP — `gym-system/`

```text
gym-system/
├── app/
│   ├── config/                 # Database, rutas
│   ├── controllers/            # Controladores MVC
│   ├── models/                 # Modelos de datos
│   ├── views/                  # Vistas PHP
│   └── lib/                    # Auth, SRI, utilidades
├── public/
│   ├── index.php               # Front controller
│   └── .htaccess
├── vendor/                     # Dependencias Composer
├── schema.sql
├── bk_basededatos.sql
├── seeder.php
└── composer.json
```

> **Nota:** `gym-system/` se mantiene como referencia durante la migración. Está excluido del repositorio remoto en algunos entornos; debe existir solo de forma local.

---

## 7. Reglas Cursor — `.cursor/rules/`

| Archivo | Contenido |
|---------|-----------|
| `migracion-gym-php-nestjs-flutter.mdc` | Plan por fases y prohibiciones |
| `migracion-gym-backend-nestjs.mdc` | Convenciones NestJS |
| `migracion-gym-flutter.mdc` | Convenciones Flutter |
| `migracion-gym-jwt-auth.mdc` | Autenticación JWT obligatoria |
| `migracion-gym-nombres-estandar.mdc` | Diccionario oficial de nombres |
| `migracion-gym-reglas-negocio.mdc` | Ventas, caja, stock, QR, SRI, IA |
| `migracion-gym-calidad-documentacion.mdc` | Bitácora, checklist, calidad |

---

## 8. Endpoints API (prefijo `/api`)

Rutas principales documentadas en `docs/07-endpoints-api.md`:

| Recurso | Ruta base |
|---------|-----------|
| Auth | `/api/auth` |
| Usuarios | `/api/users` |
| Socios | `/api/members` |
| Planes | `/api/plans` |
| Membresías | `/api/memberships` |
| Asistencias | `/api/attendance` |
| QR | `/api/qr-access` |
| Progreso físico | `/api/body-progress` |
| Rutinas | `/api/workout-routines` |
| Productos | `/api/products` |
| Inventario | `/api/inventory` |
| Caja | `/api/cash-register` |
| Ventas | `/api/sales` |
| Reportes | `/api/reports` |
| SRI | `/api/billing-sri` |
| IA | `/api/ai-assistant` |
| Notificaciones | `/api/notifications` |

---

## 9. Comandos de inicio rápido

```bash
# Backend
cd backend-nest
cp .env.example .env
npm install
npx prisma migrate deploy
npm run start:dev

# Flutter
cd frontend-flutter
flutter pub get
flutter run -d windows
```

Referencia completa: `docs/13-comandos-ejecucion.md`.

---

## 10. Estado de migración (resumen)

| Fase | Área | Estado |
|------|------|--------|
| 00 | Diagnóstico PHP | Completada |
| 01 | Backend base | Completada |
| 02 | Auth / usuarios / roles | Completada |
| 03 | Socios / membresías | Completada |
| 04 | Asistencias / QR | Completada |
| 05 | Progreso / rutinas | Completada |
| 06 | Inventario / productos | Completada |
| 07 | POS / ventas / caja | Completada |
| 08 | Reportes | Completada |
| 09 | Facturación SRI | Completada |
| 10 | Flutter app socio | Completada |
| 11 | IA + WebSockets | Completada |
| 12 | Cierre migración | En curso |
| 13–17 | Flutter staff + notificaciones + alertas | Completadas |

Detalle: `docs/04-plan-migracion-fases.md` y `docs/05-bitacora-migracion.md`.

---

## Documentos relacionados

- `docs/03-arquitectura-objetivo-nestjs-flutter.md` — Principios y flujo
- `docs/07-endpoints-api.md` — API REST completa
- `docs/08-guia-instalacion-backend.md` — Instalación NestJS
- `docs/09-guia-instalacion-flutter.md` — Instalación Flutter
- `docs/10-decisiones-tecnicas.md` — Diccionario y decisiones
- `docs/15-manual-integracion-base-datos.md` — Integración BD paso a paso (Prisma + NestJS)
- `docs/02-mapa-base-datos-actual.md` — Esquema de base de datos
