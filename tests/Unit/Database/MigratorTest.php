<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\MigrationRepository;
use Sauerkraut\Database\Migrator;

class MigratorTest extends TestCase
{
    private Connection $db;
    private string $migrationsDir;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->migrationsDir = sys_get_temp_dir() . '/sauerkraut_test_migrations_' . uniqid();
        mkdir($this->migrationsDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->migrationsDir . '/*.php') as $file) {
            unlink($file);
        }

        rmdir($this->migrationsDir);
    }

    public function testMigrateWithNoFiles(): void
    {
        $migrator = $this->buildMigrator();
        $ran = $migrator->migrate();

        $this->assertEmpty($ran);
    }

    public function testMigrateRunsPendingMigrations(): void
    {
        $this->createMigration('2026_03_18_100000_create_test_table', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class CreateTestTable extends Migration {
            public function up(): void {
                $this->db->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
            }
            public function down(): void {
                $this->db->execute('DROP TABLE test');
            }
        }
        PHP);

        $migrator = $this->buildMigrator();
        $ran = $migrator->migrate();

        $this->assertCount(1, $ran);
        $this->assertSame('2026_03_18_100000_create_test_table', $ran[0]);

        // Table should exist
        $result = $this->db->queryOne("SELECT name FROM sqlite_master WHERE type='table' AND name='test'");
        $this->assertNotNull($result);
    }

    public function testMigrateSkipsAlreadyRun(): void
    {
        $this->createMigration('2026_03_18_100000_create_test_table', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class CreateTestTable2 extends Migration {
            public function up(): void {
                $this->db->execute('CREATE TABLE test2 (id INTEGER PRIMARY KEY)');
            }
            public function down(): void {
                $this->db->execute('DROP TABLE test2');
            }
        }
        PHP);

        $migrator = $this->buildMigrator();
        $migrator->migrate();
        $ran = $migrator->migrate();

        $this->assertEmpty($ran);
    }

    public function testRollback(): void
    {
        $this->createMigration('2026_03_18_100000_create_rollback_table', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class CreateRollbackTable extends Migration {
            public function up(): void {
                $this->db->execute('CREATE TABLE rollback_test (id INTEGER PRIMARY KEY)');
            }
            public function down(): void {
                $this->db->execute('DROP TABLE rollback_test');
            }
        }
        PHP);

        $migrator = $this->buildMigrator();
        $migrator->migrate();
        $rolledBack = $migrator->rollback();

        $this->assertCount(1, $rolledBack);

        // Table should no longer exist
        $result = $this->db->queryOne("SELECT name FROM sqlite_master WHERE type='table' AND name='rollback_test'");
        $this->assertNull($result);
    }

    public function testRollbackWithNothingToRollBack(): void
    {
        $migrator = $this->buildMigrator();
        $rolledBack = $migrator->rollback();

        $this->assertEmpty($rolledBack);
    }

    public function testStatus(): void
    {
        $this->createMigration('2026_03_18_100000_status_test', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class StatusTest extends Migration {
            public function up(): void {}
            public function down(): void {}
        }
        PHP);

        $migrator = $this->buildMigrator();

        $statuses = $migrator->status();
        $this->assertCount(1, $statuses);
        $this->assertFalse($statuses[0]['ran']);

        $migrator->migrate();

        $statuses = $migrator->status();
        $this->assertTrue($statuses[0]['ran']);
    }

    public function testMigrationsRunInOrder(): void
    {
        $this->createMigration('2026_03_18_100000_first', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class First extends Migration {
            public function up(): void {
                $this->db->execute('CREATE TABLE first_table (id INTEGER PRIMARY KEY)');
            }
            public function down(): void {
                $this->db->execute('DROP TABLE first_table');
            }
        }
        PHP);

        $this->createMigration('2026_03_18_200000_second', <<<'PHP'
        <?php
        declare(strict_types=1);
        use Sauerkraut\Database\Migration;
        class Second extends Migration {
            public function up(): void {
                $this->db->execute('CREATE TABLE second_table (id INTEGER PRIMARY KEY)');
            }
            public function down(): void {
                $this->db->execute('DROP TABLE second_table');
            }
        }
        PHP);

        $migrator = $this->buildMigrator();
        $ran = $migrator->migrate();

        $this->assertSame('2026_03_18_100000_first', $ran[0]);
        $this->assertSame('2026_03_18_200000_second', $ran[1]);
    }

    private function buildMigrator(): Migrator
    {
        return new Migrator($this->db, new MigrationRepository($this->db), $this->migrationsDir);
    }

    private function createMigration(string $name, string $content): void
    {
        file_put_contents($this->migrationsDir . '/' . $name . '.php', $content);
    }
}
