<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

use Sauerkraut\Request;
use Sauerkraut\Response;

interface Middleware
{
    public function handle(Request $request, \Closure $next): Response;
}
