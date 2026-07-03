<?php
/**
 * WhatsAppNotifier
 * Sends WhatsApp messages via the free CallMeBot API.
 * Documentation: https://www.callmebot.com/blog/free-api-whatsapp-messages/
 *
 * IMPORTANT: Each recipient must activate their number once by sending
 *   "I allow callmebot to send me messages"
 * to +34 644 68 38 85 on WhatsApp before messages can be delivered.
 */
class WhatsAppNotifier {

    /**
     * Sends a WhatsApp message to a single recipient.
     *
     * @param string $telefono  Recipient phone number with country code (e.g. +593987654321)
     * @param string $apikey    The CallMeBot API key assigned to the recipient.
     * @param string $mensaje   Plain-text message to send.
     * @return array            ['success' => bool, 'response' => string]
     */
    public static function enviar(string $telefono, string $apikey, string $mensaje): array {
        // Sanitize: remove spaces from phone
        $telefono = preg_replace('/\s+/', '', $telefono);

        $url = "https://api.callmebot.com/whatsapp.php?"
             . "phone="   . urlencode($telefono)
             . "&text="   . urlencode($mensaje)
             . "&apikey=" . urlencode($apikey);

        // Use cURL for better error handling
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'response' => "cURL Error: $error"];
        }

        // CallMeBot returns "Message queued" on success
        $success = (str_contains($response, 'Message queued') || $httpCode === 200);

        return ['success' => $success, 'response' => $response];
    }

    /**
     * Builds the standard expiry reminder message for a member.
     *
     * @param string $nombre_socio
     * @param string $nombre_plan
     * @param string $fecha_fin   Date string (Y-m-d)
     * @param string $nombre_gym  Gym name
     */
    public static function mensajeVencimiento(string $nombre_socio, string $nombre_plan, string $fecha_fin, string $nombre_gym = 'Iron Gym'): string {
        $fecha = date('d/m/Y', strtotime($fecha_fin));
        return "⚠️ *$nombre_gym*\n\n"
             . "Hola *$nombre_socio*! 👋\n\n"
             . "Te recordamos que tu membresía *$nombre_plan* vence el *$fecha*.\n\n"
             . "Renuévala a tiempo para no perder tus beneficios. 💪\n\n"
             . "📍 Visítanos o comunícate con nosotros para renovar.\n"
             . "_¡Tu bienestar es nuestra prioridad!_";
    }
}
