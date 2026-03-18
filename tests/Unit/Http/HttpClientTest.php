<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Http\HttpClient;
use Sauerkraut\Http\HttpResponse;

class HttpClientTest extends TestCase
{
    public function testWithHeadersReturnsNewInstance(): void
    {
        $client = new HttpClient();
        $modified = $client->withHeaders(['Authorization' => 'Bearer test']);

        $this->assertNotSame($client, $modified);
    }

    public function testWithTimeoutReturnsNewInstance(): void
    {
        $client = new HttpClient();
        $modified = $client->withTimeout(60);

        $this->assertNotSame($client, $modified);
    }
}

class HttpResponseTest extends TestCase
{
    public function testStatusAccessor(): void
    {
        $response = new HttpResponse(200, 'OK', []);

        $this->assertSame(200, $response->status());
    }

    public function testBodyAccessor(): void
    {
        $response = new HttpResponse(200, 'Hello', []);

        $this->assertSame('Hello', $response->body());
    }

    public function testJsonDecoding(): void
    {
        $response = new HttpResponse(200, '{"name":"John"}', []);

        $this->assertSame(['name' => 'John'], $response->json());
    }

    public function testHeaderLookup(): void
    {
        $response = new HttpResponse(200, '', ['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $response->header('content-type'));
        $this->assertNull($response->header('missing'));
    }

    public function testIsOk(): void
    {
        $this->assertTrue((new HttpResponse(200, '', []))->isOk());
        $this->assertTrue((new HttpResponse(201, '', []))->isOk());
        $this->assertFalse((new HttpResponse(404, '', []))->isOk());
    }

    public function testIsRedirect(): void
    {
        $this->assertTrue((new HttpResponse(301, '', []))->isRedirect());
        $this->assertTrue((new HttpResponse(302, '', []))->isRedirect());
        $this->assertFalse((new HttpResponse(200, '', []))->isRedirect());
    }

    public function testIsClientError(): void
    {
        $this->assertTrue((new HttpResponse(404, '', []))->isClientError());
        $this->assertTrue((new HttpResponse(422, '', []))->isClientError());
        $this->assertFalse((new HttpResponse(500, '', []))->isClientError());
    }

    public function testIsServerError(): void
    {
        $this->assertTrue((new HttpResponse(500, '', []))->isServerError());
        $this->assertTrue((new HttpResponse(503, '', []))->isServerError());
        $this->assertFalse((new HttpResponse(404, '', []))->isServerError());
    }
}
