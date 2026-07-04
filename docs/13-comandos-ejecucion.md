# Comandos de ejecución — Gym Pro IA

Referencia rápida de todos los comandos para clonar, instalar, ejecutar, probar y publicar el proyecto.

**Repositorio:** https://github.com/boris13jbb/gim-pro-ia  
**Rama principal:** `master`

---

## 1. Clonar el proyecto

```bash
git clone https://github.com/boris13jbb/gim-pro-ia.git
cd gim_pro_ia
```

---

## 2. Requisitos previos

| Herramienta | Versión mínima |
|-------------|----------------|
| Node.js | 20+ |
| npm | 10+ |
| MySQL / MariaDB | 10+ (BD `ec_gym_system`) |
| Flutter | 3.44+ |
| Git | 2+ |

Verificar:

```bash
node -v
npm -v
flutter --version
git --version
```

---

## 3. Base de datos (primera vez)

### 3.1 Importar BD legacy (opcional, si no tienes datos)

Desde `backend-nest/`:

```bash
cd backend-nest
npm run db:import-legacy
npm run db:pull
npm run db:generate
```

### 3.2 Sincronizar tablas nuevas de NestJS (sin reset destructivo)

```bash
cd backend-nest
npm run db:push:legacy
npm run db:generate
```

### 3.3 Migraciones Prisma (BD nueva o con migraciones aplicadas)

```bash
cd backend-nest
npx prisma migrate deploy
npx prisma generate
```

> **Importante:** No usar `npx prisma migrate dev` si pide reset en BD legacy importada. Ver `docs/11-riesgos-y-rollback.md`.

### 3.4 Prisma Studio (explorar BD)

```bash
cd backend-nest
npx prisma studio
```

---

## 4. Backend NestJS

### 4.1 Instalación (primera vez)

**Linux / macOS / Git Bash:**

```bash
cd backend-nest
cp .env.example .env
npm install
npx prisma migrate deploy
```

**Windows PowerShell:**

```powershell
cd backend-nest
Copy-Item .env.example .env
npm install
npx prisma migrate deploy
```

Editar `.env` con tus credenciales reales (`DATABASE_URL`, JWT, Gemini, SMTP, etc.).  
**Nunca subir `.env` a GitHub.**

### 4.2 Ejecutar en desarrollo

```bash
cd backend-nest
npm run start:dev
```

Desde la **raíz del monorepo**:

```bash
npm run backend:start:dev
```

### 4.3 Ejecutar en producción

```bash
cd backend-nest
npm run build
npm run start:prod
```

Desde la raíz:

```bash
npm run backend:build
cd backend-nest
npm run start:prod
```

### 4.4 Otros modos backend

```bash
cd backend-nest
npm run start          # sin watch
npm run start:debug    # con inspector Node
```

### 4.5 Calidad de código (backend)

```bash
cd backend-nest
npm run build
npm run lint
npm run format
npm run test
npm run test:e2e
npm run test:cov
```

---

## 5. Flutter (app socios)

### 5.1 Instalación (primera vez)

```bash
cd frontend-flutter
flutter pub get
```

### 5.2 Ejecutar por plataforma

**Windows:**

```bash
cd frontend-flutter
flutter run -d windows
```

**Web (Chrome):**

```bash
cd frontend-flutter
flutter run -d chrome --web-port=8080
```

**Android (emulador o dispositivo):**

```bash
cd frontend-flutter
flutter devices
flutter run
```

**iOS (solo macOS):**

```bash
cd frontend-flutter
flutter run -d ios
```

### 5.3 API en red local (dispositivo físico)

Reemplaza la IP por la de tu PC en la red:

```bash
cd frontend-flutter
flutter run --dart-define=API_BASE_URL=http://192.168.100.140:3000/api
```

Ejemplos por plataforma:

```bash
# Windows
flutter run -d windows --dart-define=API_BASE_URL=http://192.168.100.140:3000/api

# Chrome
flutter run -d chrome --web-port=8080 --dart-define=API_BASE_URL=http://192.168.100.140:3000/api

# Android emulador (API en la misma máquina)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:3000/api
```

### 5.4 URLs API por defecto (sin dart-define)

| Plataforma | URL base |
|------------|----------|
| Windows / Web / iOS sim | `http://localhost:3000/api` |
| Android emulador | `http://10.0.2.2:3000/api` |

### 5.5 Calidad de código (Flutter)

```bash
cd frontend-flutter
flutter analyze
flutter test
```

---

## 6. Flujo diario recomendado (2 terminales)

