<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Config\Env;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class EnvEncryptCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'env:encrypt',
            description: 'Encrypt .env to .env.encrypted',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $basePath = $this->app->basePath();
        $envFile = $basePath . '/.env';
        $keyFile = $basePath . '/.env.key';

        if (!file_exists($envFile)) {
            $output->error('.env file not found.');
            return 1;
        }

        if (!file_exists($keyFile)) {
            $output->error('.env.key not found. Run: php sauerkraut env:key');
            return 1;
        }

        $key = trim(file_get_contents($keyFile));
        $content = file_get_contents($envFile);
        $encrypted = Env::encrypt($content, $key);

        file_put_contents($basePath . '/.env.encrypted', $encrypted);

        $output->success('Encrypted .env → .env.encrypted');
        $output->info('You can safely commit .env.encrypted to git.');

        return 0;
    }
}
