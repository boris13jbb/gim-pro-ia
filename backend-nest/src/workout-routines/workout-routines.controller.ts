import {
  Body,
  Controller,
  Get,
  HttpCode,
  HttpStatus,
  Param,
  ParseIntPipe,
  Post,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { assertMemberResourceAccess } from '../common/utils/member-access.util';
import { WorkoutRoutinesService } from './workout-routines.service';
import { CreateWorkoutRoutineDto } from './dto/create-workout-routine.dto';

@ApiTags('workout-routines')
@ApiBearerAuth()
@Controller('workout-routines')
export class WorkoutRoutinesController {
  constructor(private readonly workoutRoutinesService: WorkoutRoutinesService) {}

  @Get('me/current')
  @Roles('socio')
  @ApiOperation({ summary: 'Socio: rutina de entrenamiento actual' })
  getMyCurrentRoutine(@CurrentUser() user: JwtPayload) {
    const memberId = user.memberId ?? user.sub;
    return this.workoutRoutinesService.getCurrentRoutine(memberId);
  }

  @Get('members/:memberId/current')
  @Roles('admin', 'recepcionista', 'entrenador', 'socio')
  @ApiOperation({ summary: 'Rutina actual del socio (última asignada)' })
  getCurrentRoutine(
    @Param('memberId', ParseIntPipe) memberId: number,
    @CurrentUser() user: JwtPayload,
  ) {
    assertMemberResourceAccess(user, memberId);
    return this.workoutRoutinesService.getCurrentRoutine(memberId);
  }

  @Get('members/:memberId')
  @Roles('admin', 'recepcionista', 'entrenador', 'socio')
  @ApiOperation({ summary: 'Historial de rutinas del socio' })
  listHistory(
    @Param('memberId', ParseIntPipe) memberId: number,
    @CurrentUser() user: JwtPayload,
  ) {
    assertMemberResourceAccess(user, memberId);
    return this.workoutRoutinesService.listHistory(memberId);
  }

  @Post('members/:memberId')
  @HttpCode(HttpStatus.CREATED)
  @Roles('admin', 'entrenador')
  @ApiOperation({ summary: 'Asignar nueva versión de rutina (INSERT, conserva historial)' })
  createRoutine(
    @Param('memberId', ParseIntPipe) memberId: number,
    @Body() dto: CreateWorkoutRoutineDto,
  ) {
    return this.workoutRoutinesService.createRoutine(memberId, dto);
  }
}
