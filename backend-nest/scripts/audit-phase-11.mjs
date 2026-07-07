/**
 * Auditoría Fase 11 — asistente IA (REST) para socio.
 * Uso: npm run audit:phase-11
 */
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const base = process.env.API_BASE ?? 'http://localhost:3000/api';
const testDni = `AIA${Date.now()}`.slice(0, 20);

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
  const aiProvider =
    process.env.AI_PROVIDER?.trim().toLowerCase() === 'ollama'
      ? 'ollama'
      : 'gemini';
  const aiConfigured =
    aiProvider === 'ollama' || Boolean(process.env.GEMINI_API_KEY?.trim());

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
      nombre: 'Socio IA Audit',
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

  await req('/memberships', {
    method: 'POST',
    headers: authHeader(adminToken),
    body: JSON.stringify({
      memberId,
      planId,
      startDate: new Date().toISOString().slice(0, 10),
    }),
  });

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

  const staffOnAi = await req('/ai/conversations', {
    headers: authHeader(adminToken),
  });
  results.push(
    staffOnAi.status === 403
      ? ok('Admin bloqueado en /ai/conversations', 403)
      : fail('Admin bloqueado en /ai/conversations', staffOnAi.status),
  );

  const listConv = await req('/ai/conversations', {
    headers: authHeader(memberToken),
  });
  results.push(
    listConv.status === 200 && Array.isArray(listConv.body?.data)
      ? ok('GET /ai/conversations socio', listConv.body.data.length)
      : fail('GET /ai/conversations socio', listConv.status),
  );

  const chat = await req('/ai/chat', {
    method: 'POST',
    headers: authHeader(memberToken),
    body: JSON.stringify({ message: 'Hola, ¿cómo está mi membresía?' }),
  });

  if (aiConfigured) {
    const chatMessage = chat.body?.error?.message ?? '';
    const providerLabel = aiProvider === 'ollama' ? 'Ollama' : 'Gemini';
    results.push(
      chat.status === 200 && chat.body?.data?.reply
        ? ok(`POST /ai/chat con ${providerLabel}`, chat.body.data.conversationId)
        : chat.status === 429
          ? ok(`POST /ai/chat créditos ${providerLabel} agotados (429)`, chatMessage.slice(0, 80))
          : fail(`POST /ai/chat con ${providerLabel}`, `${chat.status} ${chatMessage}`),
    );

    const convId = chat.body?.data?.conversationId;
    if (convId) {
      const detail = await req(`/ai/conversations/${convId}`, {
        headers: authHeader(memberToken),
      });
      results.push(
        detail.status === 200 &&
          Array.isArray(detail.body?.data?.messages) &&
          detail.body.data.messages.length >= 2
          ? ok('GET /ai/conversations/:id', detail.body.data.messages.length)
          : fail('GET /ai/conversations/:id', detail.status),
      );
    }
  } else {
    results.push(
      chat.status === 503
        ? ok('POST /ai/chat sin proveedor IA configurado', 503)
        : fail('POST /ai/chat sin proveedor IA configurado', chat.status),
    );
  }

  const passed = results.filter((r) => r.ok).length;
  console.log(`\nResultado: ${passed}/${results.length} OK`);
  if (!aiConfigured) {
    console.log(
      'Nota: define GEMINI_API_KEY o AI_PROVIDER=ollama en .env para probar respuesta real.',
    );
  } else if (aiProvider === 'ollama') {
    console.log(
      'Nota: requiere Ollama en ejecución (`ollama serve`) y modelo descargado (`ollama pull llama3.2`).',
    );
  }
  if (passed !== results.length) process.exit(1);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
