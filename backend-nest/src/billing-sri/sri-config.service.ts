import { Injectable } from '@nestjs/common';
import { sri_series_estado } from '@prisma/client';
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { PrismaService } from '../database/prisma.service';
import { normalizeSriEnvironment } from './utils/sri-environment.util';

type ReadinessCheck = {
  id: string;
  ok: boolean;
  message: string;
};

function isSmtpConfigured() {
  return !!(
    process.env.SMTP_HOST?.trim() &&
    process.env.SMTP_USER?.trim() &&
    process.env.SMTP_PASS?.trim()
  );
}

@Injectable()
export class SriConfigService {
  constructor(private readonly prisma: PrismaService) {}

  /**
   * Regla de seguridad:
   * La API nunca expone la clave del certificado P12 ni rutas absolutas del servidor.
   * Solo indica si el archivo configurado existe para validar readiness fiscal.
   */
  async getPublicConfig() {
    const config = await this.prisma.configuracion.findFirst({
      select: {
        ruc: true,
        razon_social: true,
        nombre_comercial: true,
        direccion: true,
        telefono: true,
        email: true,
        iva_tasa: true,
        incluye_iva: true,
        sri_ambiente: true,
        sri_establecimiento: true,
        sri_punto_emision: true,
        sri_certificado_p12: true,
        sri_certificado_clave: true,
      },
    });

    const certDir = process.env.SRI_CERT_DIR ?? 'cert';
    const certFile = config?.sri_certificado_p12 ?? '';
    const certPath = certFile ? join(process.cwd(), 'public', certDir, certFile) : '';

    const series = await this.prisma.sri_series.findMany({
      where: { estado: sri_series_estado.activo },
      orderBy: [{ tipo_doc: 'asc' }, { serie: 'asc' }],
      select: {
        id: true,
        tipo_doc: true,
        serie: true,
        correlativo: true,
        descripcion: true,
      },
    });

    const environment = normalizeSriEnvironment(config?.sri_ambiente);
    const readiness = this.buildReadiness(config, certFile, certPath, environment);

    return {
      taxId: config?.ruc ?? null,
      legalName: config?.razon_social ?? null,
      tradeName: config?.nombre_comercial ?? null,
      address: config?.direccion ?? null,
      phone: config?.telefono ?? null,
      email: config?.email ?? null,
      vatRate: config?.iva_tasa != null ? Number(config.iva_tasa) : 15,
      pricesIncludeVat: config?.incluye_iva ?? true,
      environment,
      establishment: config?.sri_establecimiento ?? '001',
      emissionPoint: config?.sri_punto_emision ?? '001',
      certificateFileName: certFile || null,
      hasCertificate: certFile ? existsSync(certPath) : false,
      hasCertificatePassword: !!(config?.sri_certificado_clave?.trim()),
      smtpConfigured: isSmtpConfigured(),
      productionReady: readiness.productionReady,
      readinessChecks: readiness.checks,
      sriEndpoints:
        environment === '2'
          ? {
              reception:
                'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline',
              authorization:
                'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline',
            }
          : {
              reception:
                'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline',
              authorization:
                'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline',
            },
      activeSeries: series.map((row) => ({
        id: row.id,
        documentType: row.tipo_doc,
        series: row.serie,
        currentSequence: row.correlativo,
        description: row.descripcion,
      })),
    };
  }

  private buildReadiness(
    config: {
      ruc?: string | null;
      razon_social?: string | null;
      sri_establecimiento?: string | null;
      sri_punto_emision?: string | null;
      sri_certificado_clave?: string | null;
    } | null,
    certFile: string,
    certPath: string,
    environment: '1' | '2',
  ) {
    const checks: ReadinessCheck[] = [
      {
        id: 'ruc',
        ok: !!(config?.ruc?.trim() && config.ruc.replace(/\D/g, '').length >= 10),
        message: config?.ruc
          ? 'RUC configurado'
          : 'Configure el RUC en configuración del sistema',
      },
      {
        id: 'legal_name',
        ok: !!config?.razon_social?.trim(),
        message: config?.razon_social
          ? 'Razón social configurada'
          : 'Configure la razón social del emisor',
      },
      {
        id: 'establishment',
        ok: !!config?.sri_establecimiento?.trim(),
        message: 'Establecimiento y punto de emisión configurados',
      },
      {
        id: 'certificate_file',
        ok: !!certFile && existsSync(certPath),
        message:
          certFile && existsSync(certPath)
            ? 'Archivo P12 encontrado en servidor'
            : 'Suba el certificado .p12 a public/cert/ y regístrelo en configuración',
      },
      {
        id: 'certificate_password',
        ok: !!config?.sri_certificado_clave?.trim(),
        message: config?.sri_certificado_clave
          ? 'Clave del certificado registrada (no expuesta por API)'
          : 'Registre la clave del certificado P12 en configuración',
      },
      {
        id: 'smtp',
        ok: isSmtpConfigured(),
        message: isSmtpConfigured()
          ? 'SMTP configurado para envío de comprobantes'
          : 'Opcional: configure SMTP_HOST, SMTP_USER y SMTP_PASS para email a clientes',
      },
    ];

    const productionChecks = checks.filter((item) => item.id !== 'smtp');
    const productionReady =
      environment === '2' &&
      productionChecks.every((item) => item.ok);

    if (environment === '1') {
      checks.push({
        id: 'environment',
        ok: true,
        message:
          'Ambiente de pruebas (celcer). Para producción cambie a ambiente 2 y valide con P12 real.',
      });
    } else {
      checks.push({
        id: 'environment',
        ok: productionReady,
        message: productionReady
          ? 'Ambiente producción listo para emisión real en cel.sri.gob.ec'
          : 'Ambiente producción: complete certificado P12 y datos fiscales antes de emitir',
      });
    }

    return { productionReady, checks };
  }
}
