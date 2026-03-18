<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Cache\Cache;

class CacheTest extends TestCase
{
    private Cache $cache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/sauerkraut_test_cache_' . uniqid();
        $this->cache = new Cache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();

        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function testPutAndGet(): void
    {
        $this->cache->put('name', 'John', 60);

        $this->assertSame('John', $this->cache->get('name'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $this->assertNull($this->cache->get('missing'));
        $this->assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('key'));

        $this->cache->put('key', 'value', 60);

        $this->assertTrue($this->cache->has('key'));
    }

    public function testForget(): void
    {
        $this->cache->put('key', 'value', 60);
        $this->cache->forget('key');

        $this->assertNull($this->cache->get('key'));
    }

    public function testFlush(): void
    {
        $this->cache->put('a', 1, 60);
        $this->cache->put('b', 2, 60);
        $this->cache->flush();

        $this->assertNull($this->cache->get('a'));
        $this->assertNull($this->cache->get('b'));
    }

    public function testExpiredValueReturnsDefault(): void
    {
        $this->cache->put('temp', 'value', 0);
        sleep(1);

        $this->assertNull($this->cache->get('temp'));
    }

    public function testRemember(): void
    {
        $callCount = 0;

        $value1 = $this->cache->remember('computed', 60, function () use (&$callCount) {
            $callCount++;
            return 'expensive';
        });

        $value2 = $this->cache->remember('computed', 60, function () use (&$callCount) {
            $callCount++;
            return 'expensive';
        });

        $this->assertSame('expensive', $value1);
        $this->assertSame('expensive', $value2);
        $this->assertSame(1, $callCount);
    }

    public function testStoresArrays(): void
    {
        $this->cache->put('data', ['a' => 1, 'b' => 2], 60);

        $this->assertSame(['a' => 1, 'b' => 2], $this->cache->get('data'));
    }

    public function testStoresIntegers(): void
    {
        $this->cache->put('count', 42, 60);

        $this->assertSame(42, $this->cache->get('count'));
    }

    public function testCreatesCacheDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/sauerkraut_cache_new_' . uniqid();
        $this->assertDirectoryDoesNotExist($dir);

        $cache = new Cache($dir);
        $this->assertDirectoryExists($dir);

        rmdir($dir);
    }
}
