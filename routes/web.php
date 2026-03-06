<?php

use App\Controllers\HomeController;
use Sauerkraut\Response;
use Sauerkraut\View\Component;

/** @var \Sauerkraut\Router $router */

$router->name('home')->get('/', [HomeController::class, 'index']);

$router->get('/htmx/alert', function () {
    return Response::html(\Sauerkraut\View\View::partial('partials/htmx-alert'));
});

$router->get('/htmx/row', function (\Sauerkraut\Request $request) {
    $id = $request->query('id');

    return Response::html(\Sauerkraut\View\View::partial('partials/htmx-row', [
        'id' => $id,
        'name' => $request->query('name'),
        'role' => $request->query('role'),
        'target' => "#user-{$id}",
    ]));
});

$router->get('/js/components.js', function (\Sauerkraut\Request $request) {
    $names = $request->query('c', '');

    if (empty($names)) {
        return Response::empty(404);
    }

    return Response::js(Component::bundleAssets('js', $names))
        ->withHeader('Cache-Control', 'public, max-age=86400');
});

$router->get('/css/components.css', function (\Sauerkraut\Request $request) {
    $names = $request->query('c', '');

    if (empty($names)) {
        return Response::empty(404);
    }

    return Response::css(Component::bundleAssets('css', $names))
        ->withHeader('Cache-Control', 'public, max-age=86400');
});
