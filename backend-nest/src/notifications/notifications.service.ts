import { Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { startOfDay } from '../common/utils/date.util';
import { RealtimeNotification } from '../websocket/types/realtime-notification.type';
import { mapNotification } from './notifications.mapper';

@Injectable()
export class NotificationsService {
  constructor(private readonly prisma: PrismaService) {}

  async createForMember(memberId: number, notification: RealtimeNotification) {
    const row = await this.prisma.notifications.create({
      data: {
        member_id: memberId,
        type: notification.type,
        title: notification.title,
        body: notification.body,
        data: notification.data
          ? (notification.data as Prisma.InputJsonValue)
          : undefined,
      },
    });

    return mapNotification(row);
  }

  async listForMember(memberId: number, limit = 50) {
    const rows = await this.prisma.notifications.findMany({
      where: { member_id: memberId },
      orderBy: [{ created_at: 'desc' }, { id: 'desc' }],
      take: Math.min(Math.max(limit, 1), 100),
    });

    return {
      items: rows.map(mapNotification),
      unreadCount: await this.countUnread(memberId),
    };
  }

  async countUnread(memberId: number) {
    return this.prisma.notifications.count({
      where: { member_id: memberId, read_at: null },
    });
  }

  async markAsRead(memberId: number, notificationId: number) {
    const row = await this.prisma.notifications.findFirst({
      where: { id: notificationId, member_id: memberId },
    });
    if (!row) {
      throw new NotFoundException('Notificación no encontrada');
    }
    if (row.read_at) {
      return mapNotification(row);
    }

    const updated = await this.prisma.notifications.update({
      where: { id: notificationId },
      data: { read_at: new Date() },
    });

    return mapNotification(updated);
  }

  async markAllAsRead(memberId: number) {
    const result = await this.prisma.notifications.updateMany({
      where: { member_id: memberId, read_at: null },
      data: { read_at: new Date() },
    });

    return { updated: result.count };
  }

  /**
   * Evita duplicar alertas proactivas el mismo día (Fase 17).
   * `alertKey` se guarda en `data` al crear la notificación.
   */
  async hasAlertKeyToday(memberId: number, alertKey: string): Promise<boolean> {
    const rows = await this.prisma.notifications.findMany({
      where: {
        member_id: memberId,
        created_at: { gte: startOfDay(new Date()) },
      },
      select: { data: true },
      take: 100,
    });

    return rows.some((row) => this.readAlertKey(row.data) === alertKey);
  }

  private readAlertKey(data: Prisma.JsonValue | null): string | null {
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
      return null;
    }
    const value = (data as Record<string, unknown>).alertKey;
    return typeof value === 'string' ? value : null;
  }
}
