/**
 * Auditoría Fase 10 — endpoints socio para app Flutter.
 * Uso: npm run audit:phase-10
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const base = process.env.API_BASE ?? 'http://localhost:3000/api';
const testDni = `FLU${Date.now()}`.slice(0, 20);

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

function authHeader(token) {
  return { Authorization: `Bearer ${token}` };
}

function ok(name, detail) {
  console.log(`OK\t${name}\t${detail}`);
  return { name, ok: true, detail };
}

function fail(name, detail) {
  console.log(`FAIL\t${name}\t${detail}`);
  return { name, ok: false, detail };
}

async function main() {
  const results = [];

  const loginAdmin = await req('/auth/login', {
    method: 'POST',
    body: JSON.stringify({
      email: process.env.AUDIT_ADMIN_EMAIL ?? 'admin@gym.com',
      password: process.env.AUDIT_ADMIN_PASSWORD ?? '123456',
    }),
  });
  results.push(
    loginAdmin.status === 201
      ? ok('Login admin', loginAdmin.status)
      : fail('Login admin', loginAdmin.status),
  );
  const adminToken = loginAdmin.body?.data?.accessToken;

  const createMember = await req('/members', {
    method: 'POST',
    headers: authHeader(adminToken),
    body: JSON.stringify({
      nombre: 'Socio Flutter Audit',
      dni: testDni,
      email: `${testDni}@test.com`,
      password: '123456',
    }),
  });
  results.push(
    createMember.status === 201
      ? ok('Crear socio prueba', createMember.status)
      : fail('Crear socio prueba', createMember.status),
  );
  const memberId = createMember.body?.data?.id;

  const plans = await req('/plans', { headers: authHeader(adminToken) });
  const planId =
    plans.body?.data?.find((p) => Number(p.duracion_dias) >= 30)?.id ??
    plans.body?.data?.[0]?.id;

  const createMembership = await req('/memberships', {
    method: 'POST',
    headers: authHeader(adminToken),
    body: JSON.stringify({
      memberId,
      planId,
      startDate: new Date().toISOString().slice(0, 10),
    }),
  });
  results.push(
    createMembership.status === 201
      ? ok('Membresía activa', createMembership.status)
      : fail('Membresía activa', createMembership.status),
  );

  const memberLogin = await req('/auth/member/login', {
    method: 'POST',
    body: JSON.stringify({ login: testDni, password: '123456' }),
  });
  results.push(
    memberLogin.status === 201
      ? ok('Login socio', memberLogin.status)
      : fail('Login socio', memberLogin.status),
  );
  const memberToken = memberLogin.body?.data?.accessToken;

  const me = await req('/auth/me', { headers: authHeader(memberToken) });
  results.push(
    me.status === 200 && me.body?.data?.dni === testDni
      ? ok('GET /auth/me socio', me.body.data.dni)
      : fail('GET /auth/me socio', me.status),
  );

  const membership = await req('/members/me/membership', {
    headers: authHeader(memberToken),
  });
  results.push(
    membership.status === 200
      ? ok('GET /members/me/membership', membership.body?.data?.effectiveStatus ?? 'ok')
      : fail('GET /members/me/membership', membership.status),
  );

  const memberships = await req('/members/me/memberships', {
    headers: authHeader(memberToken),
  });
  results.push(
    memberships.status === 200
      ? ok('GET /members/me/memberships', Array.isArray(memberships.body?.data) ? memberships.body.data.length : 0)
      : fail('GET /members/me/memberships', memberships.status),
  );

  const qrCard = await req('/qr-access/me/card', {
    headers: authHeader(memberToken),
  });
  results.push(
    qrCard.status === 200 && qrCard.body?.data?.qrPayload === testDni
      ? ok('GET /qr-access/me/card', qrCard.body.data.qrPayload)
      : fail('GET /qr-access/me/card', qrCard.status),
  );

  const bodyProgress = await req('/body-progress/me', {
    headers: authHeader(memberToken),
  });
  results.push(
    bodyProgress.status === 200
      ? ok('GET /body-progress/me', bodyProgress.status)
      : fail('GET /body-progress/me', bodyProgress.status),
  );

  const workout = await req('/workout-routines/me/current', {
    headers: authHeader(memberToken),
  });
  results.push(
    workout.status === 200
      ? ok('GET /workout-routines/me/current', workout.status)
      : fail('GET /workout-routines/me/current', workout.status),
  );

  const staffOnMember = await req('/members/me/membership', {
    headers: authHeader(adminToken),
  });
  results.push(
    staffOnMember.status === 403
      ? ok('Admin bloqueado en /members/me/membership', 403)
      : fail('Admin bloqueado en /members/me/membership', staffOnMember.status),
  );

  const passed = results.filter((r) => r.ok).length;
  console.log(`\nResultado: ${passed}/${results.length} OK`);
  if (passed !== results.length) process.exit(1);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
