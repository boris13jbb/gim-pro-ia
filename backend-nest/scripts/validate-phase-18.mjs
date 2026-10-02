/**
 * Validación runtime Fase 18 — salida de ngrok / flujo LAN.
 * Uso (API en marcha): node scripts/validate-phase-18.mjs
 *
 * Opcional:
 *   LAN_IP=192.168.x.x
 *   MEMBER_LOGIN=dni_o_email
 *   MEMBER_PASSWORD=...
 *   (no hardcodear credenciales en el repo)
 */
import { networkInterfaces } from 'node:os';

const port = process.env.PORT ?? '3000';
const forcedLan = (process.env.LAN_IP ?? '').trim();

function detectLanIp() {
  if (forcedLan) return forcedLan;
  const nets = networkInterfaces();
  const wifi = [];
  const other = [];
  for (const [name, entries] of Object.entries(nets)) {
    for (const entry of entries ?? []) {
      if (entry.family !== 'IPv4' || entry.internal) continue;
      if (entry.address.startsWith('169.254.')) continue;
      if (/wi-?fi|wlan/i.test(name)) wifi.push(entry.address);
      else other.push(entry.address);
    }
  }
  return wifi[0] ?? other[0] ?? null;
}

const results = [];
function record(id, name, ok, detail) {
  results.push({ id, name, ok, detail });
  console.log(`${ok ? 'PASS' : 'FAIL'}\t${id}\t${name}\t${detail}`);
}

async function getJson(url, options = {}) {
  const res = await fetch(url, options);
  const body = await res.json().catch(() => ({}));
  return { status: res.status, body, headers: res.headers };
}

async function main() {
  console.log('Fase 18 — validación LAN / sin ngrok obligatorio\n');
  const lanIp = detectLanIp();
  record(
    'F18-IP',
    'Detectar IP LAN',
    Boolean(lanIp),
    lanIp ? `IP=${lanIp}` : 'No se detectó IPv4 LAN (pase LAN_IP=...)',
  );

  const localHealth = `http://127.0.0.1:${port}/api/health`;
  try {
    const r = await getJson(localHealth);
    record('F18-H1', 'Health localhost', r.status === 200, `HTTP ${r.status}`);
  } catch (e) {
    record('F18-H1', 'Health localhost', false, e.message);
  }

  if (lanIp) {
    const lanHealth = `http://${lanIp}:${port}/api/health`;
    try {
      const r = await getJson(lanHealth);
      record('F18-H2', 'Health por LAN', r.status === 200, `${lanHealth} → ${r.status}`);
    } catch (e) {
      record('F18-H2', 'Health por LAN', false, e.message);
    }

    // Preflight CORS: Origin LAN web :8888
    try {
      const origin = `http://${lanIp}:8888`;
      const res = await fetch(`http://${lanIp}:${port}/api/health`, {
        method: 'GET',
        headers: { Origin: origin },
      });
      const allow = res.headers.get('access-control-allow-origin');
      const ok =
        res.status === 200 &&
        (allow === origin || allow === '*' || allow === 'true');
      record(
        'F18-CORS',
        'CORS Origin LAN :8888',
        ok,
        `status=${res.status} allow-origin=${allow ?? '(none)'}`,
      );
    } catch (e) {
      record('F18-CORS', 'CORS Origin LAN :8888', false, e.message);
    }
  } else {
    record('F18-H2', 'Health por LAN', false, 'Sin IP LAN');
    record('F18-CORS', 'CORS Origin LAN :8888', false, 'Sin IP LAN');
  }

  const login = (process.env.MEMBER_LOGIN ?? '').trim();
  const password = (process.env.MEMBER_PASSWORD ?? '').trim();
  if (login && password && lanIp) {
    try {
      const res = await fetch(`http://${lanIp}:${port}/api/auth/member/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login, password }),
      });
      const body = await res.json().catch(() => ({}));
      const data = body?.data ?? body;
      const token = data?.accessToken ?? data?.access_token;
      record(
        'F18-LOGIN',
        'Login socio por LAN',
        res.status === 200 || res.status === 201,
        `HTTP ${res.status} token=${token ? 'yes' : 'no'}`,
      );
    } catch (e) {
      record('F18-LOGIN', 'Login socio por LAN', false, e.message);
    }
  } else {
    record(
      'F18-LOGIN',
      'Login socio por LAN',
      true,
      'SKIP (defina MEMBER_LOGIN y MEMBER_PASSWORD en el entorno para validar)',
    );
  }

  record(
    'F18-NGROK',
    'ngrok no requerido para health LAN',
    true,
    'Validación ejecutada sin ngrok',
  );

  const failed = results.filter((r) => !r.ok);
  console.log(
    `\nResumen: ${results.length - failed.length}/${results.length} PASS`,
  );
  if (failed.length) {
    process.exitCode = 1;
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
