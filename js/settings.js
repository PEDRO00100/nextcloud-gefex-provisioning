document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('gefex_save_secret');
    const inputField = document.getElementById('gefex_webhook_secret');
    const statusSpan = document.getElementById('gefex_secret_status');

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const secret = inputField.value;

            if (secret.trim() === '') {
                statusSpan.textContent = 'El secreto no puede estar vacío.';
                statusSpan.style.color = '#e9322d';
                return;
            }

            saveBtn.disabled = true;
            statusSpan.textContent = 'Guardando en bóveda...';
            statusSpan.style.color = '#333';

            const url = OC.generateUrl('/apps/gefex_provisioning/settings/secret');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken
                },
                body: JSON.stringify({ secret: secret })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Fallo en la comunicación con el servidor.');
                    }
                    return response.json();
                })
                .then(data => {
                    // Éxito: Mostrar mensaje y limpiar el campo (Write-Only)
                    statusSpan.textContent = '¡Secreto blindado y guardado con éxito!';
                    statusSpan.style.color = '#4ca64c';

                    inputField.value = '';
                    inputField.placeholder = '•••••••••••••••• (Guardado)';
                })
                .catch(error => {
                    // Error: Mostrar mensaje de fallo
                    console.error('Gefex Security Error:', error);
                    statusSpan.textContent = 'Error de comunicación con el Kernel.';
                    statusSpan.style.color = '#e9322d';
                })
                .finally(() => {
                    // Restaurar el botón y limpiar el mensaje después de 4 segundos
                    saveBtn.disabled = false;
                    setTimeout(() => { statusSpan.textContent = ''; }, 4000);
                });
        });
    }
});