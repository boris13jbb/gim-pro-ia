export const STAFF_ROLES = ['admin', 'recepcionista', 'entrenador'] as const;
export type StaffRole = (typeof STAFF_ROLES)[number];

export const ALL_ROLES = [...STAFF_ROLES, 'socio'] as const;
export type AppRole = (typeof ALL_ROLES)[number];
