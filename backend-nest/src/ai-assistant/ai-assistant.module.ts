import { Module } from '@nestjs/common';
import { MembersModule } from '../members/members.module';
import { AttendanceModule } from '../attendance/attendance.module';
import { BodyProgressModule } from '../body-progress/body-progress.module';
import { WorkoutRoutinesModule } from '../workout-routines/workout-routines.module';
import { AiChatController } from './ai-chat.controller';
import { AiChatService } from './ai-chat.service';
import { AiModelService } from './ai-model.service';
import { AiToolsService } from './ai-tools.service';
import { GeminiService } from './gemini.service';
import { OllamaService } from './ollama.service';
import { ZaiService } from './zai.service';

@Module({
  imports: [
    MembersModule,
    AttendanceModule,
    BodyProgressModule,
    WorkoutRoutinesModule,
  ],
  controllers: [AiChatController],
  providers: [
    AiChatService,
    AiToolsService,
    GeminiService,
    OllamaService,
    ZaiService,
    AiModelService,
  ],
  exports: [AiChatService, AiModelService],
})
export class AiAssistantModule {}
