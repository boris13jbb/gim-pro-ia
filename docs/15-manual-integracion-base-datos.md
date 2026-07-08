# Manual de Integración de Base de Datos — Gym Pro IA

**Última actualización:** 2026-07-07  
**Motor:** MySQL / MariaDB 10+  
**Base de datos:** `ec_gym_system`  
**ORM:** Prisma 7.x con adapter MariaDB  
**Backend:** NestJS (`backend-nest/`)

Este documento explica **paso a paso** cómo funciona la integración de la base de datos en el proyecto: desde la instalación local hasta cómo NestJS ejecuta consultas en tiempo de ejecución.

---

## 1. Visión general

```text
┌─────────────────┐     HTTP/JWT      ┌──────────────────┐     Prisma Client     ┌─────────────────────┐
│  Flutter App    │ ───────────────► │  NestJS API      │ ────────────────────► │  MySQL / MariaDB    │
│  (solo consume) │                  │  (lógica negocio)│   adapter MariaDB     │  ec_gym_system      │
└─────────────────┘                  └──────────────────┘                       └─────────────────────┘
                                              │
                                              ▼
                                     prisma/schema.prisma
                                     (modelos tipados)
```

### Principios clave

| Principio | Descripción |
|-----------|-------------|
| Una sola BD central | `ec_gym_system` es compartida con el sistema PHP legacy durante la migración |
| Lógica en NestJS | Flutter **no** accede directamente a la base de datos |
| Prisma como capa de acceso | Los servicios NestJS usan `PrismaService`, no SQL crudo (salvo casos justificados) |
| Compatibilidad legacy | Los nombres de tablas/columnas PHP se conservan en el schema (`socios`, `usuarios`, `ventas`, etc.) |
| Tablas nuevas NestJS | Se agregan con `db push` seguro, sin reset destructivo |

### Documentos relacionados

| Documento | Contenido |
|-----------|-----------|
| `docs/02-mapa-base-datos-actual.md` | Mapa de tablas, columnas y relaciones |
| `docs/08-guia-instalacion-backend.md` | Instalación rápida del backend |
| `docs/11-riesgos-y-rollback.md` | Riesgo R08: drift Prisma en BD legacy |
| `docs/13-comandos-ejecucion.md` | Comandos de BD en una sola referencia |
| `backend-nest/.env.example` | Variables de entorno de conexión |

---

## 2. Requisitos previos

Antes de integrar la base de datos, verifica:

1. **Node.js 20+** y **npm 10+** instalados.
2. **MySQL 8.x** o **MariaDB 10.4+** en ejecución (Laragon, XAMPP o servicio nativo).
3. Puerto **3306** disponible en `127.0.0.1`.
4. Usuario con permisos para crear bases y tablas (típicamente `root` en desarrollo).

Comprobar versiones:

```bash
node -v
npm -v
```

---

## 3. Paso 1 — Configurar variables de entorno

### 3.1 Crear el archivo `.env`

Desde la carpeta `backend-nest/`:

```bash
cd backend-nest
copy .env.example .env
```

En Linux/macOS:

```bash
cp .env.example .env
```

### 3.2 Definir `DATABASE_URL`

La variable central de conexión tiene este formato:

```env
DATABASE_URL="mysql://USUARIO:CONTRASEÑA@HOST:PUERTO/NOMBRE_BD"
```

