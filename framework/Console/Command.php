<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

use Sauerkraut\App;

abstract class Command
{
    protected App $app;

    abstract public function signature(): Signature;

    abstract public function handle(Input $input, Output $output): int;

    public function setApp(App $app): void
    {
        $this->app = $app;
    }
}
