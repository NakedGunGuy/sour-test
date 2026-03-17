<?php

declare(strict_types=1);

namespace App\Commands;

use Sauerkraut\Console\Command;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Output;
use Sauerkraut\Console\Schedulable;
use Sauerkraut\Console\Schedule;
use Sauerkraut\Console\Signature;

class CleanLogsCommand extends Command implements Schedulable
{
    private const int DAYS_TO_KEEP = 30;

    public function signature(): Signature
    {
        return new Signature(
            name: 'logs:clean',
            description: 'Remove log files older than 30 days',
        );
    }

    public function schedule(): Schedule
    {
        return new Schedule(
            expression: Schedule::DAILY_AT_3AM,
            preventOverlapping: true,
            description: 'Clean old log files daily at 3 AM',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        $logDir = $this->app->basePath('storage/logs');

        if (!is_dir($logDir)) {
            $output->line('  No log directory found.');
            return 0;
        }

        $cutoff = time() - (self::DAYS_TO_KEEP * 86400);
        $deleted = 0;

        foreach (glob("{$logDir}/*.log") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        $output->success("  Cleaned {$deleted} old log file(s).");

        return 0;
    }
}
