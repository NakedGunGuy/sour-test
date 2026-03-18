<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Config\Env;

class EnvTest extends TestCase
{
    public function testParsesKeyValuePairs(): void
    {
        $content = "APP_NAME=Sauerkraut\nAPP_DEBUG=true";
        $result = Env::parse($content);

        $this->assertSame('Sauerkraut', $result['APP_NAME']);
        $this->assertSame('true', $result['APP_DEBUG']);
    }

    public function testIgnoresComments(): void
    {
        $content = "# This is a comment\nAPP_NAME=Sauerkraut";
        $result = Env::parse($content);

        $this->assertCount(1, $result);
        $this->assertSame('Sauerkraut', $result['APP_NAME']);
    }

    public function testIgnoresEmptyLines(): void
    {
        $content = "KEY1=val1\n\nKEY2=val2";
        $result = Env::parse($content);

        $this->assertCount(2, $result);
    }

    public function testHandlesQuotedValues(): void
    {
        $content = "APP_NAME=\"My App\"\nSECRET='s3cret'";
        $result = Env::parse($content);

        $this->assertSame('My App', $result['APP_NAME']);
        $this->assertSame('s3cret', $result['SECRET']);
    }

    public function testHandlesEmptyValues(): void
    {
        $content = 'EMPTY=';
        $result = Env::parse($content);

        $this->assertSame('', $result['EMPTY']);
    }

    public function testHandlesValuesWithEquals(): void
    {
        $content = 'DSN=mysql:host=localhost;dbname=test';
        $result = Env::parse($content);

        $this->assertSame('mysql:host=localhost;dbname=test', $result['DSN']);
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $key = Env::generateKey();
        $plaintext = "APP_KEY=secret\nDB_PASS=hunter2";

        $encrypted = Env::encrypt($plaintext, $key);
        $decrypted = Env::decrypt($encrypted, $key);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptWithWrongKeyFails(): void
    {
        $key1 = Env::generateKey();
        $key2 = Env::generateKey();

        $encrypted = Env::encrypt('secret', $key1);

        $this->expectException(\RuntimeException::class);
        Env::decrypt($encrypted, $key2);
    }

    public function testGenerateKeyFormat(): void
    {
        $key = Env::generateKey();

        $this->assertStringStartsWith('base64:', $key);
    }
}
