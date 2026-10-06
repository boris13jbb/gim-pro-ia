<p align="center">
  <a href="http://nestjs.com/" target="blank"><img src="https://nestjs.com/img/logo-small.svg" width="120" alt="Nest Logo" /></a>
</p>

[circleci-image]: https://img.shields.io/circleci/build/github/nestjs/nest/master?token=abc123def456
[circleci-url]: https://circleci.com/gh/nestjs/nest

  <p align="center">A progressive <a href="http://nodejs.org" target="_blank">Node.js</a> framework for building efficient and scalable server-side applications.</p>
    <p align="center">
<a href="https://www.npmjs.com/~nestjscore" target="_blank"><img src="https://img.shields.io/npm/v/@nestjs/core.svg" alt="NPM Version" /></a>
<a href="https://www.npmjs.com/~nestjscore" target="_blank"><img src="https://img.shields.io/npm/l/@nestjs/core.svg" alt="Package License" /></a>
<a href="https://www.npmjs.com/~nestjscore" target="_blank"><img src="https://img.shields.io/npm/dm/@nestjs/common.svg" alt="NPM Downloads" /></a>
<a href="https://circleci.com/gh/nestjs/nest" target="_blank"><img src="https://img.shields.io/circleci/build/github/nestjs/nest/master" alt="CircleCI" /></a>
<a href="https://discord.gg/G7Qnnhy" target="_blank"><img src="https://img.shields.io/badge/discord-online-brightgreen.svg" alt="Discord"/></a>
<a href="https://opencollective.com/nest#backer" target="_blank"><img src="https://opencollective.com/nest/backers/badge.svg" alt="Backers on Open Collective" /></a>
<a href="https://opencollective.com/nest#sponsor" target="_blank"><img src="https://opencollective.com/nest/sponsors/badge.svg" alt="Sponsors on Open Collective" /></a>
  <a href="https://paypal.me/kamilmysliwiec" target="_blank"><img src="https://img.shields.io/badge/Donate-PayPal-ff3f59.svg" alt="Donate us"/></a>
    <a href="https://opencollective.com/nest#sponsor"  target="_blank"><img src="https://img.shields.io/badge/Support%20us-Open%20Collective-41B883.svg" alt="Support us"></a>
  <a href="https://twitter.com/nestframework" target="_blank"><img src="https://img.shields.io/twitter/follow/nestframework.svg?style=social&label=Follow" alt="Follow us on Twitter"></a>
