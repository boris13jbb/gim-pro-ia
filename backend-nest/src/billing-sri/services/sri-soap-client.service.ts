import { Injectable } from '@nestjs/common';

export type SriSoapResult = {
  ok: boolean;
  status: string;
  messages: string[];
  rawResponse: string;
  simulated?: boolean;
  authorizedXml?: string;
  authorizationDate?: string;
};

@Injectable()
export class SriSoapClientService {
  private readonly urls = {
    '1': {
      reception:
        'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline',
      authorization:
        'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline',
    },
    '2': {
      reception:
        'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline',
      authorization:
        'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline',
    },
  };

  async sendReceipt(
    signedXml: string,
    environment: string,
    options?: { forceSimulate?: boolean },
  ): Promise<SriSoapResult> {
    if (options?.forceSimulate) {
      return this.simulateReception(
        'firma o ambiente de pruebas sin certificado',
      );
    }

    const env = environment === '2' ? '2' : '1';
    const xmlBase64 = Buffer.from(signedXml, 'utf8').toString('base64');
    const body =
      '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion">' +
      '<soapenv:Header/><soapenv:Body><ec:validarComprobante><xml>' +
      xmlBase64 +
      '</xml></ec:validarComprobante></soapenv:Body></soapenv:Envelope>';

    try {
      const response = await this.callSoap(this.urls[env].reception, body);
      return this.parseReceptionResponse(response);
    } catch (error) {
      return this.simulateReception(
        error instanceof Error ? error.message : String(error),
      );
    }
  }

  async authorizeReceipt(
    accessKey: string,
    environment: string,
    options?: { forceSimulate?: boolean },
  ): Promise<SriSoapResult> {
    if (options?.forceSimulate) {
      return this.simulateAuthorization(
        accessKey,
        'firma o ambiente de pruebas sin certificado',
      );
    }

    const env = environment === '2' ? '2' : '1';
    const body =
      '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.autorizacion">' +
      '<soapenv:Header/><soapenv:Body><ec:autorizacionComprobante>' +
      `<claveAccesoComprobante>${accessKey}</claveAccesoComprobante>` +
      '</ec:autorizacionComprobante></soapenv:Body></soapenv:Envelope>';

    try {
      const response = await this.callSoap(this.urls[env].authorization, body);
      return this.parseAuthorizationResponse(response);
    } catch (error) {
      return this.simulateAuthorization(
        accessKey,
        error instanceof Error ? error.message : String(error),
      );
    }
  }

  private async callSoap(url: string, body: string) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 8000);
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'text/xml; charset=utf-8',
          // Regla técnica SRI: JAX-WS offline exige SOAPAction vacío (no el nombre del método).
          SOAPAction: '""',
          Accept: 'text/xml',
          'Cache-Control': 'no-cache',
          Pragma: 'no-cache',
        },
        body,
        signal: controller.signal,
      });
      const text = await response.text();
      if (!text) {
        throw new Error(`Respuesta vacía del SRI (HTTP ${response.status})`);
      }
      return text;
    } finally {
      clearTimeout(timeout);
    }
  }

  private parseReceptionResponse(xmlString: string): SriSoapResult {
    this.assertNoSoapFault(xmlString);
    const status = this.matchTag(xmlString, 'estado') ?? 'DEVUELTA';
    const messages = this.extractMessages(xmlString);
    return {
      ok: status === 'RECIBIDA',
      status,
      messages,
      rawResponse: xmlString,
    };
  }

  private parseAuthorizationResponse(xmlString: string): SriSoapResult {
    this.assertNoSoapFault(xmlString);
    const status = this.matchTag(xmlString, 'estado') ?? 'NO AUTORIZADO';
    const messages = this.extractMessages(xmlString);
    const authorizedBlock = xmlString.match(
      /<comprobante>([\s\S]+?)<\/comprobante>/,
    )?.[1];
    const authorizedXml = authorizedBlock
      ? authorizedBlock.replace('<![CDATA[', '').replace(']]>', '').trim()
      : xmlString;

    return {
      ok: status === 'AUTORIZADO',
      status,
      messages,
      rawResponse: xmlString,
      authorizedXml,
      authorizationDate:
        this.matchTag(xmlString, 'fechaAutorizacion') ?? undefined,
    };
  }

  private extractMessages(xmlString: string) {
    const messages: string[] = [];
    const blocks = xmlString.match(/<mensaje>[\s\S]*?<\/mensaje>/g) ?? [];
    for (const block of blocks) {
      const text = this.matchTag(block, 'mensaje');
      const extra = this.matchTag(block, 'informacionAdicional');
      const type = this.matchTag(block, 'tipo');
      if (text) {
        messages.push(
          `${type ? `[${type}] ` : ''}${text}${extra ? ` (${extra})` : ''}`,
        );
      }
    }
    return messages;
  }

  private matchTag(xml: string, tag: string) {
    const match = xml.match(new RegExp(`<${tag}[^>]*>([^<]+)</${tag}>`));
    return match?.[1]?.trim();
  }

  /** Si el SRI responde fault SOAP, activar simulación (paridad PHP ante fallo de protocolo). */
  private assertNoSoapFault(xmlString: string) {
    if (!/<(?:soap:)?Fault\b/i.test(xmlString)) {
      return;
    }
    const fault =
      this.matchTag(xmlString, 'faultstring') ??
      this.matchTag(xmlString, 'faultcode') ??
      'SOAP Fault del SRI';
    throw new Error(fault);
  }

  private simulateReception(error: string): SriSoapResult {
    return {
      ok: true,
      status: 'RECIBIDA',
      messages: [`Modo simulación: conexión SRI omitida (${error})`],
      rawResponse: '<simulado>RECIBIDA</simulado>',
      simulated: true,
    };
  }

  private simulateAuthorization(
    accessKey: string,
    error: string,
  ): SriSoapResult {
    return {
      ok: true,
      status: 'AUTORIZADO',
      messages: [`Comprobante autorizado en modo simulación local (${error})`],
      rawResponse: '<simulado>AUTORIZADO</simulado>',
      authorizedXml: `<?xml version="1.0" encoding="UTF-8"?><autorizado_simulado><clave>${accessKey}</clave></autorizado_simulado>`,
      authorizationDate: new Date().toISOString(),
      simulated: true,
    };
  }
}
