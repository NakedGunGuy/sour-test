<?php

declare(strict_types=1);

namespace Tests\Unit\Log;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Log\Logger;
use Sauerkraut\Log\LogManager;

class LoggerTest extends TestCase
{
    private Logger $logger;
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/sauerkraut_test_logs_' . uniqid();
        $this->logger = new Logger('test', $this->logDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*.log') as $file) {
            unlink($file);
        }

        if (is_dir($this->logDir)) {
            rmdir($this->logDir);
        }
    }

    public function testInfoWritesToLogFile(): void
    {
        $this->logger->info('Server started');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('info', $content);
        $this->assertStringContainsString('Server started', $content);
    }

    public function testErrorWritesToLogFile(): void
    {
        $this->logger->error('Something broke');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('error', $content);
        $this->assertStringContainsString('Something broke', $content);
    }

    public function testWarningWritesToLogFile(): void
    {
        $this->logger->warning('Disk space low');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('warning', $content);
    }

    public function testDebugWritesToLogFile(): void
    {
        $this->logger->debug('Variable dump');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('debug', $content);
    }

    public function testContextInterpolation(): void
    {
        $this->logger->info('User {name} logged in from {ip}', [
            'name' => 'John',
            'ip' => '192.168.1.1',
        ]);

        $content = $this->readTodayLog();
        $this->assertStringContainsString('User John logged in from 192.168.1.1', $content);
    }

    public function testContextWithBooleans(): void
    {
        $this->logger->info('Active: {active}', ['active' => true]);

        $content = $this->readTodayLog();
        $this->assertStringContainsString('Active: true', $content);
    }

    public function testContextWithNull(): void
    {
        $this->logger->info('Value: {value}', ['value' => null]);

        $content = $this->readTodayLog();
        $this->assertStringContainsString('Value: null', $content);
    }

    public function testContextWithArray(): void
    {
        $this->logger->info('Data: {data}', ['data' => ['a', 'b']]);

        $content = $this->readTodayLog();
        $this->assertStringContainsString('["a","b"]', $content);
    }

    public function testMultipleEntriesAppend(): void
    {
        $this->logger->info('First');
        $this->logger->info('Second');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('First', $content);
        $this->assertStringContainsString('Second', $content);
    }

    public function testLogIncludesTimestamp(): void
    {
        $this->logger->info('test');

        $content = $this->readTodayLog();
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    public function testLogIncludesChannelName(): void
    {
        $this->logger->info('test');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('test.info', $content);
    }

    public function testMinimumLevelFiltering(): void
    {
        $logger = new Logger('app', $this->logDir, 'warning');

        $logger->debug('should be skipped');
        $logger->info('also skipped');

        $logFile = $this->logDir . '/' . date('Y-m-d') . '.log';
        $this->assertFileDoesNotExist($logFile);

        $logger->warning('this should log');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('this should log', $content);
        $this->assertStringNotContainsString('should be skipped', $content);
    }

    public function testErrorLevelAlwaysLogsAboveMinimum(): void
    {
        $logger = new Logger('app', $this->logDir, 'error');

        $logger->warning('nope');

        $logFile = $this->logDir . '/' . date('Y-m-d') . '.log';
        $this->assertFileDoesNotExist($logFile);

        $logger->error('yes');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('yes', $content);
    }

    public function testLogManagerCreatesChannels(): void
    {
        $manager = new LogManager($this->logDir, [
            'auth' => ['path' => $this->logDir, 'level' => 'info'],
        ]);

        $authLogger = $manager->channel('auth');
        $authLogger->info('Login attempt');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('auth.info', $content);
        $this->assertStringContainsString('Login attempt', $content);
    }

    public function testLogManagerCachesInstances(): void
    {
        $manager = new LogManager($this->logDir, [
            'app' => ['path' => $this->logDir, 'level' => 'debug'],
        ]);

        $first = $manager->channel('app');
        $second = $manager->channel('app');

        $this->assertSame($first, $second);
    }

    public function testLogManagerDefault(): void
    {
        $manager = new LogManager($this->logDir, [
            'app' => ['path' => $this->logDir, 'level' => 'debug'],
        ], 'app');

        $manager->default()->info('default channel');

        $content = $this->readTodayLog();
        $this->assertStringContainsString('app.info', $content);
    }

    private function readTodayLog(): string
    {
        $file = $this->logDir . '/' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);

        return file_get_contents($file);
    }
}
