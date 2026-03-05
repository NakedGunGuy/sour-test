<?php

use App\Controllers\HomeController;
use Sauerkraut\Response;
use Sauerkraut\View\Component;

/** @var \Sauerkraut\Router $router */

$router->name('home')->get('/', [HomeController::class, 'index']);

$router->get('/htmx/alert', function () {
    return Response::html(\Sauerkraut\View\View::partial('partials/htmx-alert'));
});

$router->get('/js/components.js', function (\Sauerkraut\Request $request) {
    $names = $request->query('c', '');

    if (empty($names)) {
        return Response::empty(404);
    }

    $js = '';

    foreach (explode(',', $names) as $slug) {
        $name = str_replace('.', '/', $slug);
        $content = Component::getScriptContent($name);

        if ($content !== null) {
            $js .= "/* {$name} */\n{$content}\n";
        }
    }

    return Response::js($js)
        ->withHeader('Cache-Control', 'public, max-age=86400');
});

$router->get('/css/components.css', function (\Sauerkraut\Request $request) {
    $names = $request->query('c', '');

    if (empty($names)) {
        return Response::empty(404);
    }

    $css = '';

    foreach (explode(',', $names) as $slug) {
        $name = str_replace('.', '/', $slug);
        $content = Component::getStylesheetContent($name);

        if ($content !== null) {
            $css .= "/* {$name} */\n{$content}\n";
        }
    }

    return Response::css($css)
        ->withHeader('Cache-Control', 'public, max-age=86400');
});
