/**
 * Límites de rate limit configurables por entorno.
 * En desarrollo/auditoría subir THROTTLE_LOGIN_LIMIT evita bloqueos en pruebas.
 */
export function getThrottleConfig() {
  return {
    global: {
      ttl: Number(process.env.THROTTLE_TTL_MS ?? 60_000),
      limit: Number(process.env.THROTTLE_LIMIT ?? 120),
    },
    login: {
      ttl: Number(process.env.THROTTLE_LOGIN_TTL_MS ?? 60_000),
      limit: Number(process.env.THROTTLE_LOGIN_LIMIT ?? 30),
    },
  };
}
