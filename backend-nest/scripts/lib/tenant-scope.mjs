/**
 * SAAS-03 — Inventario ejecutable del alcance multi-tenant.
 *
 * Fuente única (para scripts) de qué tablas llevan `tenant_id`, de qué padre heredan
 * y qué unicidades pasan a ser por tenant. Debe coincidir con schema.prisma y con la
 * migración 0002_multi_tenant_foundation; docs/SAAS-03-MULTI-TENANT-DB.md lo documenta.
 *
 * Todas las consultas de este módulo son de SOLO LECTURA.
 */
import { randomBytes } from 'node:crypto';

/**
 * scope: TENANT (raíz, FK directa a tenants) | TENANT_CHILD (además FK compuesta al padre).
 * parents: relaciones cuyo tenant debe coincidir con el de la fila.
 */
export const TENANT_TABLES = [
  { table: 'socios', scope: 'TENANT', parents: [] },
  { table: 'planes', scope: 'TENANT', parents: [] },
  { table: 'categorias', scope: 'TENANT', parents: [] },
  { table: 'cajas', scope: 'TENANT', parents: [] },
  { table: 'gastos', scope: 'TENANT', parents: [] },
  { table: 'configuracion', scope: 'TENANT', parents: [] },
  { table: 'sri_series', scope: 'TENANT', parents: [] },
  { table: 'comprobantes_electronicos', scope: 'TENANT', parents: [] },
  {
    table: 'suscripciones',
    scope: 'TENANT',
    parents: [
      { column: 'socio_id', table: 'socios' },
      { column: 'plan_id', table: 'planes' },
    ],
  },
  { table: 'sri_log', scope: 'TENANT', parents: [{ column: 'comprobante_id', table: 'comprobantes_electronicos' }] },
  { table: 'asistencias', scope: 'TENANT_CHILD', parents: [{ column: 'socio_id', table: 'socios' }] },
  { table: 'medidas', scope: 'TENANT_CHILD', parents: [{ column: 'socio_id', table: 'socios' }] },
  { table: 'rutinas', scope: 'TENANT_CHILD', parents: [{ column: 'socio_id', table: 'socios' }] },
  { table: 'notifications', scope: 'TENANT_CHILD', parents: [{ column: 'member_id', table: 'socios' }] },
  { table: 'ai_conversations', scope: 'TENANT_CHILD', parents: [{ column: 'member_id', table: 'socios' }] },
  { table: 'ai_messages', scope: 'TENANT_CHILD', parents: [{ column: 'conversation_id', table: 'ai_conversations' }] },
  { table: 'productos', scope: 'TENANT_CHILD', parents: [{ column: 'categoria_id', table: 'categorias' }] },
  {
    table: 'ventas',
    scope: 'TENANT_CHILD',
    parents: [
      { column: 'caja_id', table: 'cajas' },
      { column: 'socio_id', table: 'socios' },
    ],
  },
  {
    table: 'detalle_ventas',
    scope: 'TENANT_CHILD',
    parents: [
      { column: 'venta_id', table: 'ventas' },
      { column: 'producto_id', table: 'productos' },
    ],
  },
  {
    table: 'movimientos_inventario',
    scope: 'TENANT_CHILD',
    parents: [
      { column: 'producto_id', table: 'productos' },
      { column: 'venta_id', table: 'ventas' },
    ],
  },
  {
    table: 'comprobantes_detalle',
    scope: 'TENANT_CHILD',
    parents: [{ column: 'comprobante_id', table: 'comprobantes_electronicos' }],
  },
];

/**
 * Por tabla tenant: `_bi` resuelve/valida tenant_id al insertar y `_bu` impide cambiarlo
 * (inmutable) y valida padres opcionales.
 */
export function expectedTenantTriggers() {
  return TENANT_TABLES.flatMap(({ table }) => [`trg_${table}_tenant_bi`, `trg_${table}_tenant_bu`]).sort();
}

/** Modelos sin tenant_id (decisión documentada en docs/SAAS-03-MULTI-TENANT-DB.md). */
export const NON_TENANT_TABLES = {
  tenants: 'PLATFORM',
  tenant_memberships: 'PLATFORM',
  usuarios: 'GLOBAL',
  auth_refresh_tokens: 'GLOBAL',
};

