import { Injectable } from '@nestjs/common';
import {
  gastos_estado,
  socios_estado,
  ventas_metodo_pago,
} from '@prisma/client';
import {
  firstDayOfCurrentMonth,
  getDateRange,
  todayDateString,
} from '../common/utils/date.util';
import { PrismaService } from '../database/prisma.service';
import { ReportDateRangeQueryDto } from './dto/report-date-range-query.dto';

export type FinancialMovement = {
  date: Date;
  description: string;
  amount: number;
  type: 'ingreso' | 'gasto';
};

function monthKey(date: Date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}`;
}

@Injectable()
export class ReportsService {
  constructor(private readonly prisma: PrismaService) {}

  private resolveRange(query: ReportDateRangeQueryDto) {
    const fromDate = query.fromDate ?? firstDayOfCurrentMonth();
    const toDate = query.toDate ?? todayDateString();
    const { start, end } = getDateRange(fromDate, toDate);
    return { fromDate, toDate, start, end };
  }

  async getCompanyConfig() {
    const config = await this.prisma.configuracion.findFirst({
      select: {
        nombre_sistema: true,
        moneda: true,
        direccion: true,
        telefono: true,
        logo: true,
      },
    });
    return {
      systemName: config?.nombre_sistema ?? 'Gym System',
      currency: config?.moneda ?? '$',
      address: config?.direccion ?? '',
      phone: config?.telefono ?? '',
      logo: config?.logo ?? null,
    };
  }

  async getFinancialSummary(query: ReportDateRangeQueryDto) {
    const { fromDate, toDate, start, end } = this.resolveRange(query);
    const [
      membershipIncome,
      posIncome,
      totalExpenses,
      activeMembers,
      incomeByMonth,
      salesByPaymentMethod,
      newMembersByMonth,
    ] = await Promise.all([
      this.sumMembershipIncome(start, end),
      this.sumPosIncome(start, end),
      this.sumExpenses(start, end),
      this.countActiveMembers(),
      this.getIncomeByMonth(start, end),
      this.getSalesByPaymentMethod(start, end),
      this.getNewMembersByMonth(start, end),
    ]);

    const totalIncome = membershipIncome + posIncome;
    const netProfit = totalIncome - totalExpenses;

    return {
      fromDate,
      toDate,
      membershipIncome,
      posIncome,
      totalIncome,
      totalExpenses,
      netProfit,
      activeMembers,
      charts: {
        incomeByMonth,
        salesByPaymentMethod,
        newMembersByMonth,
      },
    };
  }

  async getFinancialMovements(query: ReportDateRangeQueryDto) {
    const { fromDate, toDate, start, end } = this.resolveRange(query);
    const movements = await this.buildMovements(start, end);
    const limit = query.limit ?? 200;
    return {
      fromDate,
      toDate,
      items: movements.slice(0, limit),
    };
  }

  async buildMovements(start: Date, end: Date): Promise<FinancialMovement[]> {
    const [subscriptions, sales, expenses] = await Promise.all([
      this.prisma.suscripciones.findMany({
        where: { fecha_inicio: { gte: start, lte: end } },
        include: {
          planes: { select: { nombre: true, precio: true } },
          socios: { select: { nombre: true } },
        },
        orderBy: { fecha_inicio: 'desc' },
      }),
      this.prisma.ventas.findMany({
        where: { fecha: { gte: start, lte: end } },
        orderBy: { fecha: 'desc' },
        select: { id: true, fecha: true, total: true, metodo_pago: true },
      }),
      this.prisma.gastos.findMany({
        where: {
          fecha: { gte: start, lte: end },
          estado: { not: gastos_estado.anulado },
        },
        orderBy: { fecha: 'desc' },
      }),
    ]);

    const movements: FinancialMovement[] = [
      ...subscriptions
        .filter(
          (item) =>
            item.fecha_inicio && item.planes?.precio != null && item.socios,
        )
        .map((item) => ({
          date: item.fecha_inicio!,
          description: `Membresía: ${item.planes!.nombre} — ${item.socios!.nombre}`,
          amount: Number(item.planes!.precio),
          type: 'ingreso' as const,
        })),
      ...sales.map((item) => ({
        date: item.fecha ?? new Date(),
        description: `Venta POS #${item.id} (${item.metodo_pago ?? 'efectivo'})`,
        amount: Number(item.total),
        type: 'ingreso' as const,
      })),
      ...expenses.map((item) => ({
        date: item.fecha,
        description: `Gasto: ${item.descripcion}`,
        amount: Number(item.monto),
        type: 'gasto' as const,
      })),
    ];

    return movements.sort((a, b) => b.date.getTime() - a.date.getTime());
  }

  private async sumMembershipIncome(start: Date, end: Date) {
    const items = await this.prisma.suscripciones.findMany({
      where: { fecha_inicio: { gte: start, lte: end } },
      include: { planes: { select: { precio: true } } },
    });
    return items.reduce(
      (sum, item) => sum + Number(item.planes?.precio ?? 0),
      0,
    );
  }

  private async sumPosIncome(start: Date, end: Date) {
    const result = await this.prisma.ventas.aggregate({
      where: { fecha: { gte: start, lte: end } },
      _sum: { total: true },
    });
    return Number(result._sum.total ?? 0);
  }

  private async sumExpenses(start: Date, end: Date) {
    const result = await this.prisma.gastos.aggregate({
      where: {
        fecha: { gte: start, lte: end },
        estado: { not: gastos_estado.anulado },
      },
      _sum: { monto: true },
    });
    return Number(result._sum.monto ?? 0);
  }

  private countActiveMembers() {
    return this.prisma.socios.count({
      where: { estado: socios_estado.activo },
    });
  }

  private async getIncomeByMonth(start: Date, end: Date) {
    const [subscriptions, sales] = await Promise.all([
      this.prisma.suscripciones.findMany({
        where: { fecha_inicio: { gte: start, lte: end } },
        select: { fecha_inicio: true, planes: { select: { precio: true } } },
      }),
      this.prisma.ventas.findMany({
        where: { fecha: { gte: start, lte: end } },
        select: { fecha: true, total: true },
      }),
    ]);

    const totals = new Map<string, number>();
    for (const item of subscriptions) {
      if (!item.fecha_inicio || item.planes?.precio == null) continue;
      const key = monthKey(item.fecha_inicio);
      totals.set(key, (totals.get(key) ?? 0) + Number(item.planes.precio));
    }
    for (const item of sales) {
      if (!item.fecha) continue;
      const key = monthKey(item.fecha);
      totals.set(key, (totals.get(key) ?? 0) + Number(item.total));
    }

    return [...totals.entries()]
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([month, total]) => ({ month, total }));
  }

  private async getSalesByPaymentMethod(start: Date, end: Date) {
    const grouped = await this.prisma.ventas.groupBy({
      by: ['metodo_pago'],
      where: { fecha: { gte: start, lte: end } },
      _count: { _all: true },
      _sum: { total: true },
    });

    return grouped
      .map((row) => ({
        paymentMethod: row.metodo_pago ?? ventas_metodo_pago.efectivo,
        count: row._count._all,
        total: Number(row._sum.total ?? 0),
      }))
      .sort((a, b) => b.total - a.total);
  }

  private async getNewMembersByMonth(start: Date, end: Date) {
    const members = await this.prisma.socios.findMany({
      where: {
        fecha_registro: { gte: start, lte: end },
      },
      select: { fecha_registro: true },
    });

    const totals = new Map<string, number>();
    for (const member of members) {
      if (!member.fecha_registro) continue;
      const key = monthKey(member.fecha_registro);
      totals.set(key, (totals.get(key) ?? 0) + 1);
    }

    return [...totals.entries()]
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([month, count]) => ({ month, count }));
  }
}
