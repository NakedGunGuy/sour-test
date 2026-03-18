<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Console\Schedule;

class ScheduleTest extends TestCase
{
    public function testScheduleProperties(): void
    {
        $schedule = new Schedule(
            expression: '*/5 * * * *',
            preventOverlapping: true,
            lockExpiresAfter: 60,
            description: 'Every 5 minutes',
        );

        $this->assertSame('*/5 * * * *', $schedule->expression);
        $this->assertTrue($schedule->preventOverlapping);
        $this->assertSame(60, $schedule->lockExpiresAfter);
        $this->assertSame('Every 5 minutes', $schedule->description);
    }

    public function testScheduleConstants(): void
    {
        $this->assertSame('* * * * *', Schedule::EVERY_MINUTE);
        $this->assertSame('*/5 * * * *', Schedule::EVERY_FIVE_MINUTES);
        $this->assertSame('0 * * * *', Schedule::HOURLY);
        $this->assertSame('0 0 * * *', Schedule::DAILY);
        $this->assertSame('0 0 * * 0', Schedule::WEEKLY);
        $this->assertSame('0 0 1 * *', Schedule::MONTHLY);
    }

    public function testNextRunDateReturnsDatetime(): void
    {
        $schedule = new Schedule(Schedule::DAILY);

        $this->assertInstanceOf(\DateTimeInterface::class, $schedule->nextRunDate());
    }

    public function testPreviousRunDateReturnsDatetime(): void
    {
        $schedule = new Schedule(Schedule::DAILY);

        $this->assertInstanceOf(\DateTimeInterface::class, $schedule->previousRunDate());
    }

    public function testIsDueReturnsBool(): void
    {
        $schedule = new Schedule(Schedule::DAILY);

        $this->assertIsBool($schedule->isDue());
    }

    public function testDefaultValues(): void
    {
        $schedule = new Schedule('0 0 * * *');

        $this->assertFalse($schedule->preventOverlapping);
        $this->assertSame(1440, $schedule->lockExpiresAfter);
        $this->assertNull($schedule->description);
    }
}
