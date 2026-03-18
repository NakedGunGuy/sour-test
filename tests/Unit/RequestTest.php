<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Request;

class RequestTest extends TestCase
{
    public function testCapturesMethodAndPath(): void
    {
        $request = new Request('GET', '/users');

        $this->assertSame('GET', $request->method());
        $this->assertSame('/users', $request->path());
    }

    public function testMethodIsUppercased(): void
    {
        $request = new Request('post', '/users');

        $this->assertSame('POST', $request->method());
    }

    public function testPathNormalizedWithLeadingSlash(): void
    {
        $request = new Request('GET', 'users');

        $this->assertSame('/users', $request->path());
    }

    public function testQueryParameters(): void
    {
        $request = new Request('GET', '/', query: ['page' => '2', 'sort' => 'name']);

        $this->assertSame('2', $request->query('page'));
        $this->assertSame('name', $request->query('sort'));
        $this->assertNull($request->query('missing'));
        $this->assertSame('default', $request->query('missing', 'default'));
    }

    public function testPostData(): void
    {
        $request = new Request('POST', '/', post: ['name' => 'John']);

        $this->assertSame('John', $request->post('name'));
        $this->assertNull($request->post('missing'));
    }

    public function testInputMergesQueryAndPost(): void
    {
        $request = new Request('POST', '/', query: ['page' => '1'], post: ['name' => 'John']);

        $this->assertSame('1', $request->input('page'));
        $this->assertSame('John', $request->input('name'));
    }

    public function testInputReturnsAllWhenNoKey(): void
    {
        $request = new Request('POST', '/', query: ['a' => '1'], post: ['b' => '2']);
        $all = $request->input();

        $this->assertSame('1', $all['a']);
        $this->assertSame('2', $all['b']);
    }

    public function testHeaders(): void
    {
        $request = new Request('GET', '/', server: [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_X_CUSTOM' => 'value',
        ]);

        $this->assertSame('text/html', $request->header('accept'));
        $this->assertSame('value', $request->header('x-custom'));
        $this->assertNull($request->header('missing'));
    }

    public function testBearerToken(): void
    {
        $request = new Request('GET', '/', server: [
            'HTTP_AUTHORIZATION' => 'Bearer abc123',
        ]);

        $this->assertSame('abc123', $request->bearerToken());
    }

    public function testBearerTokenReturnsNullWithoutBearer(): void
    {
        $request = new Request('GET', '/', server: [
            'HTTP_AUTHORIZATION' => 'Basic abc123',
        ]);

        $this->assertNull($request->bearerToken());
    }

    public function testBearerTokenReturnsNullWithNoAuth(): void
    {
        $request = new Request('GET', '/');

        $this->assertNull($request->bearerToken());
    }

    public function testJsonDetection(): void
    {
        $request = new Request('POST', '/', server: [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->assertTrue($request->isJson());
    }

    public function testExpectsJson(): void
    {
        $request = new Request('GET', '/', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertTrue($request->expectsJson());
    }

    public function testJsonParsing(): void
    {
        $request = new Request('POST', '/', server: [
            'CONTENT_TYPE' => 'application/json',
        ], body: '{"name":"John"}');

        $this->assertSame(['name' => 'John'], $request->json());
    }

    public function testInvalidJsonReturnsEmptyArray(): void
    {
        $request = new Request('POST', '/', server: [
            'CONTENT_TYPE' => 'application/json',
        ], body: 'not json');

        $this->assertSame([], $request->json());
    }

    public function testHasValidJson(): void
    {
        $valid = new Request('POST', '/', server: [
            'CONTENT_TYPE' => 'application/json',
        ], body: '{"ok":true}');

        $invalid = new Request('POST', '/', server: [
            'CONTENT_TYPE' => 'application/json',
        ], body: '{broken');

        $this->assertTrue($valid->hasValidJson());
        $this->assertFalse($invalid->hasValidJson());
    }

    public function testRouteParams(): void
    {
        $request = new Request('GET', '/users/42');
        $request->setRouteParams(['id' => '42']);

        $this->assertSame('42', $request->param('id'));
        $this->assertNull($request->param('missing'));
        $this->assertSame(['id' => '42'], $request->params());
    }

    public function testIpAddress(): void
    {
        $request = new Request('GET', '/', server: ['REMOTE_ADDR' => '192.168.1.1']);

        $this->assertSame('192.168.1.1', $request->ip());
    }

    public function testIpFallsBackToLocalhost(): void
    {
        $request = new Request('GET', '/');

        $this->assertSame('127.0.0.1', $request->ip());
    }

    public function testCookies(): void
    {
        $request = new Request('GET', '/', cookies: ['session' => 'abc123']);

        $this->assertSame('abc123', $request->cookie('session'));
        $this->assertNull($request->cookie('missing'));
    }
}
