<?php
declare(strict_types=1);

 /**
 * Registro de rutas para Nextcloud 33.
 * El prefijo automático de esta app será: /apps/gefex_provisioning/
 */

return [
    'routes' => [
        // RUTA 1: Webhook público (Para la API de Java)
        // Apunta a WebhookController -> handleRequest()
        ['name' => 'webhook#handleRequest', 'url' => '/webhook', 'verb' => 'POST'],

        // Apunta a SettingsController -> setSecret()
        ['name' => 'settings#setSecret', 'url' => '/settings/secret', 'verb' => 'POST'],
    ]
];