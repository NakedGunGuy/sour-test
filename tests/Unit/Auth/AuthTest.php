<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Auth\Auth;

class AuthTest extends TestCase
{
    public function testHashPasswordReturnsBcrypt(): void
    {
        $hash = Auth::hashPassword('secret');

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify('secret', $hash));
    }

    public function testHashPasswordDoesNotMatchWrongPassword(): void
    {
        $hash = Auth::hashPassword('secret');

        $this->assertFalse(password_verify('wrong', $hash));
    }

    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $hash1 = Auth::hashPassword('password1');
        $hash2 = Auth::hashPassword('password2');

        $this->assertNotSame($hash1, $hash2);
    }

    public function testSamePasswordProducesDifferentHashes(): void
    {
        $hash1 = Auth::hashPassword('secret');
        $hash2 = Auth::hashPassword('secret');

        // bcrypt uses random salt, so hashes should differ
        $this->assertNotSame($hash1, $hash2);
    }
}
