<?php
/**
 * Cliente API de Brevo (v3 REST) para gestión de listas y campañas,
 * y envío de emails transaccionales vía SMTP (OVH).
 */

require_once __DIR__ . '/mailer.php';

class BrevoAPI {

    private string $apiKey;
    private string $baseUrl = 'https://api.brevo.com/v3';
    private string $logFile;

    public function __construct() {
        $this->apiKey  = BREVO_API_KEY;
        $this->logFile = __DIR__ . '/../admin/logs/brevo.log';

        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Añadir contacto a una lista de Brevo.
     */
    public function addContact(string $email, string $nombre, int $listId): array {
        $payload = [
            'email'      => $email,
            'attributes' => ['FIRSTNAME' => $nombre],
            'listIds'    => [$listId],
            'updateEnabled' => true,
        ];
        return $this->request('POST', '/contacts', $payload);
    }

    /**
     * Eliminar contacto de una lista (baja).
     */
    public function removeContact(string $email, int $listId): array {
        $payload = ['emails' => [$email]];
        return $this->request('POST', "/contacts/lists/{$listId}/contacts/remove", $payload);
    }

    /**
     * Obtener estadísticas de una campaña por ID.
     */
    public function getCampaignStats(int $campaignId): array {
        return $this->request('GET', "/emailCampaigns/{$campaignId}");
    }

    /**
     * Enviar email transaccional vía SMTP (OVH).
     * Mantiene la misma interfaz para compatibilidad con el código existente.
     *
     * @param string $fromEmail  Remitente opcional (por defecto MAIL_FROM)
     * @param string $fromName   Nombre remitente opcional (por defecto MAIL_FROM_NAME)
     */
    public function sendTransactional(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        string $textContent = '',
        ?string $fromEmail = null,
        ?string $fromName = null
    ): array {
        $result = enviar_email($toEmail, $toName, $subject, $htmlContent, $fromEmail, $fromName);
        return [
            'success' => $result['success'],
            'data'    => [],
            'error'   => $result['error'],
        ];
    }

    /**
     * Crear campaña de email en Brevo.
     */
    public function createCampaign(
        string $name,
        string $subject,
        string $htmlContent,
        int $listId,
        string $preheader = ''
    ): array {
        $payload = [
            'name'       => $name,
            'subject'    => $subject,
            'sender'     => ['name' => BREVO_FROM_NAME, 'email' => BREVO_FROM_EMAIL],
            'htmlContent' => $htmlContent,
            'recipients' => ['listIds' => [$listId]],
        ];
        if ($preheader !== '') {
            $payload['previewText'] = $preheader;
        }
        return $this->request('POST', '/emailCampaigns', $payload);
    }

    /**
     * Activar envío inmediato de una campaña.
     */
    public function sendCampaignNow(int $campaignId): array {
        return $this->request('POST', "/emailCampaigns/{$campaignId}/sendNow");
    }

    /**
     * Petición HTTP a la API de Brevo.
     */
    private function request(string $method, string $endpoint, ?array $payload = null): array {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->log("CURL ERROR [{$method} {$endpoint}]: {$curlError}");
            return ['success' => false, 'data' => [], 'error' => $curlError];
        }

        $data = json_decode($response, true) ?? [];

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $data, 'error' => ''];
        }

        $errorMsg = $data['message'] ?? "HTTP {$httpCode}";
        $this->log("API ERROR [{$method} {$endpoint}] HTTP {$httpCode}: {$errorMsg}");
        return ['success' => false, 'data' => $data, 'error' => $errorMsg];
    }

    /**
     * Registrar mensaje en el log.
     */
    private function log(string $message): void {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
