import { socios_estado } from '@prisma/client';

export interface AttendanceAccessPreview {
  found: boolean;
  canAccess: boolean;
  reason: string;
  message: string;
  member?: {
    id: number;
    nombre: string;
    dni: string;
    email: string | null;
    telefono: string | null;
    estado: socios_estado;
    foto: string | null;
    photoUrl: string | null;
    fechaRegistro: Date | null;
  };
  memberStatus?: socios_estado | null;
  isMembershipValid?: boolean;
  effectiveStatus?: string;
  daysRemaining?: number;
  endDate?: Date | null;
  currentMembership?: Record<string, unknown> | null;
}
