<?php

declare(strict_types=1);

namespace Alfredo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class AlfredoClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $clientId,
        private readonly string $baseUrl = 'https://api.alfredosend.com',
    ) {}

    public function sendTransactional(string $templateId, array $payload, ?string $idempotencyKey = null): array
    {
        $request = $this->request();
        if ($idempotencyKey) $request = $request->withHeader('Idempotency-Key', $idempotencyKey);

        return $request->post("/v1/clients/{$this->clientId}/transactional/templates/{$templateId}/send", $payload)
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($this->apiKey);
    }
}
