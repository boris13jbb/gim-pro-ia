/**
 * Auditoría runtime Fase 07 — POS, ventas y caja.
 * Uso: npm run audit:phase-07
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
     VALUES ('Recepcionista POS','recep-pos@test.com',?,'recepcionista','activo')
     ON DUPLICATE KEY UPDATE password=?, estado='activo', rol='recepcionista'`,
    [hash, hash],
  );
  await conn.query(
    `INSERT INTO usuarios (nombre,email,password,rol,estado)
     VALUES ('Entrenador POS','trainer-pos@test.com',?,'entrenador','activo')
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
  body: JSON.stringify({ email: 'recep-pos@test.com', password: '123456' }),
});
const recepToken = recepLogin.body?.data?.accessToken;
const recepAuth = authHeader(recepToken);
results.push(
  row('Login recepcionista', (recepLogin.status === 200 || recepLogin.status === 201) && !!recepToken, recepLogin.status),
);

const trainerLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'trainer-pos@test.com', password: '123456' }),
});
const trainerAuth = authHeader(trainerLogin.body?.data?.accessToken);

const category = await req('/categories', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ name: `POS Cat ${Date.now()}` }),
});
const categoryId = category.body?.data?.id;

const product = await req('/products', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    categoryId,
    code: `POS7-${Date.now()}`,
    name: `Producto POS7 ${Date.now()}`,
    purchasePrice: 5,
    salePrice: 10,
    stock: 30,
  }),
});
const productId = product.body?.data?.id;
results.push(
  row('Producto de prueba POS', !!productId, product.status),
);

let currentCaja = await req('/cash-registers/current', { headers: recepAuth });
if (!currentCaja.body?.data) {
  currentCaja = await req('/cash-registers/open', {
    method: 'POST',
    headers: recepAuth,
    body: JSON.stringify({ openingAmount: 100 }),
  });
}
results.push(
  row('Caja abierta recepcionista', !!currentCaja.body?.data?.id, currentCaja.status),
);

const summary = await req('/cash-registers/current/summary', { headers: recepAuth });
results.push(
  row('GET /cash-registers/current/summary', summary.status === 200 && summary.body?.data?.register?.id, summary.status),
);
results.push(
  row('Summary incluye expectedAmount', typeof summary.body?.data?.expectedAmount === 'number', summary.body?.data?.expectedAmount ?? 'n/a'),
);

const sale = await req('/sales', {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({
    items: [{ productId, quantity: 2 }],
    discount: 1,
    paymentMethod: 'efectivo',
    receiptType: 'boleta',
  }),
});
const saleId = sale.body?.data?.id;
results.push(
  row('POST /sales con descuento', (sale.status === 200 || sale.status === 201) && !!saleId, sale.status),
);
results.push(
  row('Venta guarda items', Array.isArray(sale.body?.data?.items) && sale.body.data.items.length === 1, sale.body?.data?.items?.length ?? 0),
);

const saleDetail = await req(`/sales/${saleId}`, { headers: recepAuth });
results.push(
  row('GET /sales/:id detalle', saleDetail.status === 200 && saleDetail.body?.data?.id === saleId, saleDetail.status),
);

const ticket = await req(`/sales/${saleId}/ticket`, { headers: recepAuth });
results.push(
  row('GET /sales/:id/ticket', ticket.status === 200 && !!ticket.body?.data?.ticketNumber, ticket.body?.data?.ticketNumber ?? ticket.status),
);

const history = await req(`/sales?fromDate=${today}&toDate=${today}`, { headers: recepAuth });
const inHistory = Array.isArray(history.body?.data)
  ? history.body.data.some((s) => s.id === saleId)
  : false;
results.push(
  row('GET /sales historial del día', history.status === 200 && inHistory, history.status),
);

const summaryAfterSale = await req('/cash-registers/current/summary', { headers: recepAuth });
results.push(
  row('Summary totalSales tras venta', (summaryAfterSale.body?.data?.totalSales ?? 0) >= 19, summaryAfterSale.body?.data?.totalSales ?? 0),
);

const trainerForbidden = await req('/sales', {
  method: 'POST',
  headers: trainerAuth,
  body: JSON.stringify({ items: [{ productId, quantity: 1 }] }),
});
results.push(
  row('Entrenador no puede vender -> 403', trainerForbidden.status === 403, trainerForbidden.status),
);

const closeCaja = await req('/cash-registers/close', {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({
    closingAmount: summaryAfterSale.body?.data?.expectedAmount ?? 119,
  }),
});
results.push(
  row('POST /cash-registers/close', closeCaja.status === 200 || closeCaja.status === 201, closeCaja.status),
);
results.push(
  row('Cierre guarda diferencia', typeof closeCaja.body?.data?.difference === 'number', closeCaja.body?.data?.difference ?? 'n/a'),
);

const cajaHistory = await req('/cash-registers/history', { headers: adminAuth });
results.push(
  row('GET /cash-registers/history', cajaHistory.status === 200 && Array.isArray(cajaHistory.body?.data), cajaHistory.status),
);

const passed = results.filter((r) => r.ok).length;
const total = results.length;

console.log('\n=== Auditoría Fase 07 — POS, ventas y caja ===\n');
for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'} | ${r.name} | ${r.detail}`);
}
console.log(`\nResultado: ${passed}/${total} OK\n`);
process.exit(passed === total ? 0 : 1);
