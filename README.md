# Gefex Provisioning para Nextcloud

Gefex Provisioning es una aplicación de seguridad y administración para Nextcloud que automatiza el aprovisionamiento de cuotas de almacenamiento de usuarios a través de un Webhook seguro. Diseñado para integrarse con pasarelas de pago, CRMs o sistemas de gestión empresariales externos.

## 🚀 Características

* **Seguridad Zero-Trust:** Todas las peticiones deben estar firmadas criptográficamente mediante HMAC-SHA256.
* **Bóveda de Secreto (Write-Only):** El secreto del webhook se almacena de forma segura en la base de datos y nunca se expone en la interfaz gráfica del administrador.
* **Prevención DoS:** Las peticiones entrantes están estrictamente limitadas a un máximo de 10 KB.
* **Modificación en Tiempo Real:** Interacciona directamente con la API Core de Nextcloud para aplicar los cambios de cuota instantáneamente, sin necesidad de comandos de consola.

## ⚙️ Requisitos

* Nextcloud versión 32 o 33.
* PHP 8.0+

## 🛠️ Instalación y Configuración

1. Clona o descarga este repositorio dentro de la carpeta `apps/` de tu servidor Nextcloud:
   `git clone https://github.com/tu-usuario/nextcloud-gefex-provisioning.git gefex_provisioning`
2. Activa la aplicación mediante la consola de Nextcloud:
   `php occ app:enable gefex_provisioning`
3. Inicia sesión en Nextcloud como Administrador.
4. Dirígete a **Ajustes > Administración > Servidor**.
5. En la sección "Gefex Provisioning", introduce tu **Webhook Secret** y guárdalo. ¡Guarda esta clave en un lugar seguro, tu sistema externo la necesitará!

---

## 📖 Documentación de la API (Webhook)

Tu sistema externo debe enviar peticiones `POST` al endpoint del Webhook cada vez que desees actualizar la cuota de un usuario.

### Endpoint
`POST https://<tu-dominio-nextcloud.com>/apps/gefex_provisioning/webhook`

### Cabeceras HTTP Requeridas (Headers)
Para que el servidor acepte la petición, debes incluir la firma digital de la misma.

* `Content-Type: application/json`
* `X-GEFEX-SIGNATURE: <firma_hmac_sha256_en_hexadecimal>`

*(La firma se genera calculando el HMAC-SHA256 del cuerpo (payload) exacto (RAW) de la petición, utilizando el Secreto configurado en el panel de administrador).*

### Formato de Datos (JSON Payload)

La aplicación espera un objeto JSON con la siguiente estructura:

```json
{
  "event_type": "entitlement.granted",
  "user_id": "nombre_de_usuario_en_nextcloud",
  "data": {
    "grants": {
      "quota_gb": 50
    }
  }
}
```

#### Eventos Soportados (`event_type`)

1. **`entitlement.granted`**: Asigna una cuota específica al usuario.
   * Requiere el campo `data.grants.quota_gb` (Debe ser un número entero mayor a 0).
   * *Ejemplo de uso:* Cuando un usuario compra o renueva una suscripción.

2. **`entitlement.revoked`**: Revoca la cuota premium y devuelve al usuario a la cuota gratuita por defecto (1 GB).
   * No requiere el objeto `data`.
   * *Ejemplo de uso:* Cuando finaliza la suscripción de un usuario o se cancela el servicio.

---

## 💻 Ejemplos de Integración

### Ejemplo en Node.js (JavaScript)

```javascript
const crypto = require('crypto');

const secret = 'TU_SECRETO_GUARDADO_EN_NEXTCLOUD';
const payload = JSON.stringify({
    event_type: 'entitlement.granted',
    user_id: 'admin',
    data: {
        grants: {
            quota_gb: 100
        }
    }
});

// Generar la firma HMAC-SHA256
const signature = crypto.createHmac('sha256', secret)
                        .update(payload)
                        .digest('hex');

// Realizar la petición
fetch('https://<tu-dominio-nextcloud.com>/apps/gefex_provisioning/webhook', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-GEFEX-SIGNATURE': signature
    },
    body: payload
})
.then(response => response.json())
.then(data => console.log(data));
```

### Ejemplo en PHP

```php
$secret = 'TU_SECRETO_GUARDADO_EN_NEXTCLOUD';
$payload = json_encode([
    'event_type' => 'entitlement.revoked',
    'user_id' => 'admin'
]);

// Generar la firma HMAC-SHA256
$signature = hash_hmac('sha256', $payload, $secret);

$ch = curl_init('https://<tu-dominio-nextcloud.com>/apps/gefex_provisioning/webhook');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-GEFEX-SIGNATURE: ' . $signature
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
```

## 📝 Códigos de Respuesta HTTP

* `200 OK`: La cuota fue actualizada correctamente `{"status": "success"}`.
* `400 Bad Request`: Faltan campos obligatorios, el formato JSON es inválido o el valor de la cuota no es un número entero.
* `403 Forbidden`: La firma de la cabecera `X-GEFEX-SIGNATURE` es inválida o no coincide.
* `404 Not Found`: El `user_id` enviado no existe en la base de datos de Nextcloud.
* `413 Payload Too Large`: El JSON enviado supera el límite de seguridad de 10 KB.
* `500 Internal Server Error`: El secreto no se ha configurado en el panel de administrador u ocurrió un error crítico.

## 📄 Licencia
Este proyecto está licenciado bajo la licencia AGPL.
