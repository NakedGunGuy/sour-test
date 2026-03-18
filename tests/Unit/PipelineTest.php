<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Http\Middleware;
use Sauerkraut\Pipeline;
use Sauerkraut\Request;
use Sauerkraut\Response;

class AddHeaderMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        return $response->withHeader('X-Middleware', 'applied');
    }
}

class BlockMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        return Response::html('blocked', 403);
    }
}

class PipelineTest extends TestCase
{
    public function testPassesThroughWithNoMiddleware(): void
    {
        $request = new Request('GET', '/');
        $pipeline = new Pipeline();

        $response = $pipeline->send($request)
            ->through([])
            ->then(fn () => Response::html('ok'));

        $this->assertSame('ok', $response->body());
    }

    public function testMiddlewareCanModifyResponse(): void
    {
        $request = new Request('GET', '/');
        $pipeline = new Pipeline();

        $response = $pipeline->send($request)
            ->through([AddHeaderMiddleware::class])
            ->then(fn () => Response::html('ok'));

        $this->assertSame('ok', $response->body());
    }

    public function testMiddlewareCanBlockRequest(): void
    {
        $request = new Request('GET', '/');
        $pipeline = new Pipeline();

        $response = $pipeline->send($request)
            ->through([BlockMiddleware::class])
            ->then(fn () => Response::html('ok'));

        $this->assertSame(403, $response->status());
        $this->assertSame('blocked', $response->body());
    }

    public function testMiddlewareExecutesInOrder(): void
    {
        $request = new Request('GET', '/');
        $pipeline = new Pipeline();

        $response = $pipeline->send($request)
            ->through([BlockMiddleware::class, AddHeaderMiddleware::class])
            ->then(fn () => Response::html('ok'));

        // BlockMiddleware runs first, so AddHeaderMiddleware never executes
        $this->assertSame(403, $response->status());
    }
}
