/**
 * Auditoría runtime Fases 01-02. Uso: npm run audit:phase-02
 */
import bcrypt from 'bcrypt';
import mariadb from 'mariadb';
import jwt from 'jsonwebtoken';
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
    // .env opcional si variables ya están en el entorno
  }
}

loadEnvFile();

async function setupUsers(conn) {
  const hash = await bcrypt.hash('123456', 10);
  await conn.query(
    `INSERT INTO usuarios (nombre,email,password,rol,estado)
     VALUES ('Inactivo Test','inactive@test.com',?,'recepcionista','inactivo')
     ON DUPLICATE KEY UPDATE password=?, estado='inactivo'`,
    [hash, hash],
  );
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

const health = await req('/health');
results.push(row('Health check', health.body?.data?.details?.database?.status === 'up', health.body?.data?.details?.database?.status ?? health.status));

const swagger = await fetch(`${base}/docs`);
results.push(row('Swagger', swagger.status === 200, `HTTP ${swagger.status}`));

const login = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'admin@gym.com', password: '123456' }),
});
results.push(row('Login correcto', (login.status === 200 || login.status === 201) && login.body.ok, login.status));
const adminToken = login.body?.data?.accessToken;
const adminAuth = { Authorization: `Bearer ${adminToken}` };
results.push(row('Password oculto en login', !('password' in (login.body?.data?.user ?? {})), 'sin campo password'));

const badLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'admin@gym.com', password: 'wrong' }),
});
results.push(row('Login incorrecto', badLogin.status === 401, badLogin.status));

const inactiveLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'inactive@test.com', password: '123456' }),
});
results.push(
  row(
    'Login usuario inactivo',
    inactiveLogin.status === 401 && String(inactiveLogin.body?.error?.message ?? '').includes('inhabilitada'),
    inactiveLogin.body?.error?.message ?? inactiveLogin.status,
  ),
);

const me = await req('/auth/me', { headers: adminAuth });
results.push(row('Access token valido /me', me.status === 200, me.status));
results.push(row('Password oculto en /me', !('password' in (me.body?.data ?? {})), 'sin campo password'));

const noToken = await req('/auth/me');
results.push(row('Acceso sin token', noToken.status === 401, noToken.status));

const badToken = await req('/auth/me', { headers: { Authorization: 'Bearer invalid.token' } });
results.push(row('Token invalido', badToken.status === 401, badToken.status));

const jwtSecret =
  process.env.JWT_ACCESS_SECRET ?? 'change_me_access_secret_dev_only';
const expiredToken = jwt.sign(
  {
    sub: 1,
    email: 'admin@gym.com',
    role: 'admin',
    userType: 'staff',
    type: 'access',
  },
  jwtSecret,
  { expiresIn: -1 },
);
const expiredMe = await req('/auth/me', {
  headers: { Authorization: `Bearer ${expiredToken}` },
});
results.push(row('Access token expirado', expiredMe.status === 401, expiredMe.status));

const usersList = await req('/users', { headers: adminAuth });
results.push(row('Acceso rol permitido admin /users', usersList.status === 200, usersList.status));

const created = await req('/users', {
  method: 'POST',
  headers: adminAuth,
  body: JSON.stringify({
    nombre: 'Audit User',
    email: `audit_${Date.now()}@test.com`,
    password: 'Audit123',
    rol: 'recepcionista',
  }),
});
results.push(row('Crear usuario', created.status === 200 || created.status === 201, created.status));
const newUserId = created.body?.data?.id;

const edited = await req(`/users/${newUserId}`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ nombre: 'Audit User Editado' }),
});
results.push(row('Editar usuario', edited.status === 200, edited.status));

const pwd = await req(`/users/${newUserId}/password`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ password: 'NuevaAudit123' }),
});
results.push(row('Cambiar contraseña', pwd.status === 200, pwd.status));

const status = await req(`/users/${newUserId}/status`, {
  method: 'PATCH',
  headers: adminAuth,
  body: JSON.stringify({ estado: 'inactivo' }),
});
results.push(row('Cambiar estado', status.status === 200, status.status));

const refreshLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'admin@gym.com', password: '123456' }),
});
const refresh = refreshLogin.body?.data?.refreshToken;
const refreshed = await req('/auth/refresh', {
  method: 'POST',
  body: JSON.stringify({ refreshToken: refresh }),
});
results.push(row('Refresh token valido', refreshed.status === 200 || refreshed.status === 201, refreshed.status));
const newRefresh = refreshed.body?.data?.refreshToken;

const badRefresh = await req('/auth/refresh', {
  method: 'POST',
  body: JSON.stringify({ refreshToken: 'bad.token.value' }),
});
results.push(row('Refresh token invalido', badRefresh.status === 401, badRefresh.status));

const logout = await req('/auth/logout', {
  method: 'POST',
  body: JSON.stringify({ refreshToken: newRefresh }),
});
results.push(row('Logout', logout.status === 200 || logout.status === 201, logout.body?.data?.message ?? logout.status));

const afterLogout = await req('/auth/refresh', {
  method: 'POST',
  body: JSON.stringify({ refreshToken: newRefresh }),
});
results.push(row('Refresh revocado tras logout', afterLogout.status === 401, afterLogout.status));

const recepLogin = await req('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'recep@test.com', password: '123456' }),
});
results.push(row('Login recepcionista test', recepLogin.status === 200 || recepLogin.status === 201, recepLogin.status));
const recepDenied = await req('/users', {
  headers: { Authorization: `Bearer ${recepLogin.body?.data?.accessToken}` },
});
results.push(row('Acceso rol no permitido recep /users', recepDenied.status === 403, recepDenied.status));

for (const r of results) {
  console.log(`${r.ok ? 'OK' : 'FAIL'}\t${r.name}\t${r.detail}`);
}

const failed = results.filter((r) => !r.ok).length;
process.exit(failed > 0 ? 1 : 0);
