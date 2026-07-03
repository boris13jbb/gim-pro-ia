import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { cajas_estado, gastos_estado, Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import {
  CloseCashRegisterDto,
  OpenCashRegisterDto,
} from './dto/cash-register.dto';
import { ListCashRegistersQueryDto } from './dto/list-cash-registers-query.dto';

function mapCashRegister(row: {
  id: number;
  usuario_id: number;
  monto_inicial: Prisma.Decimal;
  monto_final: Prisma.Decimal | null;
  total_ventas: Prisma.Decimal | null;
  total_gastos: Prisma.Decimal | null;
  diferencia: Prisma.Decimal | null;
  fecha_apertura: Date | null;
  fecha_cierre: Date | null;
  estado: cajas_estado | null;
  usuarios?: { id: number; nombre: string | null } | null;
}) {
  return {
    id: row.id,
    userId: row.usuario_id,
    userName: row.usuarios?.nombre ?? null,
    openingAmount: Number(row.monto_inicial),
    closingAmount: row.monto_final != null ? Number(row.monto_final) : null,
    totalSales: row.total_ventas != null ? Number(row.total_ventas) : 0,
    totalExpenses: row.total_gastos != null ? Number(row.total_gastos) : 0,
    difference: row.diferencia != null ? Number(row.diferencia) : 0,
    openedAt: row.fecha_apertura,
    closedAt: row.fecha_cierre,
    status: row.estado ?? cajas_estado.abierta,
  };
}

@Injectable()
export class CashRegistersService {
  constructor(private readonly prisma: PrismaService) {}

  async getCurrentOpen(userId: number) {
    const register = await this.prisma.cajas.findFirst({
      where: { usuario_id: userId, estado: cajas_estado.abierta },
      orderBy: { id: 'desc' },
    });
    if (!register) return null;
    return mapCashRegister(register);
  }

  /**
   * Regla de negocio (corrige E01 PHP):
   * El saldo esperado usa ventas POS de la caja (`ventas`) y gastos del período,
   * no suscripciones de membresía.
   */
  async getCurrentSummary(userId: number) {
    const register = await this.prisma.cajas.findFirst({
      where: { usuario_id: userId, estado: cajas_estado.abierta },
      orderBy: { id: 'desc' },
    });
    if (!register) return null;

    const totals = await this.calculateSessionTotals(register.id, register.fecha_apertura);
    const openingAmount = Number(register.monto_inicial);

    return {
      register: mapCashRegister(register),
      ...totals,
      expectedAmount: openingAmount + totals.totalSales - totals.totalExpenses,
    };
  }

  async requireOpenRegister(userId: number) {
    const register = await this.getCurrentOpen(userId);
    if (!register) {
      throw new BadRequestException(
        'Debe abrir caja antes de registrar ventas u operaciones de POS',
      );
    }
    return register;
  }

  async open(userId: number, dto: OpenCashRegisterDto) {
    const existing = await this.getCurrentOpen(userId);
    if (existing) {
      throw new BadRequestException('Ya existe una caja abierta para este usuario');
    }

    const register = await this.prisma.cajas.create({
      data: {
        usuario_id: userId,
        monto_inicial: dto.openingAmount,
        estado: cajas_estado.abierta,
      },
    });

    return mapCashRegister(register);
  }

  async close(userId: number, dto: CloseCashRegisterDto) {
    const register = await this.prisma.cajas.findFirst({
      where: { usuario_id: userId, estado: cajas_estado.abierta },
      orderBy: { id: 'desc' },
    });
    if (!register) {
      throw new NotFoundException('No hay caja abierta para cerrar');
    }

    const totals = await this.calculateSessionTotals(register.id, register.fecha_apertura);
    const openingAmount = Number(register.monto_inicial);
    const expectedAmount = openingAmount + totals.totalSales - totals.totalExpenses;
    const difference = dto.closingAmount - expectedAmount;

    const closed = await this.prisma.cajas.update({
      where: { id: register.id },
      data: {
        monto_final: dto.closingAmount,
        total_ventas: totals.totalSales,
        total_gastos: totals.totalExpenses,
        diferencia: difference,
        fecha_cierre: new Date(),
        estado: cajas_estado.cerrada,
      },
      include: {
        usuarios: { select: { id: true, nombre: true } },
      },
    });

    return {
      ...mapCashRegister(closed),
      expectedAmount,
      salesCount: totals.salesCount,
    };
  }

  findHistory(query: ListCashRegistersQueryDto = {}) {
    return this.prisma.cajas
      .findMany({
        where: { estado: cajas_estado.cerrada },
        include: {
          usuarios: { select: { id: true, nombre: true } },
        },
        orderBy: { id: 'desc' },
        take: query.limit ?? 20,
      })
      .then((rows) => rows.map(mapCashRegister));
  }

  private async calculateSessionTotals(cashRegisterId: number, openedAt: Date | null) {
    const salesAgg = await this.prisma.ventas.aggregate({
      where: { caja_id: cashRegisterId },
      _sum: { total: true },
      _count: { id: true },
    });

    const expenseWhere: Prisma.gastosWhereInput = {
      estado: { not: gastos_estado.anulado },
    };
    if (openedAt) {
      const openedDate = new Date(openedAt);
      openedDate.setHours(0, 0, 0, 0);
      expenseWhere.fecha = { gte: openedDate };
    }

    const expensesAgg = await this.prisma.gastos.aggregate({
      where: expenseWhere,
      _sum: { monto: true },
    });

    return {
      totalSales: Number(salesAgg._sum.total ?? 0),
      totalExpenses: Number(expensesAgg._sum.monto ?? 0),
      salesCount: salesAgg._count.id,
    };
  }
}