**Ejemplo por defecto del proyecto (Laragon/XAMPP local):**

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/ec_gym_system"
```

| Parte | Valor típico | Notas |
|-------|--------------|-------|
| Usuario | `root` | Cambiar en producción |
| Contraseña | vacía en dev | Codificar caracteres especiales en la URL |
| Host | `127.0.0.1` | Preferir IP sobre `localhost` en Windows (evita problemas IPv6) |
| Puerto | `3306` | Puerto estándar MySQL/MariaDB |
| Base | `ec_gym_system` | Mismo nombre que el sistema PHP legacy |

> **Seguridad:** Nunca subas `.env` al repositorio. Solo `.env.example` va versionado.

---

## 4. Paso 2 — Preparar la base de datos

Tienes dos caminos según tu entorno.

### Opción A — Primera instalación (sin datos previos)

Importa el backup del sistema PHP legacy:

```bash
cd backend-nest
npm install
npm run db:import-legacy
```

**Qué hace este script (`scripts/import-legacy-db.mjs`):**

1. Lee `gym-system/bk_basededatos.sql`.
2. Adapta el SQL para MariaDB (collation `utf8mb4_unicode_ci`, elimina directivas MySQL 8 incompatibles).
3. Se conecta a `127.0.0.1:3306` con usuario `root` y contraseña vacía.
4. Ejecuta el script completo y crea la base `ec_gym_system` con sus tablas iniciales.

**Resultado esperado en consola:**

```text
Importando backup legacy...
Base ec_gym_system lista. Tablas: 18
```

### Opción B — Ya tienes `ec_gym_system` en MySQL

Si la base ya existe (por PHP, Laragon o un dump previo), **no necesitas** `db:import-legacy`. Solo asegúrate de que `DATABASE_URL` apunte a esa base.

---

## 5. Paso 3 — Introspección del schema con Prisma

Una vez que la base existe, Prisma lee la estructura real y genera el archivo de modelos.

```bash
cd backend-nest
npm run db:pull
```

**Qué ocurre:**

- Prisma conecta usando `DATABASE_URL` (definida en `prisma.config.ts`).
- Inspecciona tablas, columnas, índices, enums y relaciones.
- Escribe/actualiza `backend-nest/prisma/schema.prisma`.

**Archivos involucrados:**

| Archivo | Rol |
|---------|-----|
| `prisma/schema.prisma` | Definición de modelos Prisma (fuente de verdad del ORM) |
| `prisma.config.ts` | Configuración Prisma 7: ruta del schema y URL de conexión |

Ejemplo de configuración en `prisma.config.ts`:

```ts
export default defineConfig({
  schema: "prisma/schema.prisma",
  migrations: { path: "prisma/migrations" },
  datasource: { url: process.env["DATABASE_URL"] },
});
```

> En Prisma 7 la URL **no** va dentro de `schema.prisma`; va en `prisma.config.ts` y en el adapter de NestJS.

---

## 6. Paso 4 — Generar el cliente Prisma

```bash
cd backend-nest
npm run db:generate
```

**Qué ocurre:**

- Prisma genera `@prisma/client` con tipos TypeScript para cada modelo.
- El script `postinstall` en `package.json` ejecuta `prisma generate` automáticamente tras `npm install`.

**Modelos generados (ejemplos):**

- Legacy PHP: `socios`, `usuarios`, `planes`, `suscripciones`, `ventas`, `cajas`, etc.
- NestJS: `auth_refresh_tokens`, `movimientos_inventario`, tablas de IA, etc.

---

## 7. Paso 5 — Sincronizar tablas nuevas de NestJS (sin reset)

Cuando el schema Prisma incluye tablas que **aún no existen** en la BD legacy (por ejemplo `auth_refresh_tokens`), usa el flujo seguro:

```bash
cd backend-nest
npm run db:push:legacy
npm run db:generate
```

**Qué hace `db:push:legacy`:**

- Ejecuta `prisma db push` **sin reset destructivo**.
- Crea o ajusta solo lo que falta según `schema.prisma`.
- **No borra** datos existentes del sistema PHP.

### ⚠️ Qué NO hacer en BD legacy importada

| Comando | Riesgo |
|---------|--------|
| `npx prisma migrate reset` | **Destruye todos los datos** |
| `npx prisma migrate dev` (si pide reset) | Puede borrar la BD por drift con el historial PHP |
| Renombrar/eliminar columnas sin plan | Rompe PHP legacy y NestJS |

Ver mitigación completa en `docs/11-riesgos-y-rollback.md` (riesgo **R08**).

---

## 8. Paso 6 — Cómo NestJS se conecta a la BD

### 8.1 Flujo de arranque

```text
1. NestJS carga ConfigModule (.env)
2. DatabaseModule (global) instancia PrismaService
3. PrismaService lee DATABASE_URL
4. parseMysqlDatabaseUrl() convierte la URL a host/port/user/password/database
5. Se crea PrismaMariaDb adapter (driver mariadb)
6. onModuleInit() → $connect()
7. Los servicios inyectan PrismaService y ejecutan consultas
```

### 8.2 `DatabaseModule` (módulo global)

Ubicación: `backend-nest/src/database/database.module.ts`

- Marca `@Global()` para que **todos** los módulos puedan inyectar `PrismaService` sin importar `DatabaseModule` en cada uno.
- Exporta `PrismaService` y `PrismaHealthIndicator`.

### 8.3 `PrismaService` (conexión real)

Ubicación: `backend-nest/src/database/prisma.service.ts`

Puntos importantes:

1. **Prisma 7** ya no usa el motor Rust embebido para MySQL; usa el **adapter `@prisma/adapter-mariadb`**.
2. La URL se parsea con `parseMysqlDatabaseUrl()` porque el adapter necesita parámetros explícitos.
3. En Windows, `localhost` se normaliza a `127.0.0.1` para evitar fallos de conexión IPv6.
4. `allowPublicKeyRetrieval: true` permite autenticación `caching_sha2_password` (común en Laragon/XAMPP).
5. Al iniciar el módulo llama `$connect()`; al cerrar la app, `$disconnect()`.

### 8.4 Registro en la aplicación

En `app.module.ts`, `DatabaseModule` se importa junto con `ConfigModule.forRoot({ isGlobal: true })`, de modo que `DATABASE_URL` está disponible en todo el backend.

---

## 9. Paso 7 — Cómo los servicios usan la base de datos

### 9.1 Patrón estándar

Cada servicio de negocio inyecta `PrismaService`:

```ts
@Injectable()
export class MembersService {
  constructor(private readonly prisma: PrismaService) {}

