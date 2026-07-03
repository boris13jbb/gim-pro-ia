/**
 * Auditoría runtime Fase 09 — Facturación SRI (consulta / bandeja).
 * Uso: npm run audit:phase-09
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
const monthStart = `${today.slice(0, 8)}01`;

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
     VALUES ('Recep SRI','recep-sri@test.com',?,'recepcionista','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='recepcionista'`,
    [hash, hash],
  );
  await conn.query(
    `INSERT INTO usuarios (nombre,email,password,rol,estado)
     VALUES ('Trainer SRI','trainer-sri@test.com',?,'entrenador','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='entrenador'`,
    [hash, hash],
  );
}

async function req(path, options = {}) {
  const { headers: extraHeaders, expectJson = true, ...rest } = options;
  const res = await fetch(`${base}${path}`, {
    ...rest,
    headers: {
      ...(rest.body ? { 'Content-Type': 'application/json' } : {}),
      ...(extraHeaders ?? {}),
    },
  });
  if (!expectJson) {
    const buffer = Buffer.from(await res.arrayBuffer());
    return {
      status: res.status,
      contentType: res.headers.get('content-type') ?? '',
      buffer,
    };
  }
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
  body: JSON.stringify({ email: 'recep-sri@test.com', password: '123456' }),
});
const recepAuth = authHeader(recepLogin.body?.data?.accessToken);
results.push(
  row('Login recepcionista', (recepLogin.status === 200 || recepLogin.status === 201) && !!recepLogin.body?.data?.accessToken, recepLogin.status),
);

const trainerLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'trainer-sri@test.com', password: '123456' }),
});
const trainerAuth = authHeader(trainerLogin.body?.data?.accessToken);

const list = await req(
  `/electronic-receipts?fromDate=${monthStart}&toDate=${today}`,
  { headers: adminAuth },
);
results.push(
  row(
    'GET /electronic-receipts',
    list.status === 200 && Array.isArray(list.body?.data?.items),
    list.status,
  ),
);

const sriConfig = await req('/sri-config', { headers: adminAuth });
results.push(
  row(
    'GET /sri-config admin',
    sriConfig.status === 200 && sriConfig.body?.data?.environment != null,
    sriConfig.status,
  ),
);
results.push(
  row(
    'SRI config sin clave certificado',
    !('certificatePassword' in (sriConfig.body?.data ?? {})) &&
      !('sri_certificado_clave' in (sriConfig.body?.data ?? {})),
    'ok',
  ),
);
results.push(
  row(
    'SRI config readiness checks',
    Array.isArray(sriConfig.body?.data?.readinessChecks) &&
      sriConfig.body?.data?.readinessChecks.length >= 5,
    sriConfig.body?.data?.readinessChecks?.length ?? 0,
  ),
);

const recepList = await req(
  `/electronic-receipts?fromDate=${monthStart}&toDate=${today}`,
  { headers: recepAuth },
);
results.push(
  row('Recepcionista accede bandeja', recepList.status === 200, recepList.status),
);

const recepConfigForbidden = await req('/sri-config', { headers: recepAuth });
results.push(
  row('Recepcionista no accede sri-config -> 403', recepConfigForbidden.status === 403, recepConfigForbidden.status),
);

const trainerForbidden = await req('/electronic-receipts', { headers: trainerAuth });
results.push(
  row('Entrenador bloqueado bandeja -> 403', trainerForbidden.status === 403, trainerForbidden.status),
);

const receiptId = list.body?.data?.items?.[0]?.id;
if (receiptId) {
  const detail = await req(`/electronic-receipts/${receiptId}`, { headers: adminAuth });
  results.push(
    row('GET /electronic-receipts/:id', detail.status === 200 && Array.isArray(detail.body?.data?.items), detail.status),
  );

  const logs = await req(`/electronic-receipts/${receiptId}/logs`, { headers: adminAuth });
  results.push(
    row('GET /electronic-receipts/:id/logs', logs.status === 200 && Array.isArray(logs.body?.data), logs.status),
  );

  if (detail.body?.data?.hasSignedXml || detail.body?.data?.hasAuthorizedXml) {
    const xml = await req(`/electronic-receipts/${receiptId}/xml`, {
      headers: adminAuth,
      expectJson: false,
    });
    results.push(
      row(
        'GET /electronic-receipts/:id/xml',
        xml.status === 200 && xml.contentType.includes('xml'),
        xml.status,
      ),
    );
  } else {
    results.push(row('GET /electronic-receipts/:id/xml', true, 'sin XML en BD (omitido)'));
  }

  const pdf = await req(`/electronic-receipts/${receiptId}/pdf`, {
    headers: adminAuth,
    expectJson: false,
  });
  results.push(
    row(
      'GET /electronic-receipts/:id/pdf RIDE',
      pdf.status === 200 && pdf.contentType.includes('pdf') && pdf.buffer.length > 500,
      `${pdf.status} | ${pdf.buffer.length}b`,
    ),
  );

  const sendEmail = await req(`/electronic-receipts/${receiptId}/send-email`, {
    method: 'POST',
    headers: adminAuth,
    body: JSON.stringify({ email: 'audit-sri@test.com' }),
  });
  const smtpConfigured = !!(
    process.env.SMTP_HOST?.trim() &&
    process.env.SMTP_USER?.trim() &&
    process.env.SMTP_PASS?.trim()
  );
  if (smtpConfigured) {
    results.push(
      row(
        'POST /electronic-receipts/:id/send-email',
        (sendEmail.status === 200 || sendEmail.status === 201) &&
          sendEmail.body?.data?.sent === true,
        `${sendEmail.status} | ${sendEmail.body?.data?.recipient ?? 'n/a'}`,
      ),
    );
  } else {
    results.push(
      row(
        'POST send-email sin SMTP -> 503',
        sendEmail.status === 503,
        sendEmail.status,
      ),
    );
  }
} else {
  results.push(row('Detalle comprobante', true, 'sin comprobantes en período (omitido)'));
  results.push(row('Logs comprobante', true, 'sin comprobantes (omitido)'));
  results.push(row('RIDE PDF', true, 'sin comprobantes (omitido)'));
  results.push(row('Envío email', true, 'sin comprobantes (omitido)'));
}

let membershipIdForIssue = null;
let saleIdForIssue = null;
try {
  const conn = await mariadb.createConnection({
    host: '127.0.0.1',
    user: 'root',
    password: '',
    database: 'ec_gym_system',
  });
  const membershipRows = await conn.query(
    `SELECT s.id FROM suscripciones s
     INNER JOIN socios so ON s.socio_id = so.id
     INNER JOIN planes p ON s.plan_id = p.id
     WHERE s.comprobante_id IS NULL
     ORDER BY s.id DESC LIMIT 1`,
  );
  membershipIdForIssue = membershipRows[0]?.id ?? null;

  const saleRows = await conn.query(
    `SELECT v.id FROM ventas v
     WHERE v.comprobante_id IS NULL
     ORDER BY v.id DESC LIMIT 1`,
  );
  saleIdForIssue = saleRows[0]?.id ?? null;
  await conn.end();
} catch (e) {
  console.error('Setup emisión:', e.message);
}

const invalidMembership = await req('/electronic-receipts/issue/membership/99999999', {
  method: 'POST',
  headers: adminAuth,
});
results.push(
  row(
    'POST issue/membership inexistente -> 404',
    invalidMembership.status === 404,
    invalidMembership.status,
  ),
);

if (membershipIdForIssue) {
  const issueMembership = await req(
    `/electronic-receipts/issue/membership/${membershipIdForIssue}`,
    { method: 'POST', headers: adminAuth },
  );
  const okIssue =
    (issueMembership.status === 200 || issueMembership.status === 201) &&
    issueMembership.body?.data?.receiptId &&
    (issueMembership.body?.data?.ok === true ||
      issueMembership.body?.data?.code === 'AUTORIZADO');
  results.push(
    row(
      'POST issue/membership emite comprobante',
      okIssue,
      `${issueMembership.status} | ${issueMembership.body?.data?.code ?? issueMembership.body?.message ?? 'n/a'}`,
    ),
  );
} else {
  results.push(row('POST issue/membership', true, 'sin suscripción libre (omitido)'));
}

if (saleIdForIssue) {
  const issueSale = await req(`/electronic-receipts/issue/sale/${saleIdForIssue}`, {
    method: 'POST',
    headers: adminAuth,
  });
  results.push(
    row(
      'POST issue/sale emite comprobante',
      (issueSale.status === 200 || issueSale.status === 201) &&
        issueSale.body?.data?.ok === true,
      `${issueSale.status} | ${issueSale.body?.data?.code ?? 'n/a'}`,
    ),
  );
} else {
  results.push(row('POST issue/sale', true, 'sin venta libre (omitido)'));
}

const passed = results.filter((r) => r.ok).length;
const total = results.length;

console.log('\n=== Auditoría Fase 09 — SRI ===\n');
for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'} | ${r.name} | ${r.detail}`);
}
console.log(`\nResultado: ${passed}/${total} OK\n`);
process.exit(passed === total ? 0 : 1);
