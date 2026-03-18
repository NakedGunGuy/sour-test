<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Response;

class ResponseTest extends TestCase
{
    public function testHtmlResponse(): void
    {
        $response = Response::html('<h1>Hello</h1>');

        $this->assertSame('<h1>Hello</h1>', $response->body());
        $this->assertSame(200, $response->status());
    }

    public function testJsonResponse(): void
    {
        $response = Response::json(['name' => 'John']);

        $this->assertSame('{"name":"John"}', $response->body());
        $this->assertSame(200, $response->status());
    }

    public function testCustomStatusCode(): void
    {
        $response = Response::html('Not Found', 404);

        $this->assertSame(404, $response->status());
    }

    public function testWithStatusReturnsNewInstance(): void
    {
        $original = Response::html('test');
        $modified = $original->withStatus(404);

        $this->assertSame(200, $original->status());
        $this->assertSame(404, $modified->status());
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $original = Response::html('test');
        $modified = $original->withHeader('X-Custom', 'value');

        $this->assertNotSame($original, $modified);
    }

    public function testHeaderInjectionPrevented(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Response::html('test')->withHeader('X-Bad', "value\r\nInjected: header");
    }

    public function testEmptyResponse(): void
    {
        $response = Response::empty();

        $this->assertSame('', $response->body());
        $this->assertSame(204, $response->status());
    }
}
