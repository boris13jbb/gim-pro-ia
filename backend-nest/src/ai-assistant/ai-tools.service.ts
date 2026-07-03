import { Injectable } from '@nestjs/common';
import { MembersService } from '../members/members.service';
import { AttendanceService } from '../attendance/attendance.service';
import { BodyProgressService } from '../body-progress/body-progress.service';
import { WorkoutRoutinesService } from '../workout-routines/workout-routines.service';

/**
 * Herramientas internas del asistente IA.
 * Regla de seguridad: solo consultan datos del socio autenticado (memberId del JWT).
 * La IA no inventa datos; este servicio inyecta contexto real desde NestJS → BD.
 */
@Injectable()
export class AiToolsService {
  constructor(
    private readonly membersService: MembersService,
    private readonly attendanceService: AttendanceService,
    private readonly bodyProgressService: BodyProgressService,
    private readonly workoutRoutinesService: WorkoutRoutinesService,
  ) {}

  async getMemberProfile(memberId: number) {
    const member = await this.membersService.findOne(memberId);
    return {
      id: member.id,
      name: member.nombre,
      dni: member.dni,
      email: member.email,
      phone: member.telefono,
      status: member.estado,
      registeredAt: member.fechaRegistro,
    };
  }

  async getMembershipStatus(memberId: number) {
    return this.membersService.getMembershipSummary(memberId);
  }

  async getAttendanceSummary(memberId: number) {
    return this.attendanceService.getReport({ memberId });
  }

  async getBodyProgress(memberId: number) {
    const data = await this.bodyProgressService.listMemberMeasurements(memberId);
    const items = data.items ?? [];
    const latest = items.length > 0 ? items[items.length - 1] : null;
    return {
      totalRecords: items.length,
      latestMeasurement: latest,
      recentTrend: items.slice(-5),
    };
  }

  async getCurrentWorkoutRoutine(memberId: number) {
    return this.workoutRoutinesService.getCurrentRoutine(memberId);
  }

  /**
   * Contexto consolidado para el prompt de Gemini (JSON serializado).
   */
  async buildMemberContext(memberId: number): Promise<string> {
    const [profile, membership, attendance, bodyProgress, workout] =
      await Promise.all([
        this.getMemberProfile(memberId),
        this.getMembershipStatus(memberId),
        this.getAttendanceSummary(memberId),
        this.getBodyProgress(memberId),
        this.getCurrentWorkoutRoutine(memberId),
      ]);

    return JSON.stringify(
      {
        profile,
        membership,
        attendance: {
          from: attendance.from,
          to: attendance.to,
          totalVisits: attendance.totalVisits,
          averageDaily: attendance.averageDaily,
          recentVisits: (attendance.items ?? []).slice(0, 10),
        },
        bodyProgress,
        workout,
      },
      null,
      2,
    );
  }
}
