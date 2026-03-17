<?php
declare(strict_types=1);

namespace OCA\GefexProvisioning\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;

 /**
 * Controlador para la gestión de ajustes de la aplicación.
 * Asegurado por defecto: Solo accesible por Administradores logueados y con Token CSRF válido.
 */
class SettingsController extends Controller {
    private IConfig $config;

    public function __construct(string $appName, IRequest $request, IConfig $config) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    /**
     * Guarda el secreto de forma segura en la base de datos (oc_appconfig).
     * * @param string $secret El valor enviado desde el frontend (Extraído automáticamente del JSON)
     * @return DataResponse
     */
    public function setSecret(string $secret): DataResponse {
        // 1. Sanitización básica
        $trimmedSecret = trim($secret);
        
        // 2. Validación de integridad
        if ($trimmedSecret === '') {
            return new DataResponse(
                ['message' => 'El secreto no puede estar vacío.'], 
                Http::STATUS_BAD_REQUEST
            );
        }

        // 3. Guardado nativo y blindado en la base de datos
        $this->config->setAppValue($this->appName, 'webhook_secret', $trimmedSecret);

        // 4. Respuesta de éxito al frontend
        return new DataResponse(
            ['message' => 'Secreto guardado con éxito.'], 
            Http::STATUS_OK
        );
    }
}