# Modelo de Seguridad SaaS Multi-Tenant — Gim Pro IA

**Fecha:** 2026-03-30  
**Referencias código:** `auth/`, `common/utils/member-access.util.ts`, `websocket/ws-auth.service.ts`, `ai-assistant/`

---

## 1. Principios obligatorios

| Principio | Aplicación en Gim Pro IA |
|---|---|
| Least privilege | Roles mínimos por endpoint (`@Roles`) + permisos futuros granulares |
| Deny by default | Sin JWT → 401; sin rol → 403; sin membership tenant → denegar |
| Server-side authorization | Flutter solo UX (`staff_permissions.dart`); NestJS decide |
| Tenant isolation | Toda query tenant-scoped filtra `tenant_id` verificado |
| Defense in depth | JWT + TenantGuard + query scope + ownership member + audit |
| Auditability | `audit_logs` para acciones críticas |

---

## 2. Modelo de confianza (actual vs objetivo)

### Actual (seguro para mono-tenant, insuficiente para SaaS)

```text
Cliente → JWT firmado → JwtAccessGuard → RolesGuard → Controller
                                              ↓
                         assertMemberResourceAccess (solo si userType=member)
                                              ↓
                         Service: findUnique({ id })  ← sin tenant
```

Fortalezas ya existentes:

- Access/refresh separados; refresh hasheado y revocable (`refresh-token.service.ts`)
- Socio no puede leer otro `memberId` en endpoints que usan `assertMemberResourceAccess`
- AI/WS: `memberId` del token, no del body (`WsAuthService.authenticateMember`)
- API keys IA solo en servidor (`.env`, nunca Flutter)

Debilidades para multi-tenant:

- Staff `admin` ve **toda** la BD (`members.service.findAll`, `reports.service`, etc.)
- No hay contexto de organización en JWT (`JwtPayload` sin `tenantId`)
- Uploads estáticos públicos bajo `/api/uploads/...` sin ACL por tenant
- Cron de alertas recorre todas las suscripciones

### Objetivo

```text
Cliente → JWT (sub, tenantId, roles[], userType, memberId?)
       → JwtAccessGuard
       → TenantContextGuard (membership activa verificada en BD)
       → RolesGuard (rol dentro del tenant)
       → Controller
       → Service: findFirst({ where: { id, tenantId } })
```

---

## 3. Derivación del tenant (regla crítica)

### Permitido

1. `tenantId` embebido en access token **después** de validar `tenant_memberships`
2. Revalidación opcional en cada request (membership `ACTIVE`, tenant no `SUSPENDED`)
3. Socio: `tenantId` tomado de `socios.tenant_id` en login (servidor)

### Prohibido como autorización

- `tenantId` en query string (`?tenantId=`)
- `tenantId` en body
- Header manipulable `X-Tenant-Id` **sin** cruzar con membership del `sub`
- Confiar en que Flutter “oculte” otros gimnasios
- Permitir que el LLM elija tenant o genere SQL libre sin wrapper

Si el cliente envía un tenant candidato (selector UI), el backend debe:

```text
SELECT 1 FROM tenant_memberships
WHERE user_id = :sub AND tenant_id = :candidate AND status = 'ACTIVE'
```

Si no existe → 403. Solo entonces emitir/rotar JWT scoped.

---

## 4. Estrategia 403 vs 404

**Recomendación:** lecturas cross-tenant → **404** (no revelar existencia).  
Escrituras/acciones admin explícitas sobre recurso ajeno → **403** si el rol no aplica, o 404 si el recurso no está en el tenant.

Hoy: socio ajeno → `ForbiddenException` en `assertMemberResourceAccess` (403). Mantener para ownership; al añadir tenant, unificar política.

---

## 5. Capas de defensa

### 5.1 Identidad

- JWT access corto (`JWT_ACCESS_EXPIRES_IN`)
- Refresh rotado/revocado
- Usuario/socio inactivo → login rechazado (ya existe)

### 5.2 Contexto tenant

Nuevo (futuro):

```ts
interface TenantContext {
  tenantId: number;
  membershipId: number;
  roles: TenantRole[];
  userType: 'staff' | 'member' | 'platform';
}
```

Persistido en `AsyncLocalStorage` o request scoped provider.

### 5.3 RBAC

Ver `SAAS-ROLES-MATRIX.md`. RBAC **nunca** omite el filtro tenant.

Ejemplo:

```text
ADMIN Tenant A → GET /members/{id_de_B} → 404
aunque role === admin
```

### 5.4 Query scoping

Patrón Prisma objetivo:

```ts
await prisma.socios.findFirst({
  where: { id: memberId, tenant_id: tenantId },
});
```

