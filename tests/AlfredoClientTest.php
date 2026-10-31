<?php

declare(strict_types=1);

namespace Alfredo\Tests;

use Alfredo\AlfredoClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

final class AlfredoClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::swap(new Factory());
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_it_sends_attachments_unchanged_as_json_with_idempotency(): void
    {
        $payload = [
            'to' => [['email' => 'cliente@example.com', 'name' => 'Cliente']],
            'data' => ['name' => 'Cliente'],
            'attachments' => [
                [
                    'filename' => 'factura.pdf',
                    'content_type' => 'application/pdf',
                    'content_base64' => base64_encode("%PDF-1.4\nfactura"),
                ],
                [
                    'filename' => 'nota.txt',
                    'content_base64' => base64_encode('hola'),
                ],
            ],
        ];
        $response = ['messages' => [['message_id' => 'message-123']]];
        Http::fake(fn () => Http::response($response, 202));

        $client = new AlfredoClient('test-api-key', 'client-123', 'https://alfredo.example/');

        $this->assertSame($response, $client->sendTransactional('template-123', $payload, 'invoice-123'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://alfredo.example/v1/clients/client-123/transactional/templates/template-123/send'
            && $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Idempotency-Key', 'invoice-123')
            && $request->data() === $payload
            && json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR) === $payload);
        Http::assertSentCount(1);
    }

    public function test_it_still_sends_without_attachments_or_an_idempotency_key(): void
    {
        $payload = ['to' => [['email' => 'cliente@example.com']]];
        $response = ['messages' => [['message_id' => 'message-123']]];
        Http::fake(fn () => Http::response($response, 202));

        $client = new AlfredoClient('test-api-key', 'client-123');

        $this->assertSame($response, $client->sendTransactional('template-123', $payload));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.alfredosend.com/v1/clients/client-123/transactional/templates/template-123/send'
            && ! $request->hasHeader('Idempotency-Key')
            && $request->data() === $payload);
        Http::assertSentCount(1);
    }

    public function test_it_propagates_attachment_validation_errors(): void
    {
        $payload = [
            'to' => [['email' => 'cliente@example.com']],
            'attachments' => [[
                'filename' => 'factura.pdf',
                'content_base64' => 'invalid-base64!',
            ]],
        ];
        $response = [
            'message' => 'The attachment content must be valid base64.',
            'errors' => ['attachments.0.content_base64' => ['The attachment content must be valid base64.']],
        ];
        Http::fake(fn () => Http::response($response, 422));

        $client = new AlfredoClient('test-api-key', 'client-123');

        try {
            $client->sendTransactional('template-123', $payload);
            $this->fail('Expected the API validation error to throw a RequestException.');
        } catch (RequestException $exception) {
            $this->assertSame(422, $exception->response->status());
            $this->assertSame($response, $exception->response->json());
            $this->assertSame($response['errors'], $exception->response->json('errors'));
        }

        Http::assertSent(fn (Request $request): bool => $request->data() === $payload);
        Http::assertSentCount(1);
    }
}
