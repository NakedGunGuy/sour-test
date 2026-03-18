<?php

declare(strict_types=1);

namespace Sauerkraut\View;

use Sauerkraut\App;

class AppContext
{
    private static ?App $app = null;

    public static function set(App $app): void
    {
        self::$app = $app;
    }

    public static function get(): App
    {
        return self::$app ?? throw new \RuntimeException('AppContext has not been initialized.');
    }
}