**Terminal 1 — API:**

```bash
cd backend-nest
npm run start:dev
```

**Terminal 2 — App Flutter:**

```bash
cd frontend-flutter
flutter run -d windows
# o: flutter run -d chrome --web-port=8080
```

---

## 7. Verificación rápida de la API

Con el backend corriendo en `http://localhost:3000`:

```bash
# Health check
curl http://localhost:3000/api/health

# Swagger (abrir en navegador)
# http://localhost:3000/api/docs
```

**PowerShell (health):**

```powershell
Invoke-RestMethod http://localhost:3000/api/health
```

**Login staff (ejemplo):**

```bash
curl -X POST http://localhost:3000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@irongym.com\",\"password\":\"tu_password\"}"
```

**PowerShell:**

```powershell
Invoke-RestMethod -Method POST -Uri http://localhost:3000/api/auth/login `
  -ContentType "application/json" `
  -Body '{"email":"admin@irongym.com","password":"tu_password"}'
```

---

## 8. Auditorías por fase (desde la raíz)

Requieren API en ejecución y BD configurada.

```bash
# Desde la raíz del monorepo
npm run audit:phase-02
npm run audit:phase-03
npm run audit:phase-04
npm run audit:phase-05
npm run audit:phase-06
npm run audit:phase-07
npm run audit:phase-08
npm run audit:phase-09
npm run audit:phase-10
npm run audit:phase-11
```

Equivalente desde `backend-nest/`:

```bash
cd backend-nest
npm run audit:phase-02
# ... hasta audit:phase-11
```

| Fase | Módulo |
|------|--------|
| 02 | Auth, usuarios, roles |
| 03 | Socios, planes, membresías |
| 04 | Asistencias, QR |
| 05 | Progreso físico, rutinas |
| 06 | Inventario, productos |
| 07 | POS, ventas, caja |
| 08 | Reportes |
| 09 | Facturación SRI |
| 10 | App Flutter socios |
| 11 | Asistente IA Gemini |

---

## 9. Scripts de base de datos (backend-nest)

```bash
cd backend-nest

npm run db:import-legacy   # Importa gym-system/bk_basededatos.sql
npm run db:pull              # prisma db pull
npm run db:push              # prisma db push
npm run db:push:legacy       # push seguro en BD legacy
npm run db:generate          # prisma generate
```

---

## 10. Git — subir cambios a GitHub

```bash
# Ver estado
git status

# Agregar cambios (excluye .env y gym-system por .gitignore)
git add .

# Commit
git commit -m "fase-XX: descripción del cambio"

# Subir
git push origin master
```

**PowerShell (commit con mensaje multilínea):**

```powershell
git add .
git commit -m @"
fase-XX: descripción del cambio

Detalle adicional si aplica.
"@
git push origin master
```

> `gym-system/` y `backend-nest/.env` **no se suben** (están en `.gitignore`).

---

## 11. Variables de entorno clave (`.env`)

Copiar desde `backend-nest/.env.example` y configurar:

| Variable | Uso |
|----------|-----|
| `PORT` | Puerto API (default `3000`) |
| `DATABASE_URL` | MySQL/MariaDB — usar `127.0.0.1` en Windows |
| `JWT_ACCESS_SECRET` / `JWT_REFRESH_SECRET` | Tokens JWT |
| `CORS_ORIGINS` | Orígenes Flutter (incluir `:8080` para web) |
| `GEMINI_API_KEY` | IA Fase 11 (solo servidor) |
| `SMTP_*` | Envío de comprobantes SRI |

---

## 12. Sistema PHP legacy (solo local)

`gym-system/` no está en GitHub. Si lo tienes en tu PC:

```bash
# Ejemplo con PHP built-in server (ajustar según tu entorno Laragon/XAMPP)
cd gym-system/public
php -S localhost:8000
```

---

## 13. Resumen ultra-rápido

```bash
# 1. Clonar
git clone https://github.com/boris13jbb/gim-pro-ia.git
cd gim_pro_ia

# 2. Backend
cd backend-nest
cp .env.example .env          # Windows: Copy-Item .env.example .env
npm install
npx prisma migrate deploy
npm run start:dev

# 3. Flutter (otra terminal)
cd frontend-flutter
flutter pub get
flutter run -d windows
# o red local:
flutter run --dart-define=API_BASE_URL=http://TU_IP:3000/api
```

---

## Documentación relacionada

- `docs/08-guia-instalacion-backend.md`
- `docs/09-guia-instalacion-flutter.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/11-riesgos-y-rollback.md`
