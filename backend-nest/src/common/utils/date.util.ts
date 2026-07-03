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
