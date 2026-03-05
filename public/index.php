<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = \Sauerkraut\App::boot(dirname(__DIR__));
$app->handleRequest();
