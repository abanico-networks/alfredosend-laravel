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
