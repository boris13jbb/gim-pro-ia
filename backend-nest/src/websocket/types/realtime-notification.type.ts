/**
 * Tipos de notificación en tiempo real que el backend envía al socio.
 * Se amplía a medida que nuevos flujos de dominio necesiten avisar al socio.
 */
export type RealtimeNotificationType =
  'attendance.registered' | 'membership.updated' | 'membership.expiring';

/**
 * Notificación en tiempo real enviada al socio por WebSocket (namespace `/events`).
 * `createdAt` lo agrega RealtimeService al momento de emitir.
 */
export interface RealtimeNotification {
  type: RealtimeNotificationType;
  title: string;
  body: string;
  data?: Record<string, unknown>;
}