  async findOne(id: number) {
    return this.prisma.socios.findUnique({ where: { id } });
  }
}
```

**Flujo de una petición típica:**

```text
Cliente → Controller → Service → PrismaService → MySQL → respuesta tipada
```

### 9.2 Convención de nombres en el schema

| Concepto de negocio | Modelo Prisma | Tabla MySQL |
|---------------------|---------------|-------------|
| Socio | `socios` | `socios` |
| Usuario staff | `usuarios` | `usuarios` |
| Membresía | `suscripciones` | `suscripciones` |
| Refresh token JWT | `auth_refresh_tokens` | `auth_refresh_tokens` |

Los nombres legacy en español se mantienen en el schema para **no romper** la BD existente. El código NestJS usa inglés en servicios, DTOs y rutas (`MembersService`, `/api/members`).

### 9.3 Transacciones (operaciones críticas)

Ventas, inventario, caja y facturación SRI usan `$transaction` para garantizar atomicidad:

```ts
await this.prisma.$transaction(async (tx) => {
  // varias operaciones; si una falla, rollback automático
});
```

Ejemplos reales en el proyecto:

- `sales/sales.service.ts` — venta + detalle + stock
- `inventory/inventory-stock.service.ts` — movimientos de inventario
- `billing-sri/services/sri-receipt-repository.service.ts` — comprobante + detalle

### 9.4 Consultas raw (uso limitado)

El health check usa una consulta liviana sin depender de modelos:

```ts
await this.prisma.$queryRaw`SELECT 1`;
```

Úsalo solo cuando Prisma no cubra el caso o para diagnósticos puntuales.

---

## 10. Paso 8 — Verificar que la integración funciona

### 10.1 Health check de base de datos

1. Inicia el backend:

```bash
cd backend-nest
npm run start:dev
```

2. Consulta el endpoint público:

```http
GET http://localhost:3000/api/health
```

**Respuesta esperada (fragmento):**

```json
{
  "data": {
    "status": "ok",
    "info": {
      "database": { "status": "up" }
    }
  }
}
```

Si `database.status` es `"down"`, revisa la sección de problemas frecuentes (sección 12).

### 10.2 Prisma Studio (exploración visual)

```bash
cd backend-nest
npx prisma studio
```

Abre un navegador en el puerto que indique Prisma (típicamente `5555`) para ver y editar registros de forma visual.

### 10.3 Prueba funcional con login

```http
POST http://localhost:3000/api/auth/login
Content-Type: application/json

