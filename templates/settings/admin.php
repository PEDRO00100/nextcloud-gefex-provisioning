<?php 
script('gefex_provisioning', 'settings'); 
//style('gefex_provisioning', 'settings');
?>

<div id="gefex-provisioning-settings" class="section">
    <h2 class="app-name">Gefex Provisioning</h2>
    <p>Configuración del Webhook para aprovisionamiento automático de cuotas.</p>

    <div class="setting-row" style="margin-top: 15px;">
        <label for="gefex_webhook_secret" style="display:block; font-weight: bold; margin-bottom: 5px;">
            Webhook Secret (HMAC-SHA256)
        </label>
        
        <input type="password"
               id="gefex_webhook_secret"
               name="gefex_webhook_secret"
               style="width: 300px; padding: 5px;"
               placeholder="<?php p($_['hasSecret'] ? '•••••••••••••••• (Guardado)' : 'Ingresa tu secreto maestro...'); ?>"
               value=""
               autocomplete="new-password">
               
        <button id="gefex_save_secret" class="button">Guardar Secreto</button>
        <span id="gefex_secret_status" class="msg" style="margin-left: 10px; font-weight: bold;"></span>
    </div>
    
    <p class="info" style="margin-top: 10px; color: #666; font-size: 0.9em;">
        <em>Por seguridad, el secreto es "Write-Only" (Solo escritura). Una vez guardado, no se mostrará de nuevo en pantalla. Si lo pierdes, simplemente ingresa uno nuevo para sobrescribirlo.</em>
    </p>
</div>