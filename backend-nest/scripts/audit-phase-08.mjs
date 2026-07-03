/**
 * Auditoría runtime Fase 08 — Reportes y exportaciones.
 * Uso: npm run audit:phase-08
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
     VALUES ('Recepcionista Reportes','recep-rep@test.com',?,'recepcionista','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='recepcionista'`,
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
  body: JSON.stringify({ email: 'recep-rep@test.com', password: '123456' }),
});
const recepToken = recepLogin.body?.data?.accessToken;
const recepAuth = authHeader(recepToken);
results.push(
  row('Login recepcionista', (recepLogin.status === 200 || recepLogin.status === 201) && !!recepToken, recepLogin.status),
);

const summary = await req(
  `/reports/financial/summary?fromDate=${monthStart}&toDate=${today}`,
  { headers: adminAuth },
);
results.push(
  row(
    'GET /reports/financial/summary',
    summary.status === 200 && typeof summary.body?.data?.totalIncome === 'number',
    summary.status,
  ),
);
results.push(
  row(
    'Summary incluye charts',
    !!summary.body?.data?.charts?.incomeByMonth,
    summary.body?.data?.charts ? 'ok' : 'missing',
  ),
);

const movements = await req(
  `/reports/financial/movements?fromDate=${monthStart}&toDate=${today}&limit=50`,
  { headers: adminAuth },
);
results.push(
  row(
    'GET /reports/financial/movements',
    movements.status === 200 && Array.isArray(movements.body?.data?.items),
    movements.status,
  ),
);

const excelExport = await req(
  `/reports/financial/export/excel?fromDate=${monthStart}&toDate=${today}`,
  { headers: adminAuth, expectJson: false },
);
results.push(
  row(
    'GET /reports/financial/export/excel',
    excelExport.status === 200 &&
      excelExport.contentType.includes('spreadsheet') &&
      excelExport.buffer.length > 1000,
    `${excelExport.status} | ${excelExport.buffer.length}b`,
  ),
);

const pdfExport = await req(
  `/reports/financial/export/pdf?fromDate=${monthStart}&toDate=${today}`,
  { headers: adminAuth, expectJson: false },
);
results.push(
  row(
    'GET /reports/financial/export/pdf',
    pdfExport.status === 200 &&
      pdfExport.contentType.includes('pdf') &&
      pdfExport.buffer.slice(0, 4).toString() === '%PDF',
    `${pdfExport.status} | ${pdfExport.buffer.length}b`,
  ),
);

const recepForbidden = await req(
  `/reports/financial/summary?fromDate=${monthStart}&toDate=${today}`,
  { headers: recepAuth },
);
results.push(
  row('Recepcionista no accede reportes -> 403', recepForbidden.status === 403, recepForbidden.status),
);

const attendancePdf = await req(
  `/attendance/report/export/pdf?from=${monthStart}&to=${today}`,
  { headers: recepAuth, expectJson: false },
);
results.push(
  row(
    'GET /attendance/report/export/pdf',
    attendancePdf.status === 200 &&
      attendancePdf.contentType.includes('pdf') &&
      attendancePdf.buffer.slice(0, 4).toString() === '%PDF',
    `${attendancePdf.status} | ${attendancePdf.buffer.length}b`,
  ),
);

const attendanceExcel = await req(
  `/attendance/report/export/excel?from=${monthStart}&to=${today}`,
  { headers: recepAuth, expectJson: false },
);
results.push(
  row(
    'GET /attendance/report/export/excel',
    attendanceExcel.status === 200 &&
      attendanceExcel.contentType.includes('spreadsheet') &&
      attendanceExcel.buffer.length > 500,
    `${attendanceExcel.status} | ${attendanceExcel.buffer.length}b`,
  ),
);

let saleId = null;
const salesList = await req(`/sales?fromDate=${monthStart}&toDate=${today}&limit=1`, {
  headers: recepAuth,
});
if (Array.isArray(salesList.body?.data) && salesList.body.data.length > 0) {
  saleId = salesList.body.data[0].id;
}

if (!saleId) {
  const category = await req('/categories', {
    method: 'POST',
    headers: adminAuth,
    body: JSON.stringify({ name: `Rep Cat ${Date.now()}` }),
  });
  const product = await req('/products', {
    method: 'POST',
    headers: adminAuth,
    body: JSON.stringify({
      categoryId: category.body?.data?.id,
      code: `REP8-${Date.now()}`,
      name: `Producto Rep8 ${Date.now()}`,
      purchasePrice: 5,
      salePrice: 12,
      stock: 20,
    }),
  });
  let currentCaja = await req('/cash-registers/current', { headers: recepAuth });
  if (!currentCaja.body?.data) {
    currentCaja = await req('/cash-registers/open', {
      method: 'POST',
      headers: recepAuth,
      body: JSON.stringify({ openingAmount: 50 }),
    });
  }
  const sale = await req('/sales', {
    method: 'POST',
    headers: recepAuth,
    body: JSON.stringify({
      items: [{ productId: product.body?.data?.id, quantity: 1 }],
      paymentMethod: 'efectivo',
      receiptType: 'boleta',
    }),
  });
  saleId = sale.body?.data?.id;
  results.push(row('Venta de prueba para ticket PDF', !!saleId, sale.status));
}

const ticketPdf = await req(`/sales/${saleId}/ticket/export/pdf`, {
  headers: recepAuth,
  expectJson: false,
});
results.push(
  row(
    'GET /sales/:id/ticket/export/pdf',
    ticketPdf.status === 200 &&
      ticketPdf.contentType.includes('pdf') &&
      ticketPdf.buffer.slice(0, 4).toString() === '%PDF',
    saleId ? `${ticketPdf.status} | ${ticketPdf.buffer.length}b` : 'sin venta',
  ),
);

const passed = results.filter((r) => r.ok).length;
const total = results.length;

console.log('\n=== Auditoría Fase 08 — Reportes y exportaciones ===\n');
for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'} | ${r.name} | ${r.detail}`);
}
console.log(`\nResultado: ${passed}/${total} OK\n`);
process.exit(passed === total ? 0 : 1);
