import { Injectable } from '@nestjs/common';

import { PrismaService } from '../../database/prisma.service';

import type { UserType } from '../types/jwt-payload.type';

import { createJti, hashToken } from '../utils/token.util';



export interface StoreRefreshTokenInput {

  userType: UserType;

  userId?: number;

  memberId?: number;

  refreshToken: string;

  expiresAt: Date;

  userAgent?: string;

  ip?: string;

}



@Injectable()

export class RefreshTokenService {

  constructor(private readonly prisma: PrismaService) {}



  /**

   * Persiste el refresh token hasheado (nunca en texto plano).

   * Staff usa userId; socios usan memberId.

   */

  async store(input: StoreRefreshTokenInput, jti = createJti()) {

    await this.prisma.auth_refresh_tokens.create({

      data: {

        userId: input.userType === 'staff' ? input.userId : null,

        memberId: input.userType === 'member' ? input.memberId : null,

        jti,

        tokenHash: hashToken(input.refreshToken),

        expiresAt: input.expiresAt,

        userAgent: input.userAgent,

        ip: input.ip,

      },

    });

    return jti;

  }



  async findActiveByJti(jti: string) {

    return this.prisma.auth_refresh_tokens.findFirst({

      where: {

        jti,

        revokedAt: null,

        expiresAt: { gt: new Date() },

      },

    });

  }



  async validateToken(jti: string, refreshToken: string) {

    const record = await this.findActiveByJti(jti);

    if (!record) return null;

    if (record.tokenHash !== hashToken(refreshToken)) return null;

    return record;

  }



  async revokeByJti(jti: string, replacedById?: number) {

    await this.prisma.auth_refresh_tokens.updateMany({

      where: { jti, revokedAt: null },

      data: {

        revokedAt: new Date(),

        ...(replacedById ? { replacedById } : {}),

      },

    });

  }



  async revokeAllForStaff(userId: number) {

    await this.prisma.auth_refresh_tokens.updateMany({

      where: { userId, revokedAt: null },

      data: { revokedAt: new Date() },

    });

  }



  async revokeAllForMember(memberId: number) {

    await this.prisma.auth_refresh_tokens.updateMany({

      where: { memberId, revokedAt: null },

      data: { revokedAt: new Date() },

    });

  }

}

