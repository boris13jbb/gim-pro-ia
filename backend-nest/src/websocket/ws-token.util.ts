import type { Socket } from 'socket.io';

/** Datos que los gateways guardan en el socket tras autenticar el handshake. */
interface MemberSocketData {
  memberId?: number;
}

/**
 * Guarda el memberId validado en el socket (tipado, evita el `any` de
 * `socket.data`). El id proviene siempre del token firmado, nunca del cliente.
 */
export function setSocketMemberId(client: Socket, memberId: number): void {
  (client.data as MemberSocketData).memberId = memberId;
}

/** Lee el memberId previamente guardado en el socket autenticado. */
export function getSocketMemberId(client: Socket): number | undefined {
  return (client.data as MemberSocketData).memberId;
}

/**
 * Extrae el access token JWT del handshake del socket.
 *
 * Preferencia: `auth.token` (recomendado por socket.io); respaldo en la cabecera
 * Authorization (Bearer) o en el query `token`. Se comparte entre los gateways
 * WebSocket (chat IA y notificaciones) para no duplicar la lógica de extracción.
 */
export function extractHandshakeToken(client: Socket): string | undefined {
  const auth = client.handshake.auth as { token?: unknown } | undefined;
  if (auth && typeof auth.token === 'string' && auth.token.length > 0) {
    return auth.token;
  }

  const header = client.handshake.headers['authorization'];
  if (typeof header === 'string' && header.startsWith('Bearer ')) {
    return header.slice(7);
  }

  const queryToken = client.handshake.query['token'];
  if (typeof queryToken === 'string' && queryToken.length > 0) {
    return queryToken;
  }

  return undefined;
}
