/**
 * Auditoría runtime Fase 04 — asistencias y QR.
 * Uso: npm run audit:phase-04
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const base = process.env.API_BASE ?? 'http://localhost:3000/api';
function localToday() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}
const today = localToday();
const testDni = `ATT${Date.now()}`.slice(0, 20);

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
      if (!process.env[key]) process.env[key] = value;
    }
  } catch {
    // .env opcional
  }
}

loadEnvFile();

async function setupUsers(conn) {
  const hash = await bcrypt.hash('123456', 10);
  await conn.query(
    `INSERT INTO usuarios (nombre,email,password,rol,estado)
     VALUES ('Recep Test','recep@test.com',?,'recepcionista','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='recepcionista'`,
    [hash, hash],
  );
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

function row(name, ok, detail) {
  return { name, ok, detail };
}

function authHeader(token) {
  return { Authorization: `Bearer ${token}` };
}

const results = [];

try {
  const conn = await mariadb.createConnection({
    host: '127.0.0.1',
    user: 'root',
    password: '',
    database: 'ec_gym_system',
  });
  await setupUsers(conn);
  await conn.end();
} catch (e) {
  console.error('Setup BD:', e.message);
}

const adminLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'admin@gym.com', password: '123456' }),
});
const adminToken = adminLogin.body?.data?.accessToken;
const adminAuth = authHeader(adminToken);
results.push(
  row('Login admin', (adminLogin.status === 200 || adminLogin.status === 201) && !!adminToken, adminLogin.status),
);

const createdMember = await req('/members', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: 'Socio Asistencia Audit',
    dni: testDni,
    email: `att_${Date.now()}@test.com`,
    telefono: '0990000099',
    password: 'SocioApp123',
  }),
});
const memberId = createdMember.body?.data?.id;
results.push(
  row('POST /members socio prueba', (createdMember.status === 200 || createdMember.status === 201) && !!memberId, createdMember.status),
);

const plans = await req('/plans', { headers: adminAuth });
const planId =
  plans.body?.data?.find((p) => Number(p.duracion_dias) >= 30)?.id ??
  plans.body?.data?.[0]?.id;

const membership = await req('/memberships', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    memberId,
    planId,
    startDate: today,
  }),
});
results.push(
  row('POST /memberships activa para asistencia', membership.status === 200 || membership.status === 201, membership.status),
);

const validateOk = await req('/attendance/validate', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ dni: testDni }),
});
results.push(
  row('POST /attendance/validate acceso permitido', (validateOk.status === 200 || validateOk.status === 201) && validateOk.body?.data?.canAccess === true, validateOk.body?.data?.reason ?? validateOk.status),
);

const validateMissing = await req('/attendance/validate', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ dni: '0000000000' }),
});
results.push(
  row('POST /attendance/validate DNI inexistente', (validateMissing.status === 200 || validateMissing.status === 201) && validateMissing.body?.data?.found === false, validateMissing.body?.data?.reason ?? validateMissing.status),
);

const register = await req('/attendance/register', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ memberId, method: 'manual' }),
});
results.push(
  row('POST /attendance/register', (register.status === 200 || register.status === 201) && !!register.body?.data?.id, register.status),
);
results.push(
  row('POST /attendance/register guarda method', register.body?.data?.method === 'manual', register.body?.data?.method ?? 'sin method'),
);

const duplicate = await req('/attendance/register', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ memberId, method: 'manual' }),
});
results.push(row('POST /attendance/register duplicado 409', duplicate.status === 409, duplicate.status));

const todayList = await req('/attendance/today', { headers: adminAuth });
results.push(
  row('GET /attendance/today', todayList.status === 200 && Array.isArray(todayList.body?.data), todayList.body?.data?.length ?? todayList.status),
);

const report = await req(`/attendance/report?from=${today}&to=${today}&memberId=${memberId}`, {
  headers: adminAuth,
});
results.push(
  row('GET /attendance/report', report.status === 200 && report.body?.data?.totalVisits >= 1, report.body?.data?.totalVisits ?? report.status),
);

const ranking = await req(`/attendance/report/ranking?from=${today}&to=${today}`, {
  headers: adminAuth,
});
results.push(
  row('GET /attendance/report/ranking', ranking.status === 200 && Array.isArray(ranking.body?.data?.leaders), ranking.status),
);

const qrValidate = await req('/qr-access/validate', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ qrPayload: testDni }),
});
results.push(
  row('POST /qr-access/validate', (qrValidate.status === 200 || qrValidate.status === 201) && qrValidate.body?.data?.canAccess === true, qrValidate.status),
);

const card = await req(`/qr-access/members/${memberId}/card`, { headers: adminAuth });
results.push(
  row('GET /qr-access/members/:id/card', card.status === 200 && card.body?.data?.qrPayload === testDni, card.status),
);

const memberLogin = await req('/auth/member/login', {
  method: 'POST',
  body: JSON.stringify({ login: testDni, password: 'SocioApp123' }),
});
const memberToken = memberLogin.body?.data?.accessToken;
const selfRegister = await req('/attendance/self', {
  method: 'POST',
  headers: authHeader(memberToken),
});
results.push(
  row('POST /attendance/self duplicado mismo día', selfRegister.status === 409, selfRegister.status),
);

const trainerLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'trainer@test.com', password: '123456' }),
});
const trainerAuth = authHeader(trainerLogin.body?.data?.accessToken);
const trainerToday = await req('/attendance/today', { headers: trainerAuth });
results.push(row('Entrenador GET /attendance/today', trainerToday.status === 200, trainerToday.status));

for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'}\t${r.name}\t${r.detail}`);
}

const failed = results.filter((r) => !r.ok).length;
process.exit(failed > 0 ? 1 : 0);
