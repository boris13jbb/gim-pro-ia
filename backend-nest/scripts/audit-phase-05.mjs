/**
 * Auditoría runtime Fase 05 — progreso físico y rutinas.
 * Uso: npm run audit:phase-05
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
const testDni = `PRG${Date.now()}`.slice(0, 20);

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
     VALUES ('Entrenador Test','trainer@test.com',?,'entrenador','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='entrenador'`,
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

const trainerLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'trainer@test.com', password: '123456' }),
});
const trainerAuth = authHeader(trainerLogin.body?.data?.accessToken);

const createdMember = await req('/members', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: 'Socio Progreso Audit',
    dni: testDni,
    email: `prg_${Date.now()}@test.com`,
    password: 'SocioApp123',
  }),
});
const memberId = createdMember.body?.data?.id;
results.push(
  row('POST /members socio prueba', (createdMember.status === 200 || createdMember.status === 201) && !!memberId, createdMember.status),
);

const createMeasurement = await req(`/body-progress/members/${memberId}/measurements`, {
  method: 'POST',
  headers: trainerAuth,
  body: JSON.stringify({
    measuredAt: today,
    weight: 78.5,
    bodyFat: 18.2,
    waist: 82,
    arm: 34,
  }),
});
const measurementId = createMeasurement.body?.data?.id;
results.push(
  row('POST body measurement', (createMeasurement.status === 200 || createMeasurement.status === 201) && !!measurementId, createMeasurement.status),
);

const listMeasurements = await req(`/body-progress/members/${memberId}/measurements`, {
  headers: adminAuth,
});
results.push(
  row('GET body measurements + chart', listMeasurements.status === 200 && Array.isArray(listMeasurements.body?.data?.chart?.labels), listMeasurements.body?.data?.items?.length ?? listMeasurements.status),
);

const createRoutine = await req(`/workout-routines/members/${memberId}`, {
  method: 'POST',
  headers: trainerAuth,
  body: JSON.stringify({
    day1: 'Press banca 4x12',
    day2: 'Sentadilla 4x10',
    notes: 'Descanso 90 segundos',
  }),
});
const routineId = createRoutine.body?.data?.id;
results.push(
  row('POST workout routine', (createRoutine.status === 200 || createRoutine.status === 201) && !!routineId, createRoutine.status),
);

const currentRoutine = await req(`/workout-routines/members/${memberId}/current`, {
  headers: adminAuth,
});
results.push(
  row('GET current workout routine', currentRoutine.status === 200 && currentRoutine.body?.data?.current?.id === routineId, currentRoutine.status),
);

const routineHistory = await req(`/workout-routines/members/${memberId}`, {
  headers: adminAuth,
});
results.push(
  row('GET workout routine history', routineHistory.status === 200 && Array.isArray(routineHistory.body?.data) && routineHistory.body.data.length >= 1, routineHistory.status),
);

const deleteMeasurement = await req(`/body-progress/measurements/${measurementId}`, {
  method: 'DELETE',
  headers: trainerAuth,
});
results.push(
  row('DELETE body measurement', deleteMeasurement.status === 200 && deleteMeasurement.body?.data?.deleted === true, deleteMeasurement.status),
);

const recepLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'recep@test.com', password: '123456' }),
});
const recepAuth = authHeader(recepLogin.body?.data?.accessToken);
const recepCreateMeasurement = await req(`/body-progress/members/${memberId}/measurements`, {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({ measuredAt: today, weight: 77 }),
});
results.push(row('Recepcionista POST measurement 403', recepCreateMeasurement.status === 403, recepCreateMeasurement.status));

const memberLogin = await req('/auth/member/login', {
  method: 'POST',
  body: JSON.stringify({ login: testDni, password: 'SocioApp123' }),
});
const memberToken = memberLogin.body?.data?.accessToken;
const memberProgress = await req('/body-progress/me', {
  headers: authHeader(memberToken),
});
results.push(
  row('Socio GET /body-progress/me', memberProgress.status === 200, memberProgress.status),
);

const memberRoutine = await req('/workout-routines/me/current', {
  headers: authHeader(memberToken),
});
results.push(
  row('Socio GET /workout-routines/me/current', memberRoutine.status === 200 && !!memberRoutine.body?.data?.current, memberRoutine.status),
);

const otherMemberList = await req('/body-progress/members/1/measurements', {
  headers: authHeader(memberToken),
});
results.push(row('Socio acceso otro socio 403', otherMemberList.status === 403, otherMemberList.status));

for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'}\t${r.name}\t${r.detail}`);
}

const failed = results.filter((r) => !r.ok).length;
process.exit(failed > 0 ? 1 : 0);
