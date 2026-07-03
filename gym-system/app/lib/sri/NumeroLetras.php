<?php
class NumeroLetras {
    private static $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    private static $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    private static $especiales = [
        11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
        16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
        21 => 'VEINTIUNO', 22 => 'VEINTIDOS', 23 => 'VEINTITRES', 24 => 'VEINTICUATRO',
        25 => 'VEINTICINCO', 26 => 'VEINTISEIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO',
        29 => 'VEINTINUEVE'
    ];
    private static $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function convertir($numero, $moneda = 'DOLARES') {
        $partes = explode('.', number_format($numero, 2, '.', ''));
        $entero = (int)$partes[0];
        $centavos = $partes[1];

        if ($entero == 0) {
            $txtEntero = 'CERO';
        } else {
            $txtEntero = self::convertirGrupo($entero);
        }

        return "SON " . $txtEntero . " CON " . $centavos . "/100 " . strtoupper($moneda);
    }

    private static function convertirGrupo($n) {
        if ($n >= 1000000) {
            $millones = (int)($n / 1000000);
            $resto = $n % 1000000;
            $txtMillon = ($millones == 1) ? 'UN MILLON' : self::convertirGrupo($millones) . ' MILLONES';
            return trim($txtMillon . ' ' . self::convertirGrupo($resto));
        }

        if ($n >= 1000) {
            $miles = (int)($n / 1000);
            $resto = $n % 1000;
            $txtMiles = ($miles == 1) ? 'MIL' : self::convertirGrupo($miles) . ' MIL';
            return trim($txtMiles . ' ' . self::convertirGrupo($resto));
        }

        if ($n >= 100) {
            $c = (int)($n / 100);
            $resto = $n % 100;
            if ($n == 100) {
                return 'CIEN';
            }
            return trim(self::$centenas[$c] . ' ' . self::convertirGrupo($resto));
        }

        if ($n >= 10) {
            if (isset(self::$especiales[$n])) {
                return self::$especiales[$n];
            }
            $d = (int)($n / 10);
            $u = $n % 10;
            if ($u == 0) {
                return self::$decenas[$d];
            }
            return self::$decenas[$d] . ' Y ' . self::$unidades[$u];
        }

        return self::$unidades[$n];
    }
}
