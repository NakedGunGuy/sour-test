<?php

declare(strict_types=1);

namespace App\Controllers;

use Sauerkraut\Response;
use Sauerkraut\View\View;

abstract class Controller
{
    protected function view(string $page, array $data = [], ?string $layout = null): Response
    {
        return Response::html(View::render($page, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}
