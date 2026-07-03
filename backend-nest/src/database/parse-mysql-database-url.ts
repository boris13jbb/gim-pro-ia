export type MysqlConnectionConfig = {
  host: string;
  port: number;
  user: string;
  password: string;
  database: string;
};

/**
 * Convierte DATABASE_URL (mysql://user:pass@host:port/db) al formato del adapter MariaDB.
 * Prisma v7 ya no lee la URL desde schema.prisma; el adapter necesita parámetros explícitos.
 */
export function parseMysqlDatabaseUrl(
  databaseUrl: string,
): MysqlConnectionConfig {
  const normalized = databaseUrl.replace(/^mysql:\/\//, 'http://');
  const url = new URL(normalized);

  const host = url.hostname || 'localhost';

  return {
    // En Windows, 'localhost' a veces resuelve a IPv6 y falla si MySQL solo escucha IPv4.
    host: host === 'localhost' ? '127.0.0.1' : host,
    port: url.port ? Number(url.port) : 3306,
    user: decodeURIComponent(url.username || 'root'),
    password: decodeURIComponent(url.password || ''),
    database: decodeURIComponent(url.pathname.replace(/^\//, '')),
  };
}
