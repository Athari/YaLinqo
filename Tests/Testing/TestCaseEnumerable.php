<?php

namespace YaLinqo\Tests\Testing;

use YaLinqo\{Enumerable as E, Functions as F};

// HACK: PHP 7.0 testing compatibility. Remove in YaLinqo 4.0 which drops support for ancient PHP versions.

if (PHP_VERSION_ID < 70100) {
    class TestCaseEnumerableBase extends \PHPUnit\Framework\TestCase {
        protected function setUp() // phpcs:ignore 2439
        {
            $this->setOutputCallback(function($str) { return str_replace("\r\n", "\n", $str); });
        }
    }
}
else {
    class TestCaseEnumerableBase extends \PHPUnit\Framework\TestCase {
        protected function setUp(): void // phpcs:ignore 0405 0413 6402
        {
            $this->setOutputCallback(function($str) { return str_replace("\r\n", "\n", $str); });
        }
    }
}

class TestCaseEnumerable extends TestCaseEnumerableBase
{
    public function setExpectedException($exception, $message = null)
    {
        $this->expectException($exception);
        if ($message !== null)
            $this->expectExceptionMessage($message);
    }

    public static function assertEnumSame(array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertSame($expected, $actual->take($maxLength)->toArrayDeep());
    }

    public static function assertEnumOrderSame(array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertSame($expected, $actual->take($maxLength)->select('[ $k, $v ]', F::increment())->toArrayDeep());
    }

    public static function assertEnumValuesEquals(array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertEquals($expected, $actual->take($maxLength)->toValues()->toArrayDeep());
    }

    public static function assertEnumValuesSame(array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        self::assertSame($expected, $actual->take($maxLength)->toValues()->toArrayDeep());
    }
}
