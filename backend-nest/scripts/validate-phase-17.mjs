/**
 * Validación runtime Fase 17 — alertas de membresía.
 * Uso (con API en marcha): node scripts/validate-phase-17.mjs
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

function loadEnvFile() {
  try {
    const content = readFileSync(resolve(process.cwd(), '.env'), 'utf8');
    for (const line of content.split('\n')) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) continue;
      const eq = trimmed.indexOf('=');
      if (eq === -1) continue;
      const key = trimmed.slice(0, eq).trim();
      let value = trimmed.slice(eq + 1).trim();
      if (
        (value.startsWith('"') && value.endsWith('"')) ||
        (value.startsWith("'") && value.endsWith("'"))
      ) {
        value = value.slice(1, -1);
      }
      process.env[key] = value;
    }
  } catch {
    // .env opcional
  }
}

loadEnvFile();

const port = process.env.PORT ?? '3000';
const base = process.env.API_BASE ?? `http://127.0.0.1:${port}/api`;
const stamp = Date.now();
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function localDatePlus(days) {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() + days);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

async function req(path, options = {}) {
  const { headers: extraHeaders, ...rest } = options;
  const res = await fetch(`${base}${path}`, {
    ...rest,
    headers: {
      'Content-Type': 'application/json',
      ...(extraHeaders ?? {}),
    },
  });
  const body = await res.json().catch(() => ({}));
  return { status: res.status, body };
}

function auth(token) {
  return { Authorization: `Bearer ${token}` };
}

function unwrap(body) {
  return body?.data ?? body;
}

const results = [];

function record(id, name, ok, detail) {
  const row = { id, name, ok, detail };
  results.push(row);
  console.log(`${ok ? 'PASS' : 'FAIL'}\t${id}\t${name}\t${detail}`);
  return row;
}

async function dbConnect() {
  return mariadb.createConnection({
    host: '127.0.0.1',
    user: 'root',
    password: '',
    database: 'ec_gym_system',
  });
}

async function ensureStaff(conn) {
  const hash = await bcrypt.hash('123456', 10);
  await conn.query(
    `INSERT INTO usuarios (nombre,email,password,rol,estado)
     VALUES ('Recep F17','recep@test.com',?,'recepcionista','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='recepcionista'`,
    [hash, hash],
  );
}

async function createMemberWithMembership(conn, label, daysUntilEnd, estado = 'activa') {
  const dni = `F17${stamp}${label}`.slice(0, 20);
  const hash = await bcrypt.hash('123456', 10);
  const result = await conn.query(
    `INSERT INTO socios (nombre,dni,email,estado,password)
     VALUES (?,?,?,'activo',?)`,
    [`Socio F17 ${label}`, dni, `${dni.toLowerCase()}@test.local`, hash],
  );
  const memberId = Number(result.insertId);

  const plans = await conn.query(
    `SELECT id FROM planes ORDER BY id ASC LIMIT 1`,
  );
  if (!plans.length) {
    throw new Error('No hay planes en BD para crear membresía de prueba');
  }
  const planId = Number(plans[0].id);
  const start = localDatePlus(-10);
  const end = localDatePlus(daysUntilEnd);

  const sub = await conn.query(
    `INSERT INTO suscripciones (socio_id, plan_id, fecha_inicio, fecha_fin, estado)
     VALUES (?,?,?,?,?)`,
    [memberId, planId, start, end, estado],
  );

  return {
    memberId,
    dni,
    membershipId: Number(sub.insertId),
    endDate: end,
    daysUntilEnd,
  };
}

async function countAlerts(conn, memberId, type) {
  const rows = await conn.query(
    `SELECT COUNT(*) AS c FROM notifications
     WHERE member_id=? AND type=? AND DATE(created_at)=CURDATE()`,
    [memberId, type],
  );
  return Number(rows[0].c);
}

async function listAlertKeys(conn, memberId) {
  const rows = await conn.query(
    `SELECT id, type, title, JSON_UNQUOTE(JSON_EXTRACT(data, '$.alertKey')) AS alertKey
     FROM notifications
     WHERE member_id=? AND DATE(created_at)=CURDATE()
     ORDER BY id DESC`,
    [memberId],
  );
  return rows;
}

async function main() {
  console.log(`API: ${base}`);
  console.log(`Alert days env: ${process.env.MEMBERSHIP_ALERT_DAYS ?? '(default 7,3,1,0)'}`);
  console.log(`Enabled: ${process.env.MEMBERSHIP_ALERTS_ENABLED ?? 'true'}`);
  console.log('---');

  const health = await req('/health');
  if (health.status !== 200) {
    console.error('API no disponible. Inicia NestJS (npm run start:dev).');
    process.exit(1);
  }
  record('F17-00', 'Health', true, `HTTP ${health.status}`);

  // F17-06 sin auth
  const noAuth = await req('/membership-alerts/run', { method: 'POST' });
  record(
    'F17-06',
    'Sin autenticación',
    noAuth.status === 401,
    `HTTP ${noAuth.status}`,
  );

  const adminLogin = await req('/auth/login', {
    method: 'POST',
    body: JSON.stringify({
      email: process.env.AUDIT_ADMIN_EMAIL ?? 'admin@gym.com',
      password: process.env.AUDIT_ADMIN_PASSWORD ?? '123456',
    }),
  });
  const adminToken = unwrap(adminLogin.body)?.accessToken;
  if (!adminToken) {
    record('F17-LOGIN', 'Login admin', false, JSON.stringify(adminLogin.body));
    process.exit(1);
  }
  record('F17-LOGIN', 'Login admin', true, `HTTP ${adminLogin.status}`);

  const conn = await dbConnect();
  await ensureStaff(conn);

  const recepLogin = await req('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email: 'recep@test.com', password: '123456' }),
  });
  const recepToken = unwrap(recepLogin.body)?.accessToken;
  const noPerm = await req('/membership-alerts/run', {
    method: 'POST',
    headers: auth(recepToken),
  });
  record(
    'F17-07',
    'Sin permisos (recepcionista)',
    noPerm.status === 403,
    `HTTP ${noPerm.status}`,
  );

  // Escenarios controlados de fechas
  const m3 = await createMemberWithMembership(conn, 'D3', 3);
  const m0 = await createMemberWithMembership(conn, 'D0', 0);
  const m1 = await createMemberWithMembership(conn, 'D1', 1);
  const m7 = await createMemberWithMembership(conn, 'D7', 7);
  const m2 = await createMemberWithMembership(conn, 'D2', 2); // fuera de umbral default
  const m8 = await createMemberWithMembership(conn, 'D8', 8); // fuera
  const mY = await createMemberWithMembership(conn, 'YM', -1); // vencida ayer
  const mFar = await createMemberWithMembership(conn, 'FAR', 30);

  // Baseline: ejecución normal
  const run1 = await req('/membership-alerts/run', {
    method: 'POST',
    headers: auth(adminToken),
  });
  const r1 = unwrap(run1.body);
  await sleep(800); // notifyMember es fire-and-forget

  record(
    'F17-01',
    'Endpoint normal',
    run1.status === 200 || run1.status === 201,
    `HTTP ${run1.status} body=${JSON.stringify(r1)}`,
  );

  const expiring3 = await countAlerts(conn, m3.memberId, 'membership.expiring');
  const expiring0 = await countAlerts(conn, m0.memberId, 'membership.expiring');
  const expiring1 = await countAlerts(conn, m1.memberId, 'membership.expiring');
  const expiring7 = await countAlerts(conn, m7.memberId, 'membership.expiring');
  const expiring2 = await countAlerts(conn, m2.memberId, 'membership.expiring');
  const expiring8 = await countAlerts(conn, m8.memberId, 'membership.expiring');
  const expiringFar = await countAlerts(conn, mFar.memberId, 'membership.expiring');
  const expiredY = await countAlerts(conn, mY.memberId, 'membership.updated');

  const yState = await conn.query(
    `SELECT estado FROM suscripciones WHERE id=?`,
    [mY.membershipId],
  );

  record(
    'F17-03',
    'Próxima a vencer (3 días)',
    expiring3 === 1,
    `notifications today=${expiring3} end=${m3.endDate}`,
  );
  record(
    'F17-03b',
    'Umbral 0 días (vence hoy)',
    expiring0 === 1,
    `notifications today=${expiring0}`,
  );
  record(
    'F17-03c',
    'Umbral 1 día',
    expiring1 === 1,
    `notifications today=${expiring1}`,
  );
  record(
    'F17-03d',
    'Umbral 7 días',
    expiring7 === 1,
    `notifications today=${expiring7}`,
  );
  record(
    'F17-08a',
    'Fuera de umbral (2 días)',
    expiring2 === 0,
    `notifications today=${expiring2}`,
  );
  record(
    'F17-08b',
    'Fuera de umbral (8 días)',
    expiring8 === 0,
    `notifications today=${expiring8}`,
  );
  record(
    'F17-08c',
    'Muy futura (30 días)',
    expiringFar === 0,
    `notifications today=${expiringFar}`,
  );
  record(
    'F17-04',
    'Membresía vencida (ayer)',
    expiredY === 1 && yState[0]?.estado === 'vencida',
    `notifications=${expiredY} estado=${yState[0]?.estado}`,
  );

  // Idempotencia
  const beforeKeys = await listAlertKeys(conn, m3.memberId);
  const run2 = await req('/membership-alerts/run', {
    method: 'POST',
    headers: auth(adminToken),
  });
  const r2 = unwrap(run2.body);
  await sleep(800);
  const afterCount3 = await countAlerts(conn, m3.memberId, 'membership.expiring');
  const afterCountY = await countAlerts(conn, mY.memberId, 'membership.updated');
  record(
    'F17-05',
    'Idempotencia (2do run)',
    afterCount3 === 1 && afterCountY === 1 && Number(r2?.skipped ?? 0) >= 0,
    `sent=${r2?.sent} skipped=${r2?.skipped} expired=${r2?.expired} n3=${afterCount3} nY=${afterCountY} keys=${beforeKeys.map((k) => k.alertKey).join(',')}`,
  );

  // Sin candidatos nuevos: crear solo membresía fuera de umbral y correr
  // (ya cubierto por m2/m8/mFar; verificamos respuesta estable)
  record(
    'F17-02',
    'Sin candidatos nuevos (run estable)',
    (run2.status === 200 || run2.status === 201) && typeof r2?.sent === 'number',
    `HTTP ${run2.status} sent=${r2?.sent} skipped=${r2?.skipped}`,
  );

  // Aislamiento: socio A vs B
  const memberALogin = await req('/auth/member/login', {
    method: 'POST',
    body: JSON.stringify({ login: m3.dni, password: '123456' }),
  });
  const memberBLogin = await req('/auth/member/login', {
    method: 'POST',
    body: JSON.stringify({ login: m7.dni, password: '123456' }),
  });
  const tokenA = unwrap(memberALogin.body)?.accessToken;
  const tokenB = unwrap(memberBLogin.body)?.accessToken;

  const listA = await req('/notifications', { headers: auth(tokenA) });
  const listB = await req('/notifications', { headers: auth(tokenB) });
  const itemsA = unwrap(listA.body)?.items ?? [];
  const itemsB = unwrap(listB.body)?.items ?? [];

  const aSeesB = itemsA.some((n) => {
    const mid = n?.data?.membershipId;
    return mid === m7.membershipId;
  });
  const bSeesA = itemsB.some((n) => {
    const mid = n?.data?.membershipId;
    return mid === m3.membershipId;
  });
  const aHasOwn = itemsA.some((n) => n.type === 'membership.expiring');
  const bHasOwn = itemsB.some((n) => n.type === 'membership.expiring');

  record(
    'F17-10',
    'Aislamiento A/B',
    !aSeesB && !bSeesA && aHasOwn && bHasOwn,
    `A items=${itemsA.length} own=${aHasOwn} seesB=${aSeesB}; B items=${itemsB.length} own=${bHasOwn} seesA=${bSeesA}`,
  );

  // Staff no lee /notifications (diseño Fase 16)
  const staffNotif = await req('/notifications', { headers: auth(adminToken) });
  record(
    'F17-10b',
    'Staff no accede a /notifications',
    staffNotif.status === 403,
    `HTTP ${staffNotif.status}`,
  );

  // Frontera fechas resumen
  record(
    'F17-08',
    'Fechas límite (0/1/3/7 sí; 2/8/30 no; -1 vencida)',
    expiring0 === 1 &&
      expiring1 === 1 &&
      expiring3 === 1 &&
      expiring7 === 1 &&
      expiring2 === 0 &&
      expiring8 === 0 &&
      expiringFar === 0 &&
      expiredY === 1,
    'umbrales default 7,3,1,0 verificados',
  );

  // Limpieza de datos de prueba (socios + cascada)
  for (const m of [m3, m0, m1, m7, m2, m8, mY, mFar]) {
    await conn.query(`DELETE FROM socios WHERE id=?`, [m.memberId]);
  }
  await conn.end();

  const failed = results.filter((r) => !r.ok);
  console.log('---');
  console.log(`TOTAL=${results.length} PASS=${results.length - failed.length} FAIL=${failed.length}`);
  if (failed.length) {
    process.exitCode = 1;
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
