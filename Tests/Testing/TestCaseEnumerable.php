<?php

namespace YaLinqo\Tests\Testing;

use YaLinqo\Enumerable as E, YaLinqo\Functions;

class TestCaseEnumerable extends \PHPUnit_Framework_TestCase
{
    protected function setUp ()
    {
        $this->setOutputCallback(function ($str) { return str_replace("\r\n", "\n", $str); });
    }

    public static function assertEnumEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertEquals($expected, $actual->take($maxLength)->toArrayDeep());
    }

    public static function assertEnumOrderEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertEquals($expected, $actual->take($maxLength)->select('[ $k, $v ]', Functions::increment())->toArrayDeep());
    }

    public static function assertEnumValuesEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertEquals($expected, $actual->take($maxLength)->toValues()->toArrayDeep());
    }
}
