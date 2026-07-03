import { BadRequestException, Injectable } from '@nestjs/common';
import { PrismaService } from '../database/prisma.service';
import { MembersService } from '../members/members.service';
import { CreateWorkoutRoutineDto } from './dto/create-workout-routine.dto';

@Injectable()
export class WorkoutRoutinesService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly membersService: MembersService,
  ) {}

  async getCurrentRoutine(memberId: number) {
    await this.membersService.findOne(memberId);

    const routine = await this.prisma.rutinas.findFirst({
      where: { socio_id: memberId },
      orderBy: { id: 'desc' },
    });

    return {
      memberId,
      current: routine ? this.mapRoutine(routine) : null,
    };
  }

  async listHistory(memberId: number) {
    await this.membersService.findOne(memberId);

    const items = await this.prisma.rutinas.findMany({
      where: { socio_id: memberId },
      orderBy: { id: 'desc' },
    });

    return items.map((item) => this.mapRoutine(item));
  }

  async createRoutine(memberId: number, dto: CreateWorkoutRoutineDto) {
    await this.membersService.findOne(memberId);
    this.ensureAtLeastOneDay(dto);

    // Regla de negocio (compatible PHP):
    // Cada guardado inserta una nueva versión para conservar historial.
    const created = await this.prisma.rutinas.create({
      data: {
        socio_id: memberId,
        dia1: dto.day1,
        dia2: dto.day2,
        dia3: dto.day3,
        dia4: dto.day4,
        dia5: dto.day5,
        dia6: dto.day6,
        observaciones: dto.notes,
      },
    });

    return this.mapRoutine(created);
  }

  private ensureAtLeastOneDay(dto: CreateWorkoutRoutineDto) {
    const hasContent = [
      dto.day1,
      dto.day2,
      dto.day3,
      dto.day4,
      dto.day5,
      dto.day6,
      dto.notes,
    ].some((value) => typeof value === 'string' && value.trim().length > 0);

    if (!hasContent) {
      throw new BadRequestException(
        'La rutina debe incluir al menos un día o notas de entrenamiento',
      );
    }
  }

  private mapRoutine(record: {
    id: number;
    socio_id: number;
    dia1: string | null;
    dia2: string | null;
    dia3: string | null;
    dia4: string | null;
    dia5: string | null;
    dia6: string | null;
    observaciones: string | null;
    fecha_asignacion: Date | null;
  }) {
    return {
      id: record.id,
      memberId: record.socio_id,
      day1: record.dia1,
      day2: record.dia2,
      day3: record.dia3,
      day4: record.dia4,
      day5: record.dia5,
      day6: record.dia6,
      notes: record.observaciones,
      assignedAt: record.fecha_asignacion,
    };
  }
}
