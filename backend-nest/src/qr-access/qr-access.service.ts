import { Injectable, NotFoundException } from '@nestjs/common';
import { AttendanceService } from '../attendance/attendance.service';
import { MembersService } from '../members/members.service';

@Injectable()
export class QrAccessService {
  constructor(
    private readonly attendanceService: AttendanceService,
    private readonly membersService: MembersService,
  ) {}

  /**
   * Regla de negocio:
   * El carnet PHP codifica el DNI en el QR. No incluir datos sensibles extra.
   */
  async validateQrPayload(qrPayload: string) {
    const dni = qrPayload.trim();
    return this.attendanceService.validateAccessByDni(dni);
  }

  async getMemberCardData(memberId: number) {
    const member = await this.membersService.findById(memberId);
    if (!member) {
      throw new NotFoundException('Socio no encontrado');
    }

    const access = await this.attendanceService.validateAccessByDni(member.dni);

    return {
      member: this.membersService.toPublicMember(member),
      qrPayload: member.dni,
      canAccess: access.canAccess,
      effectiveStatus: access.effectiveStatus,
      isMembershipValid: access.isMembershipValid,
      daysRemaining: access.daysRemaining,
      endDate: access.endDate,
    };
  }
}
