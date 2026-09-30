# Roadmap Billing SaaS — Gim Pro IA

**Fecha:** 2026-03-30  
**Estado:** Diseño conceptual. **Sin Stripe. Sin cobros implementados.**

---

## 1. Separación crítica de conceptos

| Concepto gym actual | Tablas | Significado |
|---|---|---|
| Plan de membresía del socio | `planes` | Precio/duración para clientes del gym |
| Suscripción del socio | `suscripciones` | Vigencia activa/vencida del cliente |

| Concepto SaaS futuro | Tablas propuestas | Significado |
|---|---|---|
| Plan comercial de la plataforma | `saas_plans` | FREE/STARTER/PRO/BUSINESS |
| Suscripción del **tenant** | `saas_subscriptions` | El gym paga (o trial) por usar Gim Pro IA |

**Nunca reutilizar** `planes`/`suscripciones` para billing SaaS: colisión semántica y de datos.

---

## 2. Planes propuestos (configurables, no definitivos)

| Plan | Público objetivo | Idea de valor |
|---|---|---|
| `FREE` | Prueba / gym muy pequeño | Límites bajos, marca Gim Pro, 1 sucursal |
| `STARTER` | Gym en crecimiento | Operación completa, IA básica |
| `PRO` | Gym establecido | IA + voz + reportes avanzados |
| `BUSINESS` | Cadenas / multi-sucursal | Branches, API, soporte prioritario, límites altos |

Los nombres y cupos deben vivir en BD/config, no hardcodeados en Flutter.

---

## 3. Límites sugeridos (borrador)

| Límite | FREE | STARTER | PRO | BUSINESS |
|---|---|---|---|---|
| Usuarios staff | 2 | 5 | 15 | Ilimitado* |
| Clientes (socios) | 50 | 300 | 2000 | Ilimitado* |
| Sucursales | 1 | 1 | 3 | Ilimitado* |
| Almacenamiento fotos/audio | 500 MB | 5 GB | 25 GB | Custom |
| Mensajes IA / mes | 50 | 500 | 5000 | Custom |
| Minutos voz IA / mes | 0–10 | 60 | 500 | Custom |
| Consultas IA (tools) | Incluidas en mensajes | ↑ | ↑ | Custom |
| Reportes export | Básicos | Sí | Sí + programados | Sí |
| Automatizaciones (alertas) | Off/básico | On | On | On + webhooks |
| API externa | No | No | Lectura | Completa |
| Soporte | Comunidad | Email | Prioritario | Dedicado |
| Facturación SRI | Sí (1 RUC) | Sí | Sí | Multi-establecimiento |

\* “Ilimitado” = soft-cap alto con fair use.

**No hay precios ni costos ficticios en este documento.**

---

## 4. Modelo de suscripción (conceptual)

```text
saas_subscriptions
  id
  tenant_id
  plan_id              → saas_plans
  status               TRIALING | ACTIVE | PAST_DUE | PAUSED | CANCELLED | EXPIRED
  trial_ends_at
  current_period_start
  current_period_end
  cancel_at_period_end
  external_customer_id   -- futuro Stripe customer
  external_subscription_id
  created_at
  updated_at
```

### Estados

| Estado | Comportamiento sugerido |
|---|---|
| `TRIALING` | Acceso completo o casi completo hasta `trial_ends_at` |
| `ACTIVE` | Acceso según plan |
| `PAST_DUE` | Grace period; banner; posiblemente limitar IA |
| `PAUSED` | Login staff OK; escritura limitada |
| `CANCELLED` | Acceso lectura hasta fin de periodo |
| `EXPIRED` | Bloqueo operativo; export de datos permitido un tiempo |

Enforcement: middleware/`TenantGuard` consulta status; features chequean `saas_plans.limits` + `ai_usage_records`.

---

## 5. Dónde conectar Stripe (futuro)

Puntos de integración previstos (no implementar aún):

| Pieza | Ubicación sugerida |
|---|---|
| Módulo Nest | `backend-nest/src/saas-billing/` (nuevo) |
| Webhooks | `POST /api/saas-billing/webhooks/stripe` (raw body) |
| Customer portal | Endpoint autenticado OWNER/ADMIN tenant |
| Secrets | `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` en `.env` — nunca Flutter |
| Idempotencia | Tabla `saas_billing_events` (event id Stripe) |

Flujo alto nivel:

```text
OWNER elige plan → Nest crea Checkout/Subscription Session
→ Stripe webhook → actualiza saas_subscriptions
→ TenantGuard lee status en requests
```

Alternativas regionales (PayPhone, etc.) se evaluarán después; el modelo de datos debe ser provider-agnostic (`external_*`).

---

## 6. Relación con facturación SRI existente

- SRI (`billing-sri/`) factura a **clientes del gym** (B2C/B2B local).
- Billing SaaS cobra al **tenant** por el software (B2B platform).
- Son flujos independientes; no mezclar `comprobantes_electronicos` con invoices Stripe.

Al multi-tenantizar SRI: certificado, series y `configuracion` deben ser **por tenant** (hoy singleton — riesgo documentado en `SAAS-DATABASE-MAPPING.md`).

---

## 7. Roadmap por fases

| Fase | Entrega | Billing |
|---|---|---|
| 1 (actual) | Docs arquitectura | Solo diseño |
| 2 | Tenant + scoping | Plan FREE implícito / sin cobro |
| 3 | `saas_plans` + `saas_subscriptions` + enforcement límites | Manual / trial |
| 4 | Metering IA (`ai_usage_records`) | Soft limits |
| 5 | Stripe (o provider) + portal | Cobro real |
| 6 | Dunning, PAST_DUE, self-serve upgrade | Madurez |

---

## 8. Enforcement de límites (diseño)

Hoy ya existe un límite global de mensajes IA:

```env
AI_DAILY_MESSAGE_LIMIT=50
```

(`ai-chat.service.ts`)

Objetivo: límites **por tenant y plan**, no solo por proceso global.

```text
before AiChatService.sendMessage:
  if usage(tenant, feature=ai_chat, period) >= plan.limit → 429 / mensaje claro
```

Misma idea para voz (`ai_voice`, segundos audio).

---

## 9. Qué no hacer todavía

- No crear productos Stripe
- No hardcodear precios en Flutter
- No reutilizar tabla `planes` del gym
- No cobrar sin metering confiable y tests
- No cambiar credenciales de producción
