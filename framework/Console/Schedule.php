<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

use Cron\CronExpression;

readonly class Schedule
{
    public const string EVERY_MINUTE = '* * * * *';
    public const string EVERY_FIVE_MINUTES = '*/5 * * * *';
    public const string EVERY_FIFTEEN_MINUTES = '*/15 * * * *';
    public const string EVERY_THIRTY_MINUTES = '*/30 * * * *';
    public const string HOURLY = '0 * * * *';
    public const string DAILY = '0 0 * * *';
    public const string DAILY_AT_3AM = '0 3 * * *';
    public const string WEEKLY = '0 0 * * 0';
    public const string MONTHLY = '0 0 1 * *';

    private CronExpression $cron;

    public function __construct(
        public string $expression,
        public bool $preventOverlapping = false,
        public int $lockExpiresAfter = 1440,
        public ?string $description = null,
    ) {
        $this->cron = new CronExpression($this->expression);
    }

    public function isDue(): bool
    {
        return $this->cron->isDue();
    }

    public function nextRunDate(): \DateTimeInterface
    {
        return $this->cron->getNextRunDate();
    }

    public function previousRunDate(): \DateTimeInterface
    {
        return $this->cron->getPreviousRunDate();
    }
}
