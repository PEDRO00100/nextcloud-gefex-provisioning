# Gefex Provisioning for Nextcloud

> **Language / Idioma**
>
> * [🇪🇸 Español](#spanish-section)
> * [🇺🇸 English](#english-section)

---

## Spanish Section

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

### Endpoint (Spanish)

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

---

## English Section

Gefex Provisioning is a security and administration application for Nextcloud that automates the provisioning of user storage quotas via a secure Webhook. It is specifically designed to integrate with external payment gateways, CRMs, or enterprise management systems.

## 🚀 Features

* **Zero-Trust Security:** Every request must be cryptographically signed using HMAC-SHA256.
* **Write-Only Secret Vault:** The webhook secret is securely stored in the database and is never exposed in the administrator's graphical interface.
* **DoS Prevention:** Incoming requests are strictly limited to a maximum payload size of 10 KB.
* **Real-Time Modification:** Interacts directly with the Nextcloud Core API to apply quota changes instantly, with no console commands required.

## ⚙️ Requirements

* Nextcloud version 32 or 33.
* PHP 8.0+

## 🛠️ Installation & Configuration

1. Clone or download this repository into your Nextcloud server's `apps/` folder:
    `git clone https://github.com/PEDRO00100/nextcloud-gefex-provisioning.git gefex_provisioning`
2. Enable the application via the Nextcloud console:
    `php occ app:enable gefex_provisioning`
3. Log in to Nextcloud as an **Administrator**.
4. Navigate to **Settings > Administration > Server**.
5. In the **"Gefex Provisioning"** section, enter your **Webhook Secret** and save it. Keep this key in a secure location; your external system will require it to sign requests!

---

## 📖 API Documentation (Webhook)

Your external system must send `POST` requests to the Webhook endpoint whenever you wish to update a user's quota.

### Endpoint (English)

`POST https://<your-nextcloud-domain.com>/apps/gefex_provisioning/webhook`

### Required HTTP Headers

To ensure the server accepts the request, you must include a digital signature.

* `Content-Type: application/json`
* `X-GEFEX-SIGNATURE: <hmac_sha256_hexadecimal_signature>`

*(The signature is generated by calculating the HMAC-SHA256 of the exact RAW request payload using the Secret configured in the admin panel).*

### Data Format (JSON Payload)

The application expects a JSON object with the following structure:

```json
{
  "event_type": "entitlement.granted",
  "user_id": "nextcloud_username",
  "data": {
    "grants": {
      "quota_gb": 50
    }
  }
}
```

#### Supported Events (`event_type`)

1. **`entitlement.granted`**: Assigns a specific quota to the user.
    * Requires the `data.grants.quota_gb` field (Must be an integer greater than 0).
    * *Use Case:* When a user purchases or renews a subscription.

2. **`entitlement.revoked`**: Revokes the premium quota and resets the user to the default free quota (1 GB).
    * Does not require the `data` object.
    * *Use Case:* When a user's subscription expires or the service is canceled.

---

## 💻 Integration Examples

### Node.js (JavaScript) Example

```javascript
const crypto = require('crypto');

const secret = 'YOUR_SECRET_SAVED_IN_NEXTCLOUD';
const payload = JSON.stringify({
    event_type: 'entitlement.granted',
    user_id: 'admin',
    data: {
        grants: {
            quota_gb: 100
        }
    }
});

// Generate HMAC-SHA256 signature
const signature = crypto.createHmac('sha256', secret)
                        .update(payload)
                        .digest('hex');

// Perform the request
fetch('https://<your-nextcloud-domain.com>/apps/gefex_provisioning/webhook', {
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

### PHP Example

```php
<?php
$secret = 'YOUR_SECRET_SAVED_IN_NEXTCLOUD';
$payload = json_encode([
    'event_type' => 'entitlement.revoked',
    'user_id' => 'admin'
]);

// Generate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $payload, $secret);

$ch = curl_init('https://<your-nextcloud-domain.com>/apps/gefex_provisioning/webhook');
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

## 📝 HTTP Response Codes

* `200 OK`: Quota successfully updated `{"status": "success"}`.
* `400 Bad Request`: Missing required fields, invalid JSON format, or quota value is not an integer.
* `403 Forbidden`: The signature in the `X-GEFEX-SIGNATURE` header is invalid or mismatched.
* `404 Not Found`: The provided `user_id` does not exist in the Nextcloud database.
* `413 Payload Too Large`: The sent JSON exceeds the 10 KB security limit.
* `500 Internal Server Error`: The secret has not been configured in the admin panel, or a critical error occurred.

## 📄 License

This project is licensed under the **AGPL-3.0-only** license.
