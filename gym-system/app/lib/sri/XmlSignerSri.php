<?php
/**
 * Firmador XML para SRI Ecuador — soporte nativo de certificados PKCS#12 (.p12) usando OpenSSL.
 * 
 * Inserta la firma digital estándar enveloped <ds:Signature> directamente en la raíz del documento.
 */
class XmlSignerSri {

    private $privateKey;
    private $certBase64;

    /**
     * Carga el certificado PKCS#12 (.p12) directamente.
     * 
     * @param string $p12Ruta   Ruta absoluta al archivo .p12
     * @param string $password  Clave de cifrado del certificado
     */
    public function __construct($p12Ruta, $password = '') {
        if (!file_exists($p12Ruta)) {
            throw new Exception("Certificado digital no encontrado: $p12Ruta");
        }
        
        $p12Contenido = file_get_contents($p12Ruta);
        $certs = [];
        
        if (!openssl_pkcs12_read($p12Contenido, $certs, $password)) {
            // Intentar cargar como .pem si falló el .p12 (por si subieron PEM renombrado)
            $pem = $p12Contenido;
            $this->privateKey = openssl_pkey_get_private($pem, $password);
            if (!$this->privateKey) {
                throw new Exception("No se pudo leer el archivo .p12. Verifique la contraseña.");
            }
            if (preg_match('/-----BEGIN CERTIFICATE-----[\s\S]+?-----END CERTIFICATE-----/', $pem, $m)) {
                $this->certBase64 = preg_replace('/-----.+?-----|\s+/', '', $m[0]);
            } else {
                throw new Exception("No se encontró certificado X.509 en el archivo.");
            }
            return;
        }

        $this->privateKey = openssl_pkey_get_private($certs['pkey']);
        if (!$this->privateKey) {
            throw new Exception("No se pudo obtener la clave privada del certificado.");
        }

        // Extraer base64 del certificado
        if (preg_match('/-----BEGIN CERTIFICATE-----[\s\S]+?-----END CERTIFICATE-----/', $certs['cert'], $m)) {
            $this->certBase64 = preg_replace('/-----.+?-----|\s+/', '', $m[0]);
        } else {
            throw new Exception("No se pudo leer el certificado X.509.");
        }
    }

    /**
     * Firma un comprobante XML (Factura / Nota de Crédito).
     * Devuelve el XML firmado como string.
     */
    public function firmar($xml) {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        
        // Evitar warnings por entidades externas
        $oldEntityLoader = libxml_disable_entity_loader(true);
        $doc->loadXML($xml);
        libxml_disable_entity_loader($oldEntityLoader);

        // 1) Canonicalizar el documento completo
        $c14n = $doc->C14N(false, false);

        // 2) DigestValue = SHA1 en base64
        $digest = base64_encode(sha1($c14n, true));

        // 3) Construir <SignedInfo>
        $signedInfo = $this->signedInfoXml($digest);

        // 4) Firmar el SignedInfo canonicalizado
        $tmp = new DOMDocument('1.0', 'UTF-8');
        $tmp->loadXML('<root xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $signedInfo . '</root>');
        $siNode = $tmp->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'SignedInfo')->item(0);
        $siC14n = $siNode->C14N(false, false);

        $signature = '';
        if (!openssl_sign($siC14n, $signature, $this->privateKey, OPENSSL_ALGO_SHA1)) {
            throw new Exception('Fallo al firmar el documento.');
        }
        $signatureValue = base64_encode($signature);

        // 5) Componer el bloque ds:Signature completo
        $dsSignature =
            '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="SignatureSRI">'
            . $signedInfo
            . '<ds:SignatureValue>' . $this->wrap64($signatureValue) . '</ds:SignatureValue>'
            . '<ds:KeyInfo>'
            .   '<ds:X509Data>'
            .     '<ds:X509Certificate>' . $this->wrap64($this->certBase64) . '</ds:X509Certificate>'
            .   '</ds:X509Data>'
            . '</ds:KeyInfo>'
            . '</ds:Signature>';

        // 6) Insertar la firma al final del nodo principal del comprobante
        $root = $doc->documentElement;
        $frag = $doc->createDocumentFragment();
        $frag->appendXML($dsSignature);
        $root->appendChild($frag);

        return $doc->saveXML();
    }

    private function signedInfoXml($digest) {
        return
            '<ds:SignedInfo>'
            . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>'
            . '<ds:Reference URI="">'
            .   '<ds:Transforms>'
            .     '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>'
            .   '</ds:Transforms>'
            .   '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>'
            .   '<ds:DigestValue>' . $digest . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '</ds:SignedInfo>';
    }

    private function wrap64($base64, $width = 76) {
        return rtrim(chunk_split($base64, $width, "\n"), "\n");
    }

    /** Calcula el Hash digest value (DigestValue) */
    public static function calcularHash($xmlFirmado) {
        if (preg_match('#<ds:DigestValue>([^<]+)</ds:DigestValue>#', $xmlFirmado, $m)) {
            return trim($m[1]);
        }
        return '';
    }
}
