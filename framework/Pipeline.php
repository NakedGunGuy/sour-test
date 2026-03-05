<?php

declare(strict_types=1);

namespace Sauerkraut;

class Pipeline
{
    private Request $request;
    private array $pipes = [];

    public function send(Request $request): static
    {
        $this->request = $request;
        return $this;
    }

    public function through(array $middleware): static
    {
        $this->pipes = $middleware;
        return $this;
    }

    public function then(\Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            function (\Closure $next, string $middlewareClass) {
                return function (Request $request) use ($next, $middlewareClass) {
                    $middleware = new $middlewareClass();
                    return $middleware->handle($request, $next);
                };
            },
            $destination,
        );

        return $pipeline($this->request);
    }
}