</p>
  <!--[![Backers on Open Collective](https://opencollective.com/nest/backers/badge.svg)](https://opencollective.com/nest#backer)
  [![Sponsors on Open Collective](https://opencollective.com/nest/sponsors/badge.svg)](https://opencollective.com/nest#sponsor)-->

## Descripción

Backend API en **NestJS** para la migración progresiva de `gym-system/` (PHP MVC) hacia una arquitectura moderna:

- **NestJS** como lógica de negocio centralizada
- **Prisma** como acceso a datos (MySQL existente `ec_gym_system` en fases iniciales)
- **Swagger** para documentación de endpoints
- **Validación** global de DTOs
- **Seguridad** base (Helmet, CORS, rate limit global)

Reglas del proyecto en `.cursor/rules/`:
- `migracion-gym-nombres-estandar.mdc` — diccionario y convenciones de nombres (global)
- `migracion-gym-jwt-auth.mdc` — JWT Access/Refresh (Fase 02)
- `migracion-gym-backend-nestjs.mdc` — arquitectura NestJS

Diccionario oficial documentado en `docs/10-decisiones-tecnicas.md`.

## Project setup

```bash
$ npm install
```

## Configuración de entorno

1. Copia `.env.example` a `.env`
2. Ajusta `DATABASE_URL` para tu MySQL local (misma BD del PHP legacy si estás en fase de compatibilidad)

## Base de datos (Fase 01)

1. Copia `.env.example` a `.env`
2. Importa el backup legacy (crea `ec_gym_system` + datos demo):

```bash
npm run db:import-legacy
npm run db:pull
npm run db:generate
```

3. Valida conectividad con `GET /api/health` (`database.status` debe ser `up`).

### Migraciones, backup y harness MySQL (SAAS-02)

Requieren `TEST_DATABASE_URL` (servidor MySQL **sin** nombre de base, p. ej. `mysql://root:@127.0.0.1:3306`).
Los scripts solo crean/eliminan bases con prefijo `gim_test_` y nunca usan `DATABASE_URL`.

```bash
npm run db:validate-migrations   # baseline sobre base vacía + drift + tablas/FK/únicos
npm run test:integration         # Jest + Prisma real contra base desechable
npm run db:backup:rehearsal      # backup -> restore -> comparación (requiere mysqldump/mysql)
npm run db:backup                # BACKUP_DATABASE_URL obligatorio; salida en backups/ (ignorado por Git)
npm run db:restore:test -- --file <dump.sql>   # restaura SOLO en una base gim_test_*
```

En Windows/XAMPP: `MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe` y `MYSQL_CLIENT_PATH=C:\xampp\mysql\bin\mysql.exe`.
Detalle completo: `docs/SAAS-02-FUNDACIONES.md`.

## Estado del proyecto

| Fase | Módulo | Estado |
|------|--------|--------|
| 01 | Backend base, health, Swagger | ✅ Completada |
| 02 | Auth JWT, users CRUD | ✅ Aprobada |
| 03 | Members, plans, memberships | ✅ Completada |
| 04 | Asistencias/QR | ✅ Completada |
| 05 | Progreso físico y rutinas | ✅ Completada |
| 06 | Inventario y productos | ✅ Completada |
| 07 | POS, ventas y caja | ✅ Completada |
| 08 | Reportes y exportaciones | ✅ Aprobada |
| 09 | Facturación SRI | ✅ Aprobada |
| 10 | Flutter app socio (slices 1–3) | ✅ Aprobada |
| 11 | IA Gemini + WebSockets (chat streaming + notificaciones) | 🔄 Implementada — pendiente prueba manual y aprobación |
| 12 | Cierre de migración | 🔄 En curso |

### Auditorías

```bash
npm run audit:phase-02   # auth/users
npm run audit:phase-03   # socios/membresías
npm run audit:phase-04   # asistencias/QR
npm run audit:phase-05   # progreso/rutinas
npm run audit:phase-06   # inventario/productos
npm run audit:phase-07   # POS/ventas/caja
npm run audit:phase-08   # reportes/exportaciones
npm run audit:phase-09   # SRI consulta (slice 1)
npm run audit:phase-11   # IA asistente (REST)
```

## WebSockets (Fase 11)

Dos namespaces socket.io autenticados por JWT en el handshake (mismo `JWT_ACCESS_SECRET`), aislados por sala `member:{id}`:

- `/ai` — chat del asistente IA en streaming (con respaldo REST `POST /api/ai/chat`).
- `/events` — notificaciones en tiempo real al socio (asistencia/membresía).

Detalle de eventos en `docs/07-endpoints-api.md`.

## Compile and run the project

```bash
# development
$ npm run start

# watch mode
$ npm run start:dev

# production mode
$ npm run start:prod
```

## Swagger

Si `SWAGGER_ENABLED=true`, Swagger queda en:

- `GET /api/docs`

## Health check

- `GET /api/health`

## Run tests

```bash
# unit tests
$ npm run test

# e2e tests
$ npm run test:e2e

# test coverage
$ npm run test:cov
```

## Deployment

When you're ready to deploy your NestJS application to production, there are some key steps you can take to ensure it runs as efficiently as possible. Check out the [deployment documentation](https://docs.nestjs.com/deployment) for more information.

If you are looking for a cloud-based platform to deploy your NestJS application, check out [Mau](https://mau.nestjs.com), our official platform for deploying NestJS applications on AWS. Mau makes deployment straightforward and fast, requiring just a few simple steps:

```bash
$ npm install -g @nestjs/mau
$ mau deploy
```

With Mau, you can deploy your application in just a few clicks, allowing you to focus on building features rather than managing infrastructure.

## Resources

Check out a few resources that may come in handy when working with NestJS:

- Visit the [NestJS Documentation](https://docs.nestjs.com) to learn more about the framework.
- For questions and support, please visit our [Discord channel](https://discord.gg/G7Qnnhy).
- To dive deeper and get more hands-on experience, check out our official video [courses](https://courses.nestjs.com/).
- Deploy your application to AWS with the help of [NestJS Mau](https://mau.nestjs.com) in just a few clicks.
- Visualize your application graph and interact with the NestJS application in real-time using [NestJS Devtools](https://devtools.nestjs.com).
- Need help with your project (part-time to full-time)? Check out our official [enterprise support](https://enterprise.nestjs.com).
- To stay in the loop and get updates, follow us on [X](https://x.com/nestframework) and [LinkedIn](https://linkedin.com/company/nestjs).
- Looking for a job, or have a job to offer? Check out our official [Jobs board](https://jobs.nestjs.com).

## Support

Nest is an MIT-licensed open source project. It can grow thanks to the sponsors and support by the amazing backers. If you'd like to join them, please [read more here](https://docs.nestjs.com/support).

## Stay in touch

- Author - [Kamil Myśliwiec](https://twitter.com/kammysliwiec)
- Website - [https://nestjs.com](https://nestjs.com/)
- Twitter - [@nestframework](https://twitter.com/nestframework)

## License

Nest is [MIT licensed](https://github.com/nestjs/nest/blob/master/LICENSE).
