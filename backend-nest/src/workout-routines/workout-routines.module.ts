import { Module } from '@nestjs/common';
import { MembersModule } from '../members/members.module';
import { WorkoutRoutinesController } from './workout-routines.controller';
import { WorkoutRoutinesService } from './workout-routines.service';

@Module({
  imports: [MembersModule],
  controllers: [WorkoutRoutinesController],
  providers: [WorkoutRoutinesService],
  exports: [WorkoutRoutinesService],
})
export class WorkoutRoutinesModule {}
