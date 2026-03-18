<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Validation\Rules;
use Sauerkraut\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function testPassingValidation(): void
    {
        $result = Validator::validate(
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => [Rules::required(), Rules::string()], 'email' => [Rules::required(), Rules::email()]],
        );

        $this->assertTrue($result->passed());
        $this->assertFalse($result->failed());
        $this->assertEmpty($result->errors());
    }

    public function testFailingValidation(): void
    {
        $result = Validator::validate(
            ['name' => '', 'email' => 'not-an-email'],
            ['name' => [Rules::required()], 'email' => [Rules::required(), Rules::email()]],
        );

        $this->assertTrue($result->failed());
        $this->assertArrayHasKey('name', $result->errors());
        $this->assertArrayHasKey('email', $result->errors());
    }

    public function testNullableSkipsRules(): void
    {
        $result = Validator::validate(
            ['bio' => null],
            ['bio' => [Rules::nullable(), Rules::string(), Rules::min(10)]],
        );

        $this->assertTrue($result->passed());
    }

    public function testNullableWithValueStillValidates(): void
    {
        $result = Validator::validate(
            ['bio' => 'hi'],
            ['bio' => [Rules::nullable(), Rules::string(), Rules::min(10)]],
        );

        $this->assertTrue($result->failed());
    }

    public function testValidatedReturnsOnlyRuledFields(): void
    {
        $result = Validator::validate(
            ['name' => 'Jane', 'email' => 'jane@test.com', 'hack' => 'DROP TABLE'],
            ['name' => [Rules::required()], 'email' => [Rules::required()]],
        );

        $this->assertArrayHasKey('name', $result->validated());
        $this->assertArrayHasKey('email', $result->validated());
        $this->assertArrayNotHasKey('hack', $result->validated());
    }

    public function testMissingFieldFailsRequired(): void
    {
        $result = Validator::validate(
            [],
            ['name' => [Rules::required()]],
        );

        $this->assertTrue($result->failed());
        $this->assertNotNull($result->error('name'));
    }

    public function testErrorReturnsFirstErrorForField(): void
    {
        $result = Validator::validate(
            ['name' => ''],
            ['name' => [Rules::required(), Rules::min(3)]],
        );

        $this->assertNotNull($result->error('name'));
    }

    public function testErrorReturnsNullForValidField(): void
    {
        $result = Validator::validate(
            ['name' => 'John'],
            ['name' => [Rules::required()]],
        );

        $this->assertNull($result->error('name'));
    }
}
