<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Router;

class RouterTest extends TestCase
{
    public function testMatchesSimpleRoute(): void
    {
        $router = new Router();
        $router->get('/users', fn () => 'list');

        $result = $router->match('GET', '/users');

        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertSame('GET', $route->method());
        $this->assertEmpty($params);
    }

    public function testMatchesRouteWithParameter(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn () => 'show');

        $result = $router->match('GET', '/users/42');

        $this->assertNotNull($result);
        [$route, $params] = $result;
        $this->assertSame('42', $params['id']);
    }

    public function testReturnsNullForNoMatch(): void
    {
        $router = new Router();
        $router->get('/users', fn () => 'list');

        $this->assertNull($router->match('GET', '/posts'));
    }

    public function testMethodMismatchReturnsNull(): void
    {
        $router = new Router();
        $router->post('/users', fn () => 'store');

        $this->assertNull($router->match('GET', '/users'));
    }

    public function testHasMatchingPathDistinguishes405(): void
    {
        $router = new Router();
        $router->post('/users', fn () => 'store');

        $this->assertNull($router->match('GET', '/users'));
        $this->assertTrue($router->hasMatchingPath('/users'));
    }

    public function testHasMatchingPathReturnsFalseFor404(): void
    {
        $router = new Router();
        $router->get('/users', fn () => 'list');

        $this->assertFalse($router->hasMatchingPath('/posts'));
    }

    public function testNamedRouteUrlGeneration(): void
    {
        $router = new Router();
        $router->name('user.show')->get('/users/{id}', fn () => 'show');

        $url = $router->url('user.show', ['id' => 42]);

        $this->assertSame('/users/42', $url);
    }

    public function testRouteGroupAddsPrefix(): void
    {
        $router = new Router();
        $router->group(['prefix' => 'admin'], function (Router $router) {
            $router->get('/dashboard', fn () => 'dash');
        });

        $result = $router->match('GET', '/admin/dashboard');
        $this->assertNotNull($result);
    }

    public function testRouteGroupAddsMiddleware(): void
    {
        $router = new Router();
        $router->group(['middleware' => ['AuthMiddleware']], function (Router $router) {
            $router->get('/secret', fn () => 'secret');
        });

        [$route] = $router->match('GET', '/secret');
        $this->assertContains('AuthMiddleware', $route->middleware());
    }

    public function testMultipleHttpMethods(): void
    {
        $router = new Router();
        $router->get('/items', fn () => 'list');
        $router->post('/items', fn () => 'store');
        $router->put('/items/{id}', fn () => 'update');
        $router->delete('/items/{id}', fn () => 'delete');

        $this->assertNotNull($router->match('GET', '/items'));
        $this->assertNotNull($router->match('POST', '/items'));
        $this->assertNotNull($router->match('PUT', '/items/1'));
        $this->assertNotNull($router->match('DELETE', '/items/1'));
    }

    public function testTrailingSlashNormalization(): void
    {
        $router = new Router();
        $router->get('/users', fn () => 'list');

        $this->assertNotNull($router->match('GET', '/users/'));
        $this->assertNotNull($router->match('GET', '/users'));
    }
}
