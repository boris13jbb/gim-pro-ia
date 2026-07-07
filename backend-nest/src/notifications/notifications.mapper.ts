import type { notifications } from '@prisma/client';

export function mapNotification(row: notifications) {
  return {
    id: row.id,
    memberId: row.member_id,
    type: row.type,
    title: row.title,
    body: row.body,
    data: row.data as Record<string, unknown> | null,
    readAt: row.read_at,
    isRead: row.read_at != null,
    createdAt: row.created_at,
  };
}
