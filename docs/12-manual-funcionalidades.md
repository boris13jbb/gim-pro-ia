# Manual de Funcionalidades — Sistema Migrado (NestJS + Flutter)

**Estado:** Cierre de migración (Fase 12). Describe el sistema migrado. El inventario del sistema PHP original se conserva en `docs/00-diagnostico-sistema-php.md` y `docs/01-mapa-funcionalidades-actuales.md`.

## Arquitectura

```text
Flutter (app socios)  ─┐
Swagger / clientes API ─┼─►  NestJS API (JWT) ──► MySQL (ec_gym_system, vía Prisma)
                        │         │
                        │         └──► Gemini (solo servidor, Fase 11)
                        └─►  WebSocket (socket.io): /ai (chat streaming), /events (notificaciones)
```

- **Backend NestJS**: toda la lógica de negocio, validación, seguridad y acceso a datos.
- **Flutter**: app de socios; solo consume la API (sin reglas críticas en el cliente).
- **Legacy PHP**: se mantiene como referencia; no forma parte del despliegue nuevo.

## Roles y acceso

| Rol | Acceso | Dónde |
|-----|--------|-------|
| `admin` | Todo el sistema | API / Swagger |
| `recepcionista` | Socios, membresías, asistencias, POS, caja | API / Swagger |
| `entrenador` | Socios, asistencias, rutinas, progreso | API / Swagger |
| `socio` | Solo sus propios datos | App Flutter (`/auth/member/login`) |

> La interfaz gráfica de staff (admin/recepcionista/entrenador) es un pendiente post-cierre; hoy esos roles se operan por la API. La app Flutter es exclusiva de socios.

## Módulos del sistema migrado (API NestJS)

1. **Autenticación** — Login staff y socio, JWT access/refresh, `/auth/me`, logout con revocación.
2. **Usuarios (staff)** — CRUD, cambio de rol/estado/contraseña (solo admin).
3. **Socios** — CRUD con foto, búsqueda, estado, contraseña de app.
4. **Planes** — CRUD de planes con duración y precio.
5. **Membresías** — Alta/cancelación; estado calculado en backend (activa/vencida/cancelada/suspendida).
6. **Asistencias y QR** — Validación por DNI/QR, registro (staff y auto-registro del socio), reportes y ranking, export PDF/Excel.
7. **Progreso físico** — Medidas corporales e historial.
8. **Rutinas** — Rutina actual y asignación (admin/entrenador).
9. **Inventario y productos** — Categorías, productos, stock con auditoría, alertas de stock bajo (sin stock negativo).
10. **POS / Ventas** — Venta transaccional con ticket y export PDF (requiere caja abierta).
11. **Caja** — Apertura/cierre con cuadre; resumen de caja usando ventas reales (corrige bug PHP E01).
12. **Reportes** — Financieros y de asistencia con exportaciones.
13. **Facturación SRI (Ecuador)** — Emisión de comprobantes electrónicos, notas de crédito, XML/PDF, reintentos y logs, envío por correo.
14. **Asistente IA (Gemini)** — Chat solo para socios con contexto real (perfil, membresía, asistencias, progreso, rutina); REST + streaming por WebSocket. API key solo en servidor.
15. **Notificaciones en tiempo real** — Avisos al socio por WebSocket (asistencia registrada, cambios de membresía).

## Funcionalidades de la app Flutter (socios)

- Inicio de sesión de socio y perfil.
- Estado de membresía (días restantes, vigencia).
- Carnet QR para acceso.
- Progreso físico con gráficos.
- Rutina actual.
- Registro de asistencia propia.
- Asistente IA con respuesta en streaming.
- Notificaciones en tiempo real (campana con historial + aviso puntual).
- Tema claro/oscuro/sistema.

## Referencias

- Endpoints y ejemplos: `docs/07-endpoints-api.md`
- Instalación backend: `docs/08-guia-instalacion-backend.md`
- Instalación Flutter: `docs/09-guia-instalacion-flutter.md`
- Comandos: `docs/13-comandos-ejecucion.md`
- Decisiones técnicas y diccionario: `docs/10-decisiones-tecnicas.md`
- Riesgos y rollback: `docs/11-riesgos-y-rollback.md`
