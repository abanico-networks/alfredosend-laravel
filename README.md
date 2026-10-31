# Cliente Laravel de Alfredo

Instálalo con Composer cuando se publique como `alfredosend/laravel`. Usa el cliente HTTP que Laravel ya incluye.

```php
use Alfredo\AlfredoClient;

$client = new AlfredoClient(
    apiKey: config('services.alfredo.key'),
    clientId: config('services.alfredo.client_id'),
);

$result = $client->sendTransactional(config('services.alfredo.welcome_template_id'), [
    'to' => [['email' => $user->email, 'name' => $user->name]],
    'data' => ['name' => $user->name],
]);
```

Alfredo genera la idempotencia del envío. Pasa el tercer argumento solo si el mismo proceso puede reintentarse y quieres reutilizar una misma operación.

## Adjuntos

Añade `attachments` al mismo payload. El contenido debe enviarse en base64 en `content_base64`, no como una ruta local ni como un fichero multipart.

```php
$result = $client->sendTransactional(config('services.alfredo.invoice_template_id'), [
    'to' => [['email' => $user->email, 'name' => $user->name]],
    'data' => ['name' => $user->name],
    'attachments' => [[
        'filename' => 'factura.pdf',
        'content_type' => 'application/pdf',
        'content_base64' => base64_encode($pdf),
    ]],
]);
```

En este ejemplo, `$pdf` contiene los bytes del PDF generado por tu aplicación.

- Se admiten hasta 10 adjuntos y 10 MiB (10 × 1024 × 1024 bytes) en total antes de codificar en base64.
- `filename` y `content_base64` son obligatorios; el contenido no puede estar vacío.
- `content_type` es opcional. Si lo omites, la API detecta el tipo MIME.
- Todos los destinatarios del envío reciben los mismos adjuntos.
- La API rechaza nombres inválidos, base64 inválido, tamaños excesivos y extensiones no admitidas por Amazon SES (por ejemplo `.exe`, `.bat`, `.js` o `.vbs`) con HTTP 422.

El cliente transmite el payload sin modificarlo y deja la validación a la API. Los errores HTTP se propagan como `Illuminate\Http\Client\RequestException`; puedes consultar los detalles de validación con `$exception->response->json('errors')`.

## Pruebas

```sh
composer install
composer test
```

Las pruebas usan `Http::fake()` y no realizan llamadas a la API. Comprueban el envío con y sin adjuntos, la cabecera de idempotencia y la propagación de errores de validación.
