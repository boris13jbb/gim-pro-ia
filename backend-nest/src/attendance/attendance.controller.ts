import {
  Body,
  Controller,
  ForbiddenException,
  Get,
  Header,
  HttpCode,
  HttpStatus,
  Post,
  Query,
  StreamableFile,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiProduces, ApiTags } from '@nestjs/swagger';
import { RawResponse } from '../common/decorators/raw-response.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { AttendanceExportService } from './attendance-export.service';
import { AttendanceService } from './attendance.service';
import { ValidateAttendanceAccessDto } from './dto/validate-attendance-access.dto';
import { RegisterAttendanceDto } from './dto/register-attendance.dto';
import { ScanAttendanceDto } from './dto/scan-attendance.dto';
import { ListAttendanceReportQueryDto } from './dto/list-attendance-report-query.dto';

@ApiTags('attendance')
@ApiBearerAuth()
@Controller('attendance')
export class AttendanceController {
  constructor(
    private readonly attendanceService: AttendanceService,
    private readonly attendanceExportService: AttendanceExportService,
  ) {}

  @Get('today')
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({ summary: 'Listar asistencias registradas hoy' })
  listToday() {
    return this.attendanceService.listToday();
  }

  @Get('report')
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({ summary: 'Reporte de asistencias por rango de fechas' })
  getReport(@Query() query: ListAttendanceReportQueryDto) {
    return this.attendanceService.getReport(query);
  }

  @Get('report/ranking')
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({ summary: 'Ranking de socios con más visitas en el período' })
  getRanking(@Query() query: ListAttendanceReportQueryDto) {
    return this.attendanceService.getRanking(query);
  }

  @Get('report/export/excel')
  @RawResponse()
  @ApiProduces(
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  )
  @Header(
    'Content-Type',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  )
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({ summary: 'Exportar reporte de asistencias a Excel' })
  async exportReportExcel(@Query() query: ListAttendanceReportQueryDto) {
    const file = await this.attendanceExportService.buildExcel(query);
    return new StreamableFile(file.buffer, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }

  @Get('report/export/pdf')
  @RawResponse()
  @ApiProduces('application/pdf')
  @Header('Content-Type', 'application/pdf')
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({ summary: 'Exportar reporte de asistencias a PDF' })
  async exportReportPdf(@Query() query: ListAttendanceReportQueryDto) {
    const file = await this.attendanceExportService.buildPdf(query);
    return new StreamableFile(file.buffer, {
      type: 'application/pdf',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }

  @Post('validate')
  @HttpCode(HttpStatus.OK)
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({
    summary: 'Validar acceso por DNI sin registrar asistencia (paso 1 PHP)',
  })
  validateAccess(@Body() dto: ValidateAttendanceAccessDto) {
    return this.attendanceService.validateAccessByDni(dto.dni);
  }

  @Post('register')
  @HttpCode(HttpStatus.CREATED)
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({
    summary: 'Registrar ingreso tras verificación visual (paso 2 PHP)',
  })
  register(@Body() dto: RegisterAttendanceDto) {
    return this.attendanceService.register(dto);
  }

  @Post('scan')
  @HttpCode(HttpStatus.CREATED)
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({
    summary: 'Validar por DNI/QR y registrar asistencia en un solo paso',
  })
  scan(@Body() dto: ScanAttendanceDto) {
    return this.attendanceService.scanAndRegister(dto.dni, dto.method);
  }

  @Post('self')
  @HttpCode(HttpStatus.CREATED)
  @Roles('socio')
  @ApiOperation({ summary: 'Socio registra su propia asistencia desde la app' })
  registerSelf(@CurrentUser() user: JwtPayload) {
    if (!user.memberId || user.memberId !== user.sub) {
      throw new ForbiddenException('Token de socio inválido');
    }

    return this.attendanceService.register({
      memberId: user.memberId,
      method: 'app',
    });
  }
}
