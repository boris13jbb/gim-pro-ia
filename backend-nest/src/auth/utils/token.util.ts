import { createHash, randomUUID } from 'node:crypto';

export function hashToken(token: string): string {
  return createHash('sha256').update(token).digest('hex');
}

export function createJti(): string {
  return randomUUID().replace(/-/g, '');
}