{
  "email": "admin@irongym.com",
  "password": "tu_contraseña"
}
```

Si el login responde con tokens, la BD de usuarios y `auth_refresh_tokens` está operativa.

---

## 11. Paso 9 — Agregar o modificar estructura de BD (flujo seguro)

Sigue este orden **siempre**:

### 11.1 Antes de cambiar nada

1. Lee `docs/02-mapa-base-datos-actual.md`.
2. Haz respaldo:

```bash
mysqldump -u root -p ec_gym_system > backup_antes_cambio.sql
```

3. Documenta el cambio en `docs/11-riesgos-y-rollback.md` si afecta tablas legacy.

### 11.2 Si la tabla/columna es nueva (solo NestJS)

1. Edita `backend-nest/prisma/schema.prisma`.
2. Ejecuta:

```bash
npm run db:push:legacy
npm run db:generate
```

3. Implementa o actualiza el servicio NestJS.
4. Actualiza `docs/02-mapa-base-datos-actual.md`, bitácora y checklist de pruebas.

### 11.3 Si la BD cambió fuera de Prisma (manual o PHP)

1. Aplica el cambio en MySQL.
2. Sincroniza el schema:

```bash
npm run db:pull
npm run db:generate
```

3. Revisa enums y relaciones que Prisma pueda haber regenerado.
4. Compila: `npm run build`.

### 11.4 Prohibiciones en migración activa

- No renombrar tablas/columnas legacy sin plan documentado.
- No eliminar columnas usadas por PHP.
- No ejecutar `migrate reset`.
- No commitear `.env` ni dumps con datos reales.

---

## 12. Problemas frecuentes y soluciones

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `DATABASE_URL no está definida` | Falta `.env` | Copiar `.env.example` → `.env` |
| `Unknown database 'ec_gym_system'` | BD no creada | `npm run db:import-legacy` o crear la base manualmente |
| `pool timeout` / MySQL no responde | Servicio MySQL detenido | Iniciar MySQL/MariaDB en Laragon/XAMPP |
| Error collation `utf8mb4_0900_ai_ci` | SQL de MySQL 8 en MariaDB 10 | Usar `db:import-legacy` (ya adapta el SQL) |
| `migrate dev` pide reset | Drift entre BD PHP y historial Prisma | Usar `npm run db:push:legacy` |
| Health `database: down` | Credenciales/host incorrectos | Revisar `DATABASE_URL`; probar `127.0.0.1` |
| Tipos desactualizados en TS | Cliente Prisma viejo | `npm run db:generate` |
| Login OK pero refresh falla | Falta tabla `auth_refresh_tokens` | `npm run db:push:legacy` |

---

## 13. Resumen de comandos (referencia rápida)

Ejecutar siempre desde `backend-nest/`:

| Orden | Comando | Cuándo usarlo |
|-------|---------|---------------|
| 1 | `npm install` | Primera vez o tras cambiar dependencias |
| 2 | `copy .env.example .env` | Configurar conexión |
| 3 | `npm run db:import-legacy` | BD vacía, primera instalación |
| 4 | `npm run db:pull` | Sincronizar schema desde BD existente |
| 5 | `npm run db:push:legacy` | Crear tablas nuevas NestJS sin borrar datos |
| 6 | `npm run db:generate` | Regenerar cliente TypeScript |
| 7 | `npm run start:dev` | Levantar API y probar conexión |
| 8 | `GET /api/health` | Confirmar `database.status = up` |

---

## 14. Diagrama completo del ciclo de vida

```mermaid
flowchart TD
  A[Instalar dependencias] --> B[Configurar .env / DATABASE_URL]
  B --> C{¿BD ec_gym_system existe?}
  C -->|No| D[npm run db:import-legacy]
  C -->|Sí| E[npm run db:pull]
  D --> E
  E --> F[npm run db:push:legacy]
  F --> G[npm run db:generate]
  G --> H[npm run start:dev]
  H --> I[PrismaService $connect]
  I --> J[Servicios NestJS consultan BD]
  J --> K[GET /api/health confirma database up]
```

---

## 15. Guía de prueba paso a paso (checklist)

Usa esta lista para validar una integración desde cero:

| # | Acción | Resultado esperado |
|---|--------|-------------------|
| 1 | `npm install` en `backend-nest/` | Sin errores; `prisma generate` en postinstall |
| 2 | Crear `.env` con `DATABASE_URL` correcta | Archivo presente, no versionado |
| 3 | `npm run db:import-legacy` (si aplica) | Mensaje con tablas importadas |
| 4 | `npm run db:pull` | `schema.prisma` actualizado |
| 5 | `npm run db:push:legacy` | Tablas NestJS creadas sin error |
| 6 | `npm run db:generate` | Cliente Prisma generado |
| 7 | `npm run build` | Compilación exitosa |
| 8 | `npm run start:dev` | API en puerto 3000 |
| 9 | `GET /api/health` | `database.status: up` |
| 10 | `POST /api/auth/login` | Tokens emitidos |

**Si algo falla:** anota el mensaje exacto, revisa la sección 12 y consulta `docs/11-riesgos-y-rollback.md`.

---

## 16. Archivos clave del repositorio

```text
backend-nest/
├── .env.example              # Plantilla DATABASE_URL
├── prisma.config.ts          # URL y ruta del schema (Prisma 7)
├── prisma/
│   └── schema.prisma         # Modelos ORM
├── scripts/
│   ├── import-legacy-db.mjs  # Importa bk_basededatos.sql
│   └── db-push-legacy.mjs    # db push seguro
└── src/
    └── database/
        ├── database.module.ts
        ├── prisma.service.ts
        ├── prisma.health.ts
        └── parse-mysql-database-url.ts

gym-system/
└── bk_basededatos.sql        # Backup SQL del sistema PHP
```

---

**Estado:** Documento operativo para desarrollo y onboarding.  
**Próxima revisión:** al cambiar versión mayor de Prisma, motor de BD o estrategia de migraciones en Fase 12 (cierre).
