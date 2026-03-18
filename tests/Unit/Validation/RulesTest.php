<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Validation\Rules\Alpha;
use Sauerkraut\Validation\Rules\AlphaNum;
use Sauerkraut\Validation\Rules\Between;
use Sauerkraut\Validation\Rules\Confirmed;
use Sauerkraut\Validation\Rules\Email;
use Sauerkraut\Validation\Rules\In;
use Sauerkraut\Validation\Rules\IsBoolean;
use Sauerkraut\Validation\Rules\IsDate;
use Sauerkraut\Validation\Rules\IsInteger;
use Sauerkraut\Validation\Rules\IsString;
use Sauerkraut\Validation\Rules\Max;
use Sauerkraut\Validation\Rules\Min;
use Sauerkraut\Validation\Rules\Regex;
use Sauerkraut\Validation\Rules\Required;
use Sauerkraut\Validation\Rules\Url;

class RulesTest extends TestCase
{
    public function testRequiredPassesWithValue(): void
    {
        $this->assertNull((new Required())->validate('name', 'John', []));
    }

    public function testRequiredFailsWithEmpty(): void
    {
        $this->assertNotNull((new Required())->validate('name', '', []));
        $this->assertNotNull((new Required())->validate('name', null, []));
        $this->assertNotNull((new Required())->validate('name', [], []));
    }

    public function testEmailPassesWithValidEmail(): void
    {
        $this->assertNull((new Email())->validate('email', 'test@example.com', []));
    }

    public function testEmailFailsWithInvalidEmail(): void
    {
        $this->assertNotNull((new Email())->validate('email', 'not-email', []));
        $this->assertNotNull((new Email())->validate('email', 123, []));
    }

    public function testIsStringPasses(): void
    {
        $this->assertNull((new IsString())->validate('name', 'hello', []));
    }

    public function testIsStringFails(): void
    {
        $this->assertNotNull((new IsString())->validate('name', 123, []));
    }

    public function testIsIntegerPasses(): void
    {
        $this->assertNull((new IsInteger())->validate('age', 25, []));
        $this->assertNull((new IsInteger())->validate('age', '25', []));
    }

    public function testIsIntegerFails(): void
    {
        $this->assertNotNull((new IsInteger())->validate('age', 'abc', []));
        $this->assertNotNull((new IsInteger())->validate('age', 3.14, []));
    }

    public function testMinStringLength(): void
    {
        $this->assertNull((new Min(3))->validate('name', 'John', []));
        $this->assertNotNull((new Min(3))->validate('name', 'Jo', []));
    }

    public function testMinNumericValue(): void
    {
        $this->assertNull((new Min(18))->validate('age', 25, []));
        $this->assertNotNull((new Min(18))->validate('age', 10, []));
    }

    public function testMaxStringLength(): void
    {
        $this->assertNull((new Max(5))->validate('name', 'John', []));
        $this->assertNotNull((new Max(3))->validate('name', 'John', []));
    }

    public function testMaxNumericValue(): void
    {
        $this->assertNull((new Max(100))->validate('age', 50, []));
        $this->assertNotNull((new Max(100))->validate('age', 150, []));
    }

    public function testBetween(): void
    {
        $this->assertNull((new Between(1, 10))->validate('qty', 5, []));
        $this->assertNotNull((new Between(1, 10))->validate('qty', 15, []));
        $this->assertNotNull((new Between(1, 10))->validate('qty', 'abc', []));
    }

    public function testInRule(): void
    {
        $this->assertNull((new In(['a', 'b', 'c']))->validate('choice', 'a', []));
        $this->assertNotNull((new In(['a', 'b', 'c']))->validate('choice', 'd', []));
    }

    public function testConfirmed(): void
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'secret'];
        $this->assertNull((new Confirmed())->validate('password', 'secret', $data));

        $data['password_confirmation'] = 'wrong';
        $this->assertNotNull((new Confirmed())->validate('password', 'secret', $data));
    }

    public function testIsBooleanPasses(): void
    {
        $this->assertNull((new IsBoolean())->validate('active', true, []));
        $this->assertNull((new IsBoolean())->validate('active', 0, []));
        $this->assertNull((new IsBoolean())->validate('active', '1', []));
    }

    public function testIsBooleanFails(): void
    {
        $this->assertNotNull((new IsBoolean())->validate('active', 'yes', []));
    }

    public function testIsDate(): void
    {
        $this->assertNull((new IsDate())->validate('date', '2026-03-18', []));
        $this->assertNotNull((new IsDate())->validate('date', 'not-a-date', []));
    }

    public function testUrl(): void
    {
        $this->assertNull((new Url())->validate('site', 'https://example.com', []));
        $this->assertNotNull((new Url())->validate('site', 'not a url', []));
    }

    public function testRegex(): void
    {
        $this->assertNull((new Regex('/^\d{3}$/'))->validate('code', '123', []));
        $this->assertNotNull((new Regex('/^\d{3}$/'))->validate('code', '12', []));
    }

    public function testAlpha(): void
    {
        $this->assertNull((new Alpha())->validate('name', 'John', []));
        $this->assertNotNull((new Alpha())->validate('name', 'John123', []));
    }

    public function testAlphaNum(): void
    {
        $this->assertNull((new AlphaNum())->validate('code', 'abc123', []));
        $this->assertNotNull((new AlphaNum())->validate('code', 'abc-123', []));
    }
}
