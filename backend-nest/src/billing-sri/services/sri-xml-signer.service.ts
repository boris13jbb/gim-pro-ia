import { Injectable } from '@nestjs/common';
import { readFileSync } from 'node:fs';
import { DOMParser, XMLSerializer } from '@xmldom/xmldom';
import forge from 'node-forge';
import { SignedXml } from 'xml-crypto';

@Injectable()
export class SriXmlSignerService {
  sign(xml: string, certificatePath: string, password: string) {
    const { privateKeyPem, certificatePem } = this.loadPkcs12(
      certificatePath,
      password,
    );
    const doc = new DOMParser().parseFromString(xml, 'text/xml');
    const rootName = doc.documentElement?.nodeName ?? 'factura';
    const signedXml = new SignedXml({
      privateKey: privateKeyPem,
      publicCert: certificatePem,
    });
    signedXml.signatureAlgorithm =
      'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    signedXml.addReference({
      xpath: `//*[local-name(.)='${rootName}']`,
      transforms: ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
      digestAlgorithm: 'http://www.w3.org/2000/09/xmldsig#sha1',
    });
    signedXml.computeSignature(new XMLSerializer().serializeToString(doc), {
      location: {
        reference: `//*[local-name(.)='${rootName}']`,
        action: 'append',
      },
    });
    return signedXml.getSignedXml();
  }

  extractDigestHash(signedXml: string) {
    const match = signedXml.match(/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/);
    return match?.[1]?.trim() ?? '';
  }

  private loadPkcs12(path: string, password: string) {
    const buffer = readFileSync(path);
    const p12Der = forge.util.createBuffer(buffer.toString('binary'));
    const p12Asn1 = forge.asn1.fromDer(p12Der.getBytes());
    const p12 = forge.pkcs12.pkcs12FromAsn1(p12Asn1, password || undefined);
    const keyBags = p12.getBags({ bagType: forge.pki.oids.pkcs8ShroudedKeyBag });
    const certBags = p12.getBags({ bagType: forge.pki.oids.certBag });
    const privateKey = keyBags[forge.pki.oids.pkcs8ShroudedKeyBag]?.[0]?.key;
    const certificate = certBags[forge.pki.oids.certBag]?.[0]?.cert;
    if (!privateKey || !certificate) {
      throw new Error('No se pudo leer la clave privada o el certificado del P12');
    }
    return {
      privateKeyPem: forge.pki.privateKeyToPem(privateKey),
      certificatePem: forge.pki.certificateToPem(certificate),
    };
  }
}
