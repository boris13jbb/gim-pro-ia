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
    (isDev() && isNgrokOrigin(origin)) ||
    isLocalDevOrigin(origin)
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
