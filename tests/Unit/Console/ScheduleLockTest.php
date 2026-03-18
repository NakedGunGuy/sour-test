<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Console\ScheduleLock;

class ScheduleLockTest extends TestCase
{
    private string $lockDir;

    protected function setUp(): void
    {
        $this->lockDir = sys_get_temp_dir() . '/sauerkraut_test_locks_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->lockDir)) {
            return;
        }

        foreach (glob($this->lockDir . '/*.lock') as $file) {
            unlink($file);
        }

        rmdir($this->lockDir);
    }

    public function testAcquireReturnsTrue(): void
    {
        $lock = new ScheduleLock($this->lockDir);

        $this->assertTrue($lock->acquire('test:command', 60));
    }

    public function testAcquireReturnsFalseWhenAlreadyLocked(): void
    {
        $lock = new ScheduleLock($this->lockDir);

        $lock->acquire('test:command', 60);

        $this->assertFalse($lock->acquire('test:command', 60));
    }

    public function testReleaseAllowsReacquire(): void
    {
        $lock = new ScheduleLock($this->lockDir);

        $lock->acquire('test:command', 60);
        $lock->release('test:command');

        $this->assertTrue($lock->acquire('test:command', 60));
    }

    public function testDifferentCommandsLockIndependently(): void
    {
        $lock = new ScheduleLock($this->lockDir);

        $this->assertTrue($lock->acquire('command:a', 60));
        $this->assertTrue($lock->acquire('command:b', 60));
    }

    public function testExpiredLockCanBeReacquired(): void
    {
        $lock = new ScheduleLock($this->lockDir);
        $lock->acquire('test:command', 0);

        // Lock expires after 0 minutes, so it should be immediately expired
        sleep(1);

        $this->assertTrue($lock->acquire('test:command', 0));
    }

    public function testCreatesLockDirectory(): void
    {
        $this->assertDirectoryDoesNotExist($this->lockDir);

        new ScheduleLock($this->lockDir);

        $this->assertDirectoryExists($this->lockDir);
    }
}
