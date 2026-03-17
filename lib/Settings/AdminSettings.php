<?php
declare(strict_types=1);

namespace OCA\GefexProvisioning\Settings;

use OCP\Settings\ISettings;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;

 /**
 * Backend de Configuración Administrativa para Gefex Provisioning.
 * Optimizado para Nextcloud 33+ (Uso estricto de TemplateResponse)
 */
class AdminSettings implements ISettings {
    private IConfig $config;

    public function __construct(IConfig $config) {
        $this->config = $config;
    }

     /**
     * Renderiza la plantilla HTML para el panel de administración.
     * En Nextcloud moderno, DEBE devolver un TemplateResponse.
     */
    public function getForm(): TemplateResponse {
        // SEGURIDAD WRITE-ONLY: Extraemos el secreto de la base de datos.
        $secret = $this->config->getAppValue('gefex_provisioning', 'webhook_secret', '');
        
        // SOLO enviamos un booleano (true/false) al frontend. El secreto muere aquí y no viaja a la UI.
        $hasSecret = !empty($secret);

        // Creamos la respuesta de la plantilla. 
        // El 4to parámetro 'blank' es crucial: le dice a Nextcloud que solo renderice 
        // este pedazo de HTML sin cabeceras y lo incruste en la página de Configuración Global.
        $response = new TemplateResponse(
            'gefex_provisioning', 
            'settings/admin', 
            ['hasSecret' => $hasSecret], 
            'blank'
        );

        return $response;
    }

     /**
     * Define en qué sección del menú lateral aparecerá.
     * 'server' lo coloca en Administración -> Ajustes básicos/Servidor.
     */
    public function getSection(): string {
        return 'server';
    }

     /**
     * Define la prioridad (orden de aparición vertical en la página).
     */
    public function getPriority(): int {
        return 50;
    }
}