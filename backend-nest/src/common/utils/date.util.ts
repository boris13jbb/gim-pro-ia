/**
 * Parsea YYYY-MM-DD en hora local (evita desfase UTC que marca membresías vencidas).
 */
export function parseLocalDateString(value: string): Date {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day, 0, 0, 0, 0);
}

export function startOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

/**
 * Prisma/MySQL `@db.Date` suele devolver medianoche UTC (YYYY-MM-DDT00:00:00.000Z).
 * En America/Guayaquil (UTC-5), `startOfDay()` local adelanta/atrasa el día calendario.
 * Esta función recupera el día de calendario usando componentes UTC.
 */
export function fromPrismaDate(date: Date): Date {
  return new Date(
    date.getUTCFullYear(),
    date.getUTCMonth(),
    date.getUTCDate(),
    0,
    0,
    0,
    0,
  );
}

/**
 * Convierte un día de calendario local a medianoche UTC.
 * Útil para filtrar columnas DATE de MySQL/Prisma sin desfase por zona horaria.
 */
export function localCalendarAsUtcDate(date: Date = new Date()): Date {
  return new Date(
    Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
  );
}

export function getTodayRange() {
  const start = startOfDay(new Date());
  const end = new Date(start);
  end.setHours(23, 59, 59, 999);
  return { start, end };
}

export function getDateRange(from: string, to: string) {
  const start = parseLocalDateString(from);
  const end = parseLocalDateString(to);
  end.setHours(23, 59, 59, 999);
  return { start, end };
}

export function todayDateString(): string {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function firstDayOfCurrentMonth(): string {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}-01`;
}