/** Unicidades por tenant (claves de negocio que dos gimnasios pueden repetir). */
export const TENANT_UNIQUES = [
  { table: 'socios', columns: ['tenant_id', 'dni'] },
  { table: 'comprobantes_electronicos', columns: ['tenant_id', 'tipo_doc', 'serie', 'correlativo'] },
  { table: 'sri_series', columns: ['tenant_id', 'tipo_doc', 'serie'] },
  { table: 'configuracion', columns: ['tenant_id'] },
];

/** Las mismas claves antes de SAAS-03 (sin tenant): conflictos que bloquearían la migración. */
const PREFLIGHT_UNIQUES = [
  { table: 'socios', columns: ['dni'] },
  { table: 'comprobantes_electronicos', columns: ['tipo_doc', 'serie', 'correlativo'] },
  { table: 'sri_series', columns: ['tipo_doc', 'serie'] },
];

const ULID_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

/** ULID (Crockford base32): 10 caracteres de timestamp en ms + 16 aleatorios. */
export function generateUlid(now = Date.now()) {
  let time = '';
  let rest = now;
  for (let i = 0; i < 10; i += 1) {
    time = ULID_ALPHABET[rest % 32] + time;
    rest = Math.floor(rest / 32);
  }
  let random = '';
  for (const byte of randomBytes(16)) {
    random += ULID_ALPHABET[byte % 32];
  }
  return time + random;
}

async function count(conn, sql) {
  const [row] = await conn.query(sql);
  return Number(row.total);
}

function duplicateSql(table, columns) {
  const cols = columns.map((c) => `\`${c}\``).join(', ');
  return `SELECT COUNT(*) AS total FROM (SELECT 1 FROM \`${table}\` GROUP BY ${cols} HAVING COUNT(*) > 1) d`;
}

/**
 * Pre-check sobre una base PREVIA a SAAS-03: detecta datos que harían fallar la
 * migración. No modifica nada; los conflictos se reportan (BLOCKED_BY_DATA_INTEGRITY).
 */
export async function preflightTenantMigration(conn) {
  const issues = [];
  const configRows = await count(conn, 'SELECT COUNT(*) AS total FROM `configuracion`');
  if (configRows > 1) issues.push({ check: 'configuracion con más de una fila', count: configRows });
  for (const { table, columns } of PREFLIGHT_UNIQUES) {
    const duplicates = await count(conn, duplicateSql(table, columns));
    if (duplicates > 0) issues.push({ check: `duplicados ${table}(${columns.join(', ')})`, count: duplicates });
  }
  return issues;
}

/**
 * Auditoría POST-migración. Devuelve conteos por tabla y la lista de violaciones:
 * tenant_id NULL, tenant inexistente, hijo con tenant distinto al del padre,
 * duplicados en unicidades por tenant y staff sin membership.
 */
export async function checkTenantIntegrity(conn) {
  const tables = [];
  const violations = [];
  const add = (check, total) => {
    if (total > 0) violations.push({ check, count: total });
  };

  for (const { table, parents } of TENANT_TABLES) {
    const total = await count(conn, `SELECT COUNT(*) AS total FROM \`${table}\``);
    const withTenant = await count(conn, `SELECT COUNT(tenant_id) AS total FROM \`${table}\``);
    tables.push({ table, total, withTenant });
    add(`${table}: tenant_id NULL`, total - withTenant);
    add(
      `${table}: tenant inexistente`,
      await count(
        conn,
        `SELECT COUNT(*) AS total FROM \`${table}\` x LEFT JOIN \`tenants\` t ON t.id = x.tenant_id WHERE t.id IS NULL`,
      ),
    );
    for (const parent of parents) {
      add(
        `${table}.${parent.column}: tenant distinto al de ${parent.table}`,
        await count(
          conn,
          `SELECT COUNT(*) AS total FROM \`${table}\` x JOIN \`${parent.table}\` p ON p.id = x.\`${parent.column}\`
           WHERE p.tenant_id <> x.tenant_id`,
        ),
      );
    }
  }

  for (const { table, columns } of TENANT_UNIQUES) {
    add(`duplicados ${table}(${columns.join(', ')})`, await count(conn, duplicateSql(table, columns)));
  }

  const tenants = await count(conn, 'SELECT COUNT(*) AS total FROM `tenants`');
  if (tenants > 0) {
    add(
      'usuarios sin tenant_membership',
      await count(
        conn,
        'SELECT COUNT(*) AS total FROM `usuarios` u WHERE NOT EXISTS (SELECT 1 FROM `tenant_memberships` m WHERE m.user_id = u.id)',
      ),
    );
  }

  return { tables, violations };
}
