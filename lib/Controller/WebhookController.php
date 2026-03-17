<?php
declare(strict_types=1);

namespace OCA\GefexProvisioning\Controller;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;

use Exception;

class WebhookController extends Controller {
    private IUserManager $userManager;
    private IConfig $config;
    
    private const DEFAULT_FREE_QUOTA = '1 GB';
    private const MAX_PAYLOAD_SIZE = 10240;

    public function __construct(string $appName, IRequest $request, IUserManager $userManager, IConfig $config) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->config = $config;
    }

     /**
     * ATRIBUTOS DE SEGURIDAD NATIVOS (PHP 8 / Nextcloud 33)
     * Desactivan el firewall interno de forma explícita y segura para este endpoint.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function handleRequest(): DataResponse {
        $signatureHeader = $this->request->getHeader('X-GEFEX-SIGNATURE') ?? '';

        $rawPayload = $this->readSafePayload();
        if ($rawPayload === null) {
            return new DataResponse(['error' => 'Payload Too Large'], Http::STATUS_PAYLOAD_TOO_LARGE);
        }

        $webhookSecret = $this->config->getAppValue('gefex_provisioning', 'webhook_secret', '');
        
        if (empty($webhookSecret)) {
            error_log("[GEFEX-FATAL] El Webhook fue invocado, pero el Secreto no ha sido configurado en el Panel de Administrador.");
            return new DataResponse(['error' => 'Server Configuration Error'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        if (!$this->validateSignature($rawPayload, $signatureHeader, $webhookSecret)) {
            error_log("[GEFEX-SECURITY] Intento de acceso denegado: Firma HMAC invalida.");
            return new DataResponse(['error' => 'Invalid Signature'], Http::STATUS_FORBIDDEN);
        }

        // 5. Parseo y sanitización del JSON
        $payload = json_decode($rawPayload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new DataResponse(['error' => 'Invalid JSON format'], Http::STATUS_BAD_REQUEST);
        }

        $eventType = $payload['event_type'] ?? '';
        $userId = $payload['user_id'] ?? '';
        $data = $payload['data'] ?? [];

        if (empty($userId) || empty($eventType)) {
            return new DataResponse(['error' => 'Missing mandatory fields'], Http::STATUS_BAD_REQUEST);
        }

        // 6. Ejecución Nativa de Negocio
        try {
            if ($eventType === 'entitlement.granted') {
                // Validación estricta del entero para evitar inyección en la cuota
                $quotaGb = filter_var($data['grants']['quota_gb'] ?? 50, FILTER_VALIDATE_INT);
                if ($quotaGb === false || $quotaGb <= 0) {
                    return new DataResponse(['error' => 'Invalid quota parameter'], Http::STATUS_BAD_REQUEST);
                }
                $this->injectNativeQuota($userId, "{$quotaGb} GB");
                
            } elseif ($eventType === 'entitlement.revoked') {
                $this->injectNativeQuota($userId, self::DEFAULT_FREE_QUOTA);
            } else {
                return new DataResponse(['error' => 'Unsupported event_type'], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse(['status' => 'success'], Http::STATUS_OK);
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            if (stripos($errorMsg, 'User not found') !== false) {
                error_log("[GEFEX-WARN] Aprovisionamiento ignorado: El usuario {$userId} no existe en Nextcloud.");
                return new DataResponse(['error' => 'User not provisioned in Nextcloud'], Http::STATUS_NOT_FOUND);
            }

            error_log("[GEFEX-FATAL] Fallo critico en el Kernel de Nextcloud: " . $errorMsg);
            return new DataResponse(['error' => 'Internal Provisioning Error'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

     /**
     * Lee el flujo de entrada directamente de la memoria con un límite estricto.
     */
    private function readSafePayload(): ?string {
        $stream = fopen('php://input', 'r');
        if (!$stream) return null;
        
        $payload = stream_get_contents($stream, self::MAX_PAYLOAD_SIZE + 1);
        fclose($stream);

        if ($payload !== false && strlen($payload) > self::MAX_PAYLOAD_SIZE) {
            error_log("[GEFEX-SECURITY] Ataque DoS mitigado: Payload excedio los 10KB permitidos.");
            return null;
        }
        
        return $payload ?: '';
    }

     /**
     * Valida la firma usando hash_equals para evitar ataques de tiempo.
     */
    private function validateSignature(string $payload, string $signatureHeader, string $secret): bool {
        if (empty($signatureHeader) || empty($secret)) {
            return false;
        }
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signatureHeader);
    }

     /**
     * Interactúa con la API Core de Nextcloud sin usar la consola.
     */
    private function injectNativeQuota(string $userId, string $quotaString): void {
        $user = $this->userManager->get($userId);
        
        if ($user === null) {
            throw new Exception("User not found: {$userId}");
        }
        
        $user->setQuota($quotaString);
        error_log("[GEFEX-SUCCESS] Cuota Nativa Aplicada: UUID {$userId} ahora tiene {$quotaString}");
    }
}