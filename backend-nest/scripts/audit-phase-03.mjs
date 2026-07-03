/**
 * Auditoría runtime Fase 03 — socios, planes y membresías.
 * Uso: npm run audit:phase-03
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const base = process.env.API_BASE ?? 'http://localhost:3000/api';
const today = new Date().toISOString().slice(0, 10);
const testDni = `AUD${Date.now()}`.slice(0, 20);

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

const recepLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'recep@test.com', password: '123456' }),
});
const recepToken = recepLogin.body?.data?.accessToken;
const recepAuth = authHeader(recepToken);

const trainerLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'trainer@test.com', password: '123456' }),
});
const trainerToken = trainerLogin.body?.data?.accessToken;
const trainerAuth = authHeader(trainerToken);

const membersList = await req('/members?limit=5', { headers: adminAuth });
const hasMembers =
  membersList.status === 200 &&
  Array.isArray(membersList.body?.data?.items) &&
  membersList.body.data.items.length > 0;
results.push(row('GET /members listado', hasMembers, membersList.body?.data?.meta?.total ?? membersList.status));

const memberId = membersList.body?.data?.items?.[0]?.id;
const memberOne = await req(`/members/${memberId}`, { headers: adminAuth });
results.push(row('GET /members/:id', memberOne.status === 200, memberOne.status));

const membershipSummary = await req(`/members/${memberId}/membership`, { headers: adminAuth });
const hasEffectiveStatus =
  membershipSummary.status === 200 &&
  typeof membershipSummary.body?.data?.effectiveStatus === 'string';
results.push(
  row('GET /members/:id/membership (effectiveStatus)', hasEffectiveStatus, membershipSummary.body?.data?.effectiveStatus ?? membershipSummary.status),
);

const memberHistory = await req(`/members/${memberId}/memberships`, { headers: adminAuth });
results.push(
  row('GET /members/:id/memberships historial', memberHistory.status === 200 && Array.isArray(memberHistory.body?.data), memberHistory.status),
);

const createdMember = await req('/members', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: 'Socio Auditoría',
    dni: testDni,
    email: `audit_${Date.now()}@test.com`,
    telefono: '0990000001',
  }),
});
const newMemberId = createdMember.body?.data?.id;
results.push(
  row('POST /members crear socio', (createdMember.status === 200 || createdMember.status === 201) && !!newMemberId, createdMember.status),
);

const duplicateDni = await req('/members', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: 'Duplicado',
    dni: testDni,
  }),
});
results.push(row('POST /members DNI duplicado', duplicateDni.status === 409, duplicateDni.status));

const updatedMember = await req(`/members/${newMemberId}`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ nombre: 'Socio Auditoría Editado' }),
});
results.push(row('PATCH /members/:id', updatedMember.status === 200, updatedMember.status));

const plansList = await req('/plans', { headers: adminAuth });
const planId = plansList.body?.data?.[0]?.id;
results.push(
  row('GET /plans', plansList.status === 200 && Array.isArray(plansList.body?.data) && plansList.body.data.length > 0, plansList.body?.data?.length ?? plansList.status),
);

const planOne = await req(`/plans/${planId}`, { headers: adminAuth });
results.push(row('GET /plans/:id', planOne.status === 200, planOne.status));

const createdPlan = await req('/plans', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: `Plan Audit ${Date.now()}`,
    precio: 19.99,
    duracionDias: 15,
    descripcion: 'Plan de prueba auditoría',
  }),
});
const newPlanId = createdPlan.body?.data?.id;
results.push(
  row('POST /plans (admin)', (createdPlan.status === 200 || createdPlan.status === 201) && !!newPlanId, createdPlan.status),
);

const recepCreatePlan = await req('/plans', {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({
    nombre: 'Plan Recep Denegado',
    precio: 10,
    duracionDias: 7,
  }),
});
results.push(row('POST /plans recepcionista 403', recepCreatePlan.status === 403, recepCreatePlan.status));

const createdMembership = await req('/memberships', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    memberId: newMemberId,
    planId: newPlanId ?? planId,
    startDate: today,
  }),
});
const membershipId = createdMembership.body?.data?.id;
const endDate = createdMembership.body?.data?.endDate;
const effectiveOnCreate = createdMembership.body?.data?.effectiveStatus;
results.push(
  row('POST /memberships crear', (createdMembership.status === 200 || createdMembership.status === 201) && !!membershipId, createdMembership.status),
);
results.push(
  row('Membresía calcula endDate', !!endDate, endDate ?? 'sin endDate'),
);
results.push(
  row('Membresía effectiveStatus activa', effectiveOnCreate === 'activa', effectiveOnCreate ?? 'N/A'),
);

const membershipDetail = await req(`/memberships/${membershipId}`, { headers: adminAuth });
results.push(row('GET /memberships/:id', membershipDetail.status === 200, membershipDetail.status));

const membershipsList = await req('/memberships', { headers: adminAuth });
results.push(
  row('GET /memberships listado', membershipsList.status === 200 && Array.isArray(membershipsList.body?.data), membershipsList.status),
);

const cancelled = await req(`/memberships/${membershipId}/cancel`, {
  method: 'PATCH',
  headers: adminAuth,
});
results.push(
  row('PATCH /memberships/:id/cancel', cancelled.status === 200 && cancelled.body?.data?.status === 'vencida', cancelled.body?.data?.status ?? cancelled.status),
);

const trainerMembers = await req('/members?limit=1', { headers: trainerAuth });
results.push(row('Entrenador GET /members', trainerMembers.status === 200, trainerMembers.status));

const trainerMemberships = await req('/memberships', { headers: trainerAuth });
results.push(row('Entrenador GET /memberships 403', trainerMemberships.status === 403, trainerMemberships.status));

const recepCreateMember = await req('/members', {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({
    nombre: 'Socio Recep',
    dni: `RCP${Date.now()}`.slice(0, 20),
  }),
});
results.push(
  row('Recepcionista POST /members', recepCreateMember.status === 200 || recepCreateMember.status === 201, recepCreateMember.status),
);

const memberPassword = await req(`/members/${newMemberId}/password`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ password: 'SocioApp123' }),
});
results.push(row('PATCH /members/:id/password', memberPassword.status === 200, memberPassword.status));

const memberLogin = await req('/auth/member/login', {
  method: 'POST',
  body: JSON.stringify({ login: testDni, password: 'SocioApp123' }),
});
const memberToken = memberLogin.body?.data?.accessToken;
results.push(
  row('POST /auth/member/login', (memberLogin.status === 200 || memberLogin.status === 201) && !!memberToken, memberLogin.status),
);

const memberMe = await req('/auth/me', {
  headers: { Authorization: `Bearer ${memberToken}` },
});
results.push(
  row('GET /auth/me socio', memberMe.status === 200 && memberMe.body?.data?.dni === testDni, memberMe.body?.data?.dni ?? memberMe.status),
);

const exportRes = await fetch(`${base}/memberships/export/excel`, {
  headers: { Authorization: `Bearer ${adminToken}` },
});
const exportType = exportRes.headers.get('content-type') ?? '';
results.push(
  row('GET /memberships/export/excel', exportRes.status === 200 && exportType.includes('spreadsheetml'), exportType || exportRes.status),
);

const pngBuffer = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64',
);
const photoForm = new FormData();
photoForm.append('photo', new Blob([pngBuffer], { type: 'image/png' }), 'audit.png');
const photoUpload = await fetch(`${base}/members/${newMemberId}/photo`, {
  method: 'POST',
  headers: { Authorization: `Bearer ${adminToken}` },
  body: photoForm,
});
const photoBody = await photoUpload.json().catch(() => ({}));
results.push(
  row('POST /members/:id/photo', photoUpload.status === 200 || photoUpload.status === 201, photoBody?.data?.photoUrl ?? photoUpload.status),
);

for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'}\t${r.name}\t${r.detail}`);
}

const failed = results.filter((r) => !r.ok).length;
process.exit(failed > 0 ? 1 : 0);
