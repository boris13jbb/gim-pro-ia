# Fase 18 — Salida de ngrok (flujo LAN)

**Estado:** READY FOR REVIEW (implementación funcional; commit pendiente de autorización)  
**Rama:** `feature/fase-18-salir-ngrok`  
**Base:** `fix/fase-17-membership-alerts-dates` (`650b0a6`)  
**Fecha:** 2026-10-01

## Objetivo

Operar desarrollo, pruebas y demos **sin depender de ngrok free**, usando LAN como flujo principal, manteniendo ngrok como LEGACY y dejando Cloudflare Tunnel preparado (sin inventar cuentas/DNS).

## Problema

- URLs ngrok rotatorias rompen APK/Web.
- Acoplamiento CORS/header a `*.ngrok-free.app`.
- WebSocket (`/events`, `/ai`) debe usar el mismo host que REST.

## Arquitectura

```text
PC NestJS :3000 (0.0.0.0)
     │
     ├── Flutter Web :8888  →  API_BASE_URL=http://IP-LAN:3000/api
     └── Android (misma Wi-Fi) → mismo API_BASE_URL
           REST /api + WS /events + WS /ai (mismo host)
```

Opcional (externo):

```text
Internet → Cloudflare Tunnel (token usuario) → NestJS :3000
```

**Fuera de alcance:** VPS, Hostinger web para Nest, SaaS/`tenant_id`, voz Whisper/Piper, reopen F17, modificar `docs/PLAN-SALIR-DE-NGROK.md`.

## Flujo LAN

```powershell
# Guía + detección IP
.\scripts\run-lan-dev.ps1

# Backend
cd backend-nest
npm run start:dev

# Flutter Web
.\scripts\run-lan-dev.ps1 -StartWeb
# o:
cd frontend-flutter
flutter run -d chrome --web-port=8888 --dart-define=API_BASE_URL=http://<IP-LAN>:3000/api

# Android
.\scripts\run-android-physical.ps1 -PcIp <IP-LAN>
```

## APK sin ngrok

```powershell
.\scripts\build-apk-release.ps1 -ApiBaseUrl "http://<IP-LAN>:3000/api"
```

Salida: `apk-dist/IronGym-lan-*.apk`

## Cloudflare Tunnel

- Script: `scripts/start-cloudflare-tunnel.ps1`
- Requiere `cloudflared` + `CLOUDFLARE_TUNNEL_TOKEN`
- **Sin token:** `PREPARADO PERO PENDIENTE DE CONFIGURACIÓN EXTERNA` (exit 2)
- En esta máquina: `cloudflared` **sí** está instalado; falta configuración externa (cuenta/hostname/token)

## CORS

Archivo: `backend-nest/src/config/cors.config.ts`

- Development: `CORS_ORIGINS` + localhost:* + **IPv4 privadas RFC1918** (sin IP fija) + ngrok LEGACY
- Production: solo lista explícita (sin LAN/ngrok automáticos)

## Configuración Flutter

`ApiConfig`:

1. `API_BASE_URL`
2. `API_HOST` (+ `API_PORT`)
3. Defaults plataforma

`socketBaseUrl` deriva del mismo host (quita `/api`).

## ngrok LEGACY

Conservados y marcados LEGACY:

- `scripts/start-ngrok-gym.ps1`
- `scripts/web-ngrok.ps1`
- `scripts/build-apk-ngrok.ps1`
- `scripts/ngrok-gym.yml`

## Pruebas

| Prueba | Resultado | Evidencia |
|--------|-----------|-----------|
| Health localhost | PASS* | `GET /api/health` 200 (con backend activo) |
| Health LAN | PASS* | `http://<IP-LAN>:3000/api/health` 200 |
| CORS Origin LAN | PASS* | header allow-origin / test unitario |
| Unit CORS | PASS | `npx jest --testPathPatterns=cors.config.spec` |
| `npm run build` | PASS | Nest build OK |
| Login socio LAN | PENDIENTE MANUAL | Requiere `MEMBER_LOGIN`/`MEMBER_PASSWORD` en entorno |
| Flutter Web LAN | PENDIENTE MANUAL | Comando documentado `-StartWeb` |
| Android LAN | PENDIENTE MANUAL | `run-android-physical.ps1` |
| WS `/events` / `/ai` | PENDIENTE MANUAL | Mismo host que REST vía ApiConfig |
| Chat IA / F17 | PENDIENTE MANUAL | No regresiones esperadas (sin cambios F17) |
| ngrok no obligatorio | PASS | Flujo LAN no invoca ngrok |
| Cloudflare Tunnel | PENDIENTE CONFIG EXTERNA | Software OK; sin token |

\* Validado con backend en marcha y script `npm run audit:phase-18`.

## Riesgos / limitaciones

- Firewall Windows puede bloquear :3000 desde el teléfono.
- Cloudflare Tunnel no funciona 24/7 si el PC está apagado.
- Hostinger web compartido sigue sin servir para Nest+WS.
- APK LAN solo útil en la misma red (o Tunnel/URL pública).

## Rollback

Revertir cambios de la rama `feature/fase-18-salir-ngrok`. Scripts ngrok siguen disponibles.

## Archivos tocados (F18)

Ver informe de implementación / `git status`.

## Criterios de aceptación

Ver checklist en `docs/06-checklist-pruebas.md` sección Fase 18.

## Nota sobre PLAN-SALIR-DE-NGROK.md

El archivo `docs/PLAN-SALIR-DE-NGROK.md` **permanece untracked** hasta autorización explícita del usuario.
