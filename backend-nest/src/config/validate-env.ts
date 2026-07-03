const INSECURE_JWT_PATTERNS = [
  'change_me',
  'change_me_access_secret',
  'change_me_refresh_secret',
  'secret',
  'jwt_secret',
];

/**
 * En producción rechaza arranque si los secretos JWT son placeholders o demasiado cortos.
 */
export function validateEnvOnBootstrap(): void {
  if (process.env.NODE_ENV !== 'production') {
    return;
  }

  const accessSecret = process.env.JWT_ACCESS_SECRET ?? '';
  const refreshSecret = process.env.JWT_REFRESH_SECRET ?? '';
  const minLength = 32;

  const isInsecure = (value: string) =>
    !value ||
    value.length < minLength ||
    INSECURE_JWT_PATTERNS.some((pattern) =>
      value.toLowerCase().includes(pattern),
    );

  if (isInsecure(accessSecret) || isInsecure(refreshSecret)) {
    throw new Error(
      'Configuración insegura: define JWT_ACCESS_SECRET y JWT_REFRESH_SECRET únicos (mín. 32 caracteres) antes de ejecutar en producción.',
    );
  }
}
