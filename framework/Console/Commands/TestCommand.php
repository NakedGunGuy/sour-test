<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class TestCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'test',
            description: 'Run the test suite',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $phpunit = $this->app->basePath('vendor/bin/phpunit');

        passthru("php {$phpunit}", $exitCode);

        return $exitCode;
    }
}
