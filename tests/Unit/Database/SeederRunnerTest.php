<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\SeederRunner;

class SeederRunnerTest extends TestCase
{
    private Connection $db;
    private string $seedersDir;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->db->execute('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');

        $this->seedersDir = sys_get_temp_dir() . '/sauerkraut_test_seeders_' . uniqid();
        mkdir($this->seedersDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->seedersDir . '/*.php') as $file) {
            unlink($file);
        }

        rmdir($this->seedersDir);
    }

    public function testRunWithNoSeeders(): void
    {
        $runner = new SeederRunner($this->db, $this->seedersDir);
        $ran = $runner->run();

        $this->assertEmpty($ran);
    }

    public function testRunsAllSeeders(): void
    {
        $this->createSeeder('ItemsSeeder', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Seeder;
        class ItemsSeeder extends Seeder {
            public function run(): void {
                $this->db->execute("INSERT INTO items (name) VALUES (?)", ['Widget']);
            }
        }
        PHP);

        $runner = new SeederRunner($this->db, $this->seedersDir);
        $ran = $runner->run();

        $this->assertCount(1, $ran);

        $row = $this->db->queryOne('SELECT name FROM items');
        $this->assertSame('Widget', $row['name']);
    }

    public function testRunsSpecificSeeder(): void
    {
        $this->createSeeder('ItemsSeeder2', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Seeder;
        class ItemsSeeder2 extends Seeder {
            public function run(): void {
                $this->db->execute("INSERT INTO items (name) VALUES (?)", ['Specific']);
            }
        }
        PHP);

        $runner = new SeederRunner($this->db, $this->seedersDir);
        $ran = $runner->run('ItemsSeeder2');

        $this->assertCount(1, $ran);
        $this->assertSame('ItemsSeeder2', $ran[0]);
    }

    public function testThrowsForMissingSeeder(): void
    {
        $runner = new SeederRunner($this->db, $this->seedersDir);

        $this->expectException(\RuntimeException::class);
        $runner->run('NonExistent');
    }

    private function createSeeder(string $name, string $content): void
    {
        file_put_contents($this->seedersDir . '/' . $name . '.php', $content);
    }
}
