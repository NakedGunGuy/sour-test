<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Config\Env;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class EnvKeyCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'env:key',
            description: 'Generate a new encryption key',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $keyFile = $this->app->basePath('.env.key');

        if (file_exists($keyFile)) {
            $output->error('.env.key already exists. Delete it first to generate a new one.');
            return 1;
        }

        $key = Env::generateKey();
        file_put_contents($keyFile, $key);

        $output->success('Encryption key generated: .env.key');
        $output->warn('Keep this file secret. Never commit it to git.');

        return 0;
    }
}
