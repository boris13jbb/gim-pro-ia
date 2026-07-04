import { Module } from '@nestjs/common';
import { AppController } from './app.controller';
import { AppService } from './app.service';
import { ConfigModule } from '@nestjs/config';
import { ThrottlerModule, ThrottlerGuard } from '@nestjs/throttler';
import { TerminusModule } from '@nestjs/terminus';
import { HealthController } from './health/health.controller';
import { DatabaseModule } from './database/database.module';
import { APP_GUARD } from '@nestjs/core';
import { AuthModule } from './auth/auth.module';
import { UsersModule } from './users/users.module';
import { MembersModule } from './members/members.module';
import { PlansModule } from './plans/plans.module';
import { MembershipsModule } from './memberships/memberships.module';
import { AttendanceModule } from './attendance/attendance.module';
import { QrAccessModule } from './qr-access/qr-access.module';
import { BodyProgressModule } from './body-progress/body-progress.module';
import { WorkoutRoutinesModule } from './workout-routines/workout-routines.module';
import { CategoriesModule } from './categories/categories.module';
import { ProductsModule } from './products/products.module';
import { InventoryModule } from './inventory/inventory.module';
import { CashRegistersModule } from './cash-registers/cash-registers.module';
import { SalesModule } from './sales/sales.module';
import { ReportsModule } from './reports/reports.module';
import { BillingSriModule } from './billing-sri/billing-sri.module';
import { AiAssistantModule } from './ai-assistant/ai-assistant.module';
import { WebsocketModule } from './websocket/websocket.module';
import { RealtimeModule } from './websocket/realtime.module';
import { JwtAccessGuard } from './auth/guards/jwt-access.guard';
import { RolesGuard } from './auth/guards/roles.guard';
import { getThrottleConfig } from './config/throttle.config';

const throttleConfig = getThrottleConfig();

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
    }),
    ThrottlerModule.forRoot([
      {
        ttl: throttleConfig.global.ttl,
        limit: throttleConfig.global.limit,
      },
    ]),
    TerminusModule,
    DatabaseModule,
    AuthModule,
    UsersModule,
    MembersModule,
    PlansModule,
    MembershipsModule,
    AttendanceModule,
    QrAccessModule,
    BodyProgressModule,
    WorkoutRoutinesModule,
    CategoriesModule,
    ProductsModule,
    InventoryModule,
    CashRegistersModule,
    SalesModule,
    ReportsModule,
    BillingSriModule,
    AiAssistantModule,
    WebsocketModule,
    RealtimeModule,
  ],
  controllers: [AppController, HealthController],
  providers: [
    AppService,
    {
      provide: APP_GUARD,
      useClass: JwtAccessGuard,
    },
    {
      provide: APP_GUARD,
      useClass: RolesGuard,
    },
    {
      provide: APP_GUARD,
      useClass: ThrottlerGuard,
    },
  ],
})
export class AppModule {}
