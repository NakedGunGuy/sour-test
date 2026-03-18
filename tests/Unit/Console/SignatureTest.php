<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Console\Argument;
use Sauerkraut\Console\Option;
use Sauerkraut\Console\Signature;

class SignatureTest extends TestCase
{
    public function testSignatureProperties(): void
    {
        $sig = new Signature(
            name: 'migrate',
            description: 'Run migrations',
            arguments: [new Argument('direction')],
            options: [new Option('force')],
        );

        $this->assertSame('migrate', $sig->name);
        $this->assertSame('Run migrations', $sig->description);
        $this->assertCount(1, $sig->arguments);
        $this->assertCount(1, $sig->options);
    }

    public function testArgumentDefaults(): void
    {
        $arg = new Argument('name');

        $this->assertSame('name', $arg->name);
        $this->assertSame('', $arg->description);
        $this->assertFalse($arg->required);
        $this->assertNull($arg->default);
    }

    public function testOptionDefaults(): void
    {
        $opt = new Option('verbose');

        $this->assertSame('verbose', $opt->name);
        $this->assertSame('', $opt->description);
        $this->assertNull($opt->shortcut);
        $this->assertFalse($opt->acceptsValue);
        $this->assertNull($opt->default);
    }

    public function testOptionWithShortcut(): void
    {
        $opt = new Option('force', shortcut: 'f');

        $this->assertSame('f', $opt->shortcut);
    }

    public function testOptionAcceptsValue(): void
    {
        $opt = new Option('output', acceptsValue: true, default: 'json');

        $this->assertTrue($opt->acceptsValue);
        $this->assertSame('json', $opt->default);
    }
}
