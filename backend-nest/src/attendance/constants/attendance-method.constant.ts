export const ATTENDANCE_METHODS = ['manual', 'dni', 'qr', 'app'] as const;
export type AttendanceMethod = (typeof ATTENDANCE_METHODS)[number];
