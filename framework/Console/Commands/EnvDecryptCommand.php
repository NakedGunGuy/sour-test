<?php

declare(strict_types=1);

namespace Sauerkraut\Console\Commands;

use Sauerkraut\Config\Env;
use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Signature;

class EnvDecryptCommand extends Command
{
    public function signature(): Signature
    {
        return new Signature(
            name: 'env:decrypt',
            description: 'Decrypt .env.encrypted to .env',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $basePath = $this->app->basePath();
        $encryptedFile = $basePath . '/.env.encrypted';
        $keyFile = $basePath . '/.env.key';

        if (!file_exists($encryptedFile)) {
            $output->error('.env.encrypted not found.');
            return 1;
        }

        if (!file_exists($keyFile)) {
            $output->error('.env.key not found. Set ENV_KEY environment variable or create .env.key file.');
            return 1;
        }

        $key = trim(file_get_contents($keyFile));
        $encrypted = file_get_contents($encryptedFile);
        $decrypted = Env::decrypt($encrypted, $key);

        file_put_contents($basePath . '/.env', $decrypted);

        $output->success('Decrypted .env.encrypted → .env');

        return 0;
    }
}