Evitar `findUnique({ where: { id } })` en entidades tenant-scoped salvo que el unique incluya tenant.

### 5.5 Canales especiales

| Canal | Controles |
|---|---|
| WebSocket | Auth handshake JWT; sala `tenant:{tenantId}:member:{memberId}`; no suscribir salas ajenas |
| IA tools | `memberId` + `tenantId` del contexto; tools llaman services scoped |
| Voz | Mismo pipeline que chat; audio en memoria; no persistir cross-tenant |
| Archivos | Path `tenants/{tenantId}/...` + auth al servir (dejar de exponer solo static público a largo plazo) |
| Cron | Iterar por tenant o filtrar `tenant_id` |
| Exports | Incluir tenant en query y nombre de archivo |

---

## 6. PLATFORM_ADMIN

| Aspecto | Regla |
|---|---|
| Separación | Rol de plataforma, **no** es `usuarios.rol = admin` del gym |
| Almacenamiento | Tabla/flag aparte (`platform_users`) o `userType: 'platform'` |
| Operaciones | Listar tenants, suspender, impersonation controlada, billing |
| Auditoría | Obligatoria en cada acción cross-tenant |
| Impersonation | Token de corta duración, motivo registrado, opt-in |

Un `admin` de gimnasio **no** puede:

- Crear/suspender otros tenants
- Ver métricas globales de la plataforma
- Acceder a BD de otro gym

---

## 7. Secretos y superficie de ataque

| Activo | Ubicación actual | Riesgo SaaS |
|---|---|---|
| JWT secrets | `.env` | Compartidos OK a nivel platform |
| GEMINI/ZAI/OLLAMA | `.env` servidor | OK; usage por tenant |
| SMTP | `.env` | Preferir SMTP por tenant o platform relay |
| Certificado SRI P12 | `SRI_CERT_DIR` | **Debe ser por tenant** (hoy singleton) |
| WhatsApp keys en `socios` | columna legacy | Revisar; no compartir entre tenants |

Nunca loguear: passwords, refresh tokens, API keys, XML firmado completo en logs abiertos.

---

## 8. Auditoría de seguridad (tabla futura)

Campos mínimos propuestos para `audit_logs`:

```text
id, tenant_id (nullable para platform), user_id, actor_type,
action, resource, resource_id, metadata (JSON sanitizado),
ip, user_agent, created_at
```

Eventos críticos:

- login / logout / refresh revoke
- create/suspend tenant
- invite user / change role
- delete/anonymize client
- export data
- billing/plan change
- AI usage spike / config change
- SRI certificate update

---

## 9. Pruebas de aislamiento (diseño)

Ver sección detallada en `SAAS-MULTI-TENANT-MIGRATION.md` y checklist al final de este doc.

Escenario base:

```text
Tenant A, Tenant B
UserA ∈ A, UserB ∈ B
ClientA1 ∈ A, ClientB1 ∈ B

UserA → ClientA1 ALLOW
UserA → ClientB1 DENY
UserB → ClientB1 ALLOW
UserB → ClientA1 DENY
```

Repetir para: membresías, asistencia, pagos/ventas, productos, rutinas, usuarios staff, reportes, IA, voz, WS, archivos.

Automatizar en CI cuando exista segundo tenant en staging (OWASP: no depender solo de QA manual).

---

## 10. Checklist de controles (estado Fase 1)

| Control | Estado |
|---|---|
| JWT access/refresh | Implementado |
| RolesGuard | Implementado (roles gym actuales) |
| Member ownership | Parcial (endpoints con assert) |
| Tenant en JWT | **No existe** |
| Tenant query scope | **No existe** |
| PLATFORM_ADMIN | **No existe** |
| Audit logs estructurados | **No existe** (solo `sri_log`, movimientos inventario) |
| File ACL por tenant | **No existe** |
| Tests aislamiento multi-tenant | **Diseñados, no implementados** |

---

## 11. Amenazas priorizadas

| ID | Amenaza | Severidad si se abre multi-tenant sin fix |
|---|---|---|
| T1 | Staff lista socios/productos de otro gym | Crítica |
| T2 | IDOR por `GET /members/:id` | Crítica |
| T3 | Reportes financieros cross-tenant | Crítica |
| T4 | IA/tools devolviendo datos de otro gym vía IDs | Alta |
| T5 | Series/comprobantes SRI colisionando | Alta |
| T6 | Archivos de foto accesibles por URL | Media |
| T7 | Cron alertas mezclando tenants | Media |
| T8 | Elevación admin gym → platform | Alta (si se implementa mal) |
