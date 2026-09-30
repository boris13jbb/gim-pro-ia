import { Module } from '@nestjs/common';
import { MembersModule } from '../members/members.module';
import { AttendanceModule } from '../attendance/attendance.module';
import { BodyProgressModule } from '../body-progress/body-progress.module';
import { WorkoutRoutinesModule } from '../workout-routines/workout-routines.module';
import { AiChatController } from './ai-chat.controller';
import { AiVoiceController } from './ai-voice.controller';
import { AiChatService } from './ai-chat.service';
import { AiIntentService } from './ai-intent.service';
import { AiModelService } from './ai-model.service';
import { AiToolsService } from './ai-tools.service';
import { GeminiService } from './gemini.service';
import { OllamaService } from './ollama.service';
import { ZaiService } from './zai.service';
import { WhisperSttService } from './voice/whisper-stt.service';
import { PiperTtsService } from './voice/piper-tts.service';
import { AiVoiceService } from './voice/ai-voice.service';

@Module({
  imports: [
    MembersModule,
    AttendanceModule,
    BodyProgressModule,
    WorkoutRoutinesModule,
  ],
  controllers: [AiChatController, AiVoiceController],
  providers: [
    AiChatService,
    AiToolsService,
    AiIntentService,
    GeminiService,
    OllamaService,
    ZaiService,
    AiModelService,
    WhisperSttService,
    PiperTtsService,
    AiVoiceService,
  ],
  exports: [AiChatService, AiModelService, AiVoiceService],
})
export class AiAssistantModule {}
