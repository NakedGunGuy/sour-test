<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Input;
use Sauerkraut\Console\Option;
use Sauerkraut\Console\Signature;

class InputTest extends TestCase
{
    public function testParsesCommand(): void
    {
        $sig = new Signature('test', 'A test');
        $input = new Input($sig, ['sauerkraut', 'test']);

        $this->assertSame('test', $input->command());
    }

    public function testParsesPositionalArguments(): void
    {
        $sig = new Signature('publish', '', [
            new Argument('package'),
            new Argument('target'),
        ]);

        $input = new Input($sig, ['sauerkraut', 'publish', 'sauerkraut/ui', 'components/button']);

        $this->assertSame('sauerkraut/ui', $input->argument('package'));
        $this->assertSame('components/button', $input->argument('target'));
    }

    public function testArgumentDefaultValue(): void
    {
        $sig = new Signature('test', '', [
            new Argument('name', default: 'world'),
        ]);

        $input = new Input($sig, ['sauerkraut', 'test']);

        $this->assertSame('world', $input->argument('name'));
    }

    public function testParsesLongOption(): void
    {
        $sig = new Signature('test', '', [], [
            new Option('force'),
        ]);

        $input = new Input($sig, ['sauerkraut', 'test', '--force']);

        $this->assertTrue($input->hasOption('force'));
        $this->assertTrue($input->option('force'));
    }

    public function testParsesShortOption(): void
    {
        $sig = new Signature('test', '', [], [
            new Option('force', shortcut: 'f'),
        ]);

        $input = new Input($sig, ['sauerkraut', 'test', '-f']);

        $this->assertTrue($input->hasOption('force'));
    }

    public function testOptionWithValue(): void
    {
        $sig = new Signature('test', '', [], [
            new Option('name', acceptsValue: true),
        ]);

        $input = new Input($sig, ['sauerkraut', 'test', '--name', 'John']);

        $this->assertSame('John', $input->option('name'));
    }

    public function testMissingOptionReturnsFalsy(): void
    {
        $sig = new Signature('test', '', [], [
            new Option('verbose'),
        ]);

        $input = new Input($sig, ['sauerkraut', 'test']);

        $this->assertFalse($input->hasOption('verbose'));
        $this->assertNull($input->option('verbose'));
    }
}
