import { isAllowedCorsOrigin } from './cors.config';

describe('cors.config (Fase 18 LAN)', () => {
  const previousNodeEnv = process.env.NODE_ENV;
  const previousCors = process.env.CORS_ORIGINS;

  beforeEach(() => {
    process.env.NODE_ENV = 'development';
    process.env.CORS_ORIGINS =
      'http://localhost:3000,http://localhost:8888,http://127.0.0.1:8888';
  });

  afterAll(() => {
    process.env.NODE_ENV = previousNodeEnv;
    process.env.CORS_ORIGINS = previousCors;
  });

  it('permite localhost en development', () => {
    expect(isAllowedCorsOrigin('http://localhost:8888')).toBe(true);
    expect(isAllowedCorsOrigin('http://127.0.0.1:8888')).toBe(true);
  });

  it('permite IPv4 privadas LAN en development sin IP fija en código', () => {
    expect(isAllowedCorsOrigin('http://192.168.100.140:8888')).toBe(true);
    expect(isAllowedCorsOrigin('http://10.0.0.5:8888')).toBe(true);
    expect(isAllowedCorsOrigin('http://172.16.1.2:3000')).toBe(true);
  });

  it('mantiene ngrok solo como LEGACY en development', () => {
    expect(isAllowedCorsOrigin('https://abc123.ngrok-free.app')).toBe(true);
  });

  it('rechaza orígenes públicos no listados en development', () => {
    expect(isAllowedCorsOrigin('https://evil.example.com')).toBe(false);
  });

  it('en production no acepta LAN ni ngrok salvo lista explícita', () => {
    process.env.NODE_ENV = 'production';
    expect(isAllowedCorsOrigin('http://192.168.1.10:8888')).toBe(false);
    expect(isAllowedCorsOrigin('https://abc123.ngrok-free.app')).toBe(false);
    expect(isAllowedCorsOrigin('http://localhost:8888')).toBe(true);
  });
});
