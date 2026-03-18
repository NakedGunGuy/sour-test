<?php

declare(strict_types=1);

namespace Sauerkraut\Validation;

use Sauerkraut\Database\Connection;

class Rules
{
    public static function required(): Rules\Required
    {
        return new Rules\Required();
    }

    public static function string(): Rules\IsString
    {
        return new Rules\IsString();
    }

    public static function integer(): Rules\IsInteger
    {
        return new Rules\IsInteger();
    }

    public static function email(): Rules\Email
    {
        return new Rules\Email();
    }

    public static function min(int|float $min): Rules\Min
    {
        return new Rules\Min($min);
    }

    public static function max(int|float $max): Rules\Max
    {
        return new Rules\Max($max);
    }

    public static function between(int|float $min, int|float $max): Rules\Between
    {
        return new Rules\Between($min, $max);
    }

    public static function in(array $allowed): Rules\In
    {
        return new Rules\In($allowed);
    }

    public static function confirmed(): Rules\Confirmed
    {
        return new Rules\Confirmed();
    }

    public static function nullable(): Rules\Nullable
    {
        return new Rules\Nullable();
    }

    public static function boolean(): Rules\IsBoolean
    {
        return new Rules\IsBoolean();
    }

    public static function date(): Rules\IsDate
    {
        return new Rules\IsDate();
    }

    public static function url(): Rules\Url
    {
        return new Rules\Url();
    }

    public static function regex(string $pattern): Rules\Regex
    {
        return new Rules\Regex($pattern);
    }

    public static function alpha(): Rules\Alpha
    {
        return new Rules\Alpha();
    }

    public static function alphaNum(): Rules\AlphaNum
    {
        return new Rules\AlphaNum();
    }

    public static function unique(Connection $db, string $table, string $column, mixed $except = null): Rules\Unique
    {
        return new Rules\Unique($db, $table, $column, $except);
    }
}
