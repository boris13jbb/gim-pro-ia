/**
 * Auditoría runtime Fase 06 — inventario, categorías y productos.
 * Uso: npm run audit:phase-06
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const base = process.env.API_BASE ?? 'http://localhost:3000/api';

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
     VALUES ('Recepcionista Test','recep@test.com',?,'recepcionista','activo')
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

const recepLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'recep@test.com', password: '123456' }),
});
const recepAuth = authHeader(recepLogin.body?.data?.accessToken);
results.push(
  row('Login recepcionista', (recepLogin.status === 200 || recepLogin.status === 201) && !!recepLogin.body?.data?.accessToken, recepLogin.status),
);

const categoryName = `Cat Audit ${Date.now()}`;
const createdCategory = await req('/categories', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ name: categoryName }),
});
const categoryId = createdCategory.body?.data?.id;
results.push(
  row('POST /categories', (createdCategory.status === 200 || createdCategory.status === 201) && !!categoryId, createdCategory.status),
);

const listCategories = await req('/categories', { headers: adminAuth });
results.push(
  row('GET /categories', listCategories.status === 200 && Array.isArray(listCategories.body?.data), listCategories.status),
);

const activeCategories = await req('/categories/active', { headers: recepAuth });
results.push(
  row('GET /categories/active (recepcionista)', activeCategories.status === 200 && Array.isArray(activeCategories.body?.data), activeCategories.status),
);

const productName = `Producto Audit ${Date.now()}`;
const createdProduct = await req('/products', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    categoryId,
    code: `AUD-${Date.now()}`,
    name: productName,
    purchasePrice: 10,
    salePrice: 15,
    stock: 10,
  }),
});
const productId = createdProduct.body?.data?.id;
results.push(
  row('POST /products', (createdProduct.status === 200 || createdProduct.status === 201) && !!productId, createdProduct.status),
);
results.push(
  row('POST /products stock inicial >= 0', createdProduct.body?.data?.stock === 10, createdProduct.body?.data?.stock ?? 'sin stock'),
);

const getProduct = await req(`/products/${productId}`, { headers: recepAuth });
results.push(
  row('GET /products/:id (recepcionista)', getProduct.status === 200 && getProduct.body?.data?.id === productId, getProduct.status),
);

const activeProducts = await req('/products/active', { headers: recepAuth });
results.push(
  row('GET /products/active', activeProducts.status === 200 && Array.isArray(activeProducts.body?.data), activeProducts.status),
);

const subtractOk = await req('/inventory/adjustments', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    productId,
    quantity: 3,
    operation: 'subtract',
  }),
});
results.push(
  row('POST /inventory/adjustments restar stock', (subtractOk.status === 200 || subtractOk.status === 201) && subtractOk.body?.data?.product?.stock === 7, subtractOk.body?.data?.product?.stock ?? subtractOk.status),
);

const subtractFail = await req('/inventory/adjustments', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    productId,
    quantity: 100,
    operation: 'subtract',
  }),
});
results.push(
  row('POST /inventory/adjustments bloquea stock negativo', subtractFail.status === 400, subtractFail.status),
);

const addStock = await req('/inventory/adjustments', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    productId,
    quantity: 5,
    operation: 'add',
  }),
});
results.push(
  row('POST /inventory/adjustments sumar stock', (addStock.status === 200 || addStock.status === 201) && addStock.body?.data?.product?.stock === 12, addStock.body?.data?.product?.stock ?? addStock.status),
);

const lowStock = await req('/products/low-stock?threshold=20', { headers: adminAuth });
const inLowStock = Array.isArray(lowStock.body?.data)
  ? lowStock.body.data.some((p) => p.id === productId)
  : false;
results.push(
  row('GET /products/low-stock', lowStock.status === 200 && inLowStock, lowStock.status),
);

const productMovements = await req(`/inventory/products/${productId}/movements`, {
  headers: adminAuth,
});
const movementCount = Array.isArray(productMovements.body?.data)
  ? productMovements.body.data.length
  : 0;
results.push(
  row('GET /inventory/products/:id/movements', productMovements.status === 200 && movementCount >= 3, movementCount),
);

const posProduct = await req('/products', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    categoryId,
    code: `POS-${Date.now()}`,
    name: `POS Audit ${Date.now()}`,
    purchasePrice: 8,
    salePrice: 12,
    stock: 20,
  }),
});
const posProductId = posProduct.body?.data?.id;
results.push(
  row('POST /products para venta POS', (posProduct.status === 200 || posProduct.status === 201) && posProduct.body?.data?.stock === 20, posProduct.body?.data?.stock ?? posProduct.status),
);

const posMovements = await req(`/inventory/products/${posProductId}/movements`, {
  headers: adminAuth,
});
const hasCreateMovement = Array.isArray(posMovements.body?.data)
  ? posMovements.body.data.some((m) => m.type === 'product_create')
  : false;
results.push(
  row('Movimiento product_create registrado', posMovements.status === 200 && hasCreateMovement, hasCreateMovement),
);

let recepCaja = await req('/cash-registers/current', { headers: recepAuth });
if (!recepCaja.body?.data) {
  recepCaja = await req('/cash-registers/open', {
    method: 'POST',
    headers: recepAuth,
    body: JSON.stringify({ openingAmount: 50 }),
  });
}
results.push(
  row('POST /cash-registers/open (recepcionista)', !!recepCaja.body?.data?.id, recepCaja.status),
);

const adminCaja = await req('/cash-registers/current', { headers: adminAuth });
if (adminCaja.body?.data) {
  await req('/cash-registers/close', {
    method: 'POST',
    headers: adminAuth,
    body: JSON.stringify({ closingAmount: 100 }),
  });
}

const saleNoCaja = await req('/sales', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    items: [{ productId: posProductId, quantity: 1 }],
  }),
});
results.push(
  row('POST /sales sin caja abierta -> 400', saleNoCaja.status === 400, saleNoCaja.status),
);

await req('/cash-registers/open', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({ openingAmount: 30 }),
});

const saleOk = await req('/sales', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    items: [{ productId: posProductId, quantity: 2 }],
    discount: 0,
    paymentMethod: 'efectivo',
  }),
});
const saleId = saleOk.body?.data?.id;
results.push(
  row('POST /sales descuenta stock', (saleOk.status === 200 || saleOk.status === 201) && !!saleId, saleOk.status),
);

const posAfterSale = await req(`/products/${posProductId}`, { headers: adminAuth });
results.push(
  row('Stock tras venta POS', posAfterSale.body?.data?.stock === 18, posAfterSale.body?.data?.stock ?? 'sin stock'),
);

const saleMovements = await req(`/inventory/movements?saleId=${saleId}`, {
  headers: adminAuth,
});
const hasSaleMovement = Array.isArray(saleMovements.body?.data)
  ? saleMovements.body.data.some((m) => m.type === 'sale' && m.quantity === 2)
  : false;
results.push(
  row('Movimiento sale auditado por venta', saleMovements.status === 200 && hasSaleMovement, hasSaleMovement),
);

const saleOverStock = await req('/sales', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    items: [{ productId: posProductId, quantity: 999 }],
  }),
});
results.push(
  row('POST /sales bloquea stock insuficiente', saleOverStock.status === 400, saleOverStock.status),
);

const deactivate = await req(`/products/${productId}/status`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ status: 'inactivo' }),
});
results.push(
  row('PATCH /products/:id/status inactivo', deactivate.status === 200 && deactivate.body?.data?.status === 'inactivo', deactivate.body?.data?.status ?? deactivate.status),
);

const recepForbidden = await req('/categories', {
  method: 'POST',
  headers: recepAuth,
  body: JSON.stringify({ name: 'No permitido' }),
});
results.push(
  row('POST /categories recepcionista -> 403', recepForbidden.status === 403, recepForbidden.status),
);

const passed = results.filter((r) => r.ok).length;
const total = results.length;

console.log('\n=== Auditoría Fase 06 — Inventario y productos ===\n');
for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'} | ${r.name} | ${r.detail}`);
}
console.log(`\nResultado: ${passed}/${total} OK\n`);
process.exit(passed === total ? 0 : 1);
