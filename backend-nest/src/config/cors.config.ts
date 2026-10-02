const parseCorsOrigins = (): string[] =>
  (process.env.CORS_ORIGINS ?? '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

const isDev = (): boolean =>
  (process.env.NODE_ENV ?? 'development') !== 'production';

const isNgrokOrigin = (origin: string): boolean =>
  /^https:\/\/[a-z0-9-]+\.ngrok(-free)?\.app$/i.test(origin);

/** Flutter web usa puertos dinámicos; en dev aceptamos cualquier localhost. */
const isLocalDevOrigin = (origin: string): boolean =>
  isDev() && /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/i.test(origin);

/**
 * Fase 18 — LAN:
 * En development acepta orígenes HTTP(S) desde IPv4 privadas (RFC1918)
 * sin hardcodear una IP concreta (ej. http://192.168.x.x:8888).
 * No aplica en production: ahí solo CORS_ORIGINS explícito (+ sin wildcard LAN).
 */
const isPrivateLanDevOrigin = (origin: string): boolean => {
  if (!isDev()) {
    return false;
  }
  const match = origin.match(
    /^https?:\/\/(\d{1,3}(?:\.\d{1,3}){3})(?::\d+)?$/i,
  );
  if (!match) {
    return false;
  }
  const parts = match[1].split('.').map((p) => Number(p));
  if (parts.some((n) => Number.isNaN(n) || n < 0 || n > 255)) {
    return false;
  }
  const [a, b] = parts;
  // 10.0.0.0/8 | 172.16.0.0/12 | 192.168.0.0/16
  return (
    a === 10 ||
    (a === 172 && b >= 16 && b <= 31) ||
    (a === 192 && b === 168)
  );
};

/** Valida si un origen HTTP/WebSocket está permitido por CORS. */
export function isAllowedCorsOrigin(origin: string | undefined): boolean {
  if (!origin) {
    return true;
  }
  const corsOrigins = parseCorsOrigins();
  if (corsOrigins.length === 0) {
    return true;
  }
  return (
    corsOrigins.includes(origin) ||
    isLocalDevOrigin(origin) ||
    isPrivateLanDevOrigin(origin) ||
    // LEGACY: ngrok free solo en development (Fase 18 — flujo principal = LAN).
    (isDev() && isNgrokOrigin(origin))
  );
}

/** Callback compatible con enableCors de Nest/Express. */
export function corsOriginCallback(
  origin: string | undefined,
  callback: (err: Error | null, allow?: boolean) => void,
): void {
  if (isAllowedCorsOrigin(origin)) {
    callback(null, true);
    return;
  }
  callback(new Error(`Origen CORS no permitido: ${origin}`), false);
}

/** Config CORS para gateways Socket.IO (mismo criterio que la API REST). */
export function getWebSocketCorsConfig(): {
  origin:
    | boolean
    | string[]
    | ((
        origin: string | undefined,
        callback: (err: Error | null, allow?: boolean) => void,
      ) => void);
} {
  const corsOrigins = parseCorsOrigins();
  if (corsOrigins.length === 0) {
    return { origin: true };
  }
  return {
    origin: (origin, callback) => corsOriginCallback(origin, callback),
  };
}
