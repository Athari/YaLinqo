<?php

namespace Tests\Testing;

require_once __DIR__ . '/../Testing/Common.php';
use YaLinqo\Enumerable as E, YaLinqo\Functions;

class TestCase_Enumerable extends \PHPUnit_Framework_TestCase
{
    static function setUpBeforeClass ()
    {
        \PHPUnit_Framework_ComparatorFactory::getDefaultInstance()->register(new \Tests\Testing\Comparator_ArrayEnumerable);
    }

    function setUp ()
    {
        $this->setOutputCallback(function ($str) { return str_replace("\r\n", "\n", $str); });
    }

    function assertEnumEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        $this->assertEquals($expected, $actual->take($maxLength));
    }

    function assertEnumOrderEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        $this->assertEquals($expected, $actual->take($maxLength)->select('array($k, $v)', Functions::increment()));
    }

    function assertEnumValuesEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        $this->assertEquals($expected, $actual->take($maxLength)->toValues());
    }
}
