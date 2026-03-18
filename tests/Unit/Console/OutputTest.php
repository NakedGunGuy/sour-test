<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Console\Output;

class OutputTest extends TestCase
{
    public function testLineOutputsText(): void
    {
        $output = new Output();

        ob_start();
        $output->line('Hello');
        $result = ob_get_clean();

        $this->assertSame("Hello\n", $result);
    }

    public function testEmptyLine(): void
    {
        $output = new Output();

        ob_start();
        $output->line();
        $result = ob_get_clean();

        $this->assertSame("\n", $result);
    }

    public function testNewLine(): void
    {
        $output = new Output();

        ob_start();
        $output->newLine(3);
        $result = ob_get_clean();

        $this->assertSame("\n\n\n", $result);
    }

    public function testInfoHasColor(): void
    {
        $output = new Output();

        ob_start();
        $output->info('Test');
        $result = ob_get_clean();

        $this->assertStringContainsString('Test', $result);
        $this->assertStringContainsString("\033[", $result);
    }

    public function testSuccessHasColor(): void
    {
        $output = new Output();

        ob_start();
        $output->success('Done');
        $result = ob_get_clean();

        $this->assertStringContainsString('Done', $result);
        $this->assertStringContainsString("\033[32m", $result);
    }

    public function testErrorHasColor(): void
    {
        $output = new Output();

        ob_start();
        $output->error('Fail');
        $result = ob_get_clean();

        $this->assertStringContainsString('Fail', $result);
        $this->assertStringContainsString("\033[31m", $result);
    }

    public function testTableRendersCorrectly(): void
    {
        $output = new Output();

        ob_start();
        $output->table(['Name', 'Age'], [['John', '30'], ['Jane', '25']]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Name', $result);
        $this->assertStringContainsString('Age', $result);
        $this->assertStringContainsString('John', $result);
        $this->assertStringContainsString('30', $result);
        $this->assertStringContainsString('+', $result);
        $this->assertStringContainsString('|', $result);
    }
}
