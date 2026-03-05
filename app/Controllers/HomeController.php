<?php

declare(strict_types=1);

namespace App\Controllers;

use Sauerkraut\Request;
use Sauerkraut\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Sauerkraut',
        ]);
    }
}
