<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../YaLinqo/Linq.php';
use YaLinqo\Functions as F;

/** @covers YaLinqo\Functions
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    function testIdentity ()
    {
        /** @var $f callback */
        $f = F::$identity;
        $this->assertSame(2, $f(2));
    }

    function testKey ()
    {
        /** @var $f callback */
        $f = F::$key;
        $this->assertSame(3, $f(2, 3));
    }

    function testValue ()
    {
        /** @var $f callback */
        $f = F::$value;
        $this->assertSame(2, $f(2, 3));
    }

    function testTrue ()
    {
        /** @var $f callback */
        $f = F::$true;
        $this->assertSame(true, $f());
    }

    function testFalse ()
    {
        /** @var $f callback */
        $f = F::$false;
        $this->assertSame(false, $f());
    }

    function testBlank ()
    {
        /** @var $f callback */
        $f = F::$blank;
        $this->assertSame(null, $f());
    }

    function testCompareStrict ()
    {
        /** @var $f callback */
        $f = F::$compareStrict;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(-1, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    function testCompareLoose ()
    {
        /** @var $f callback */
        $f = F::$compareLoose;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(0, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    function testIncrement ()
    {
        /** @var $f callback */
        $f = F::increment();
        $this->assertSame(0, $f());
        $this->assertSame(1, $f());
        $this->assertSame(2, $f());

        $g = F::increment();
        $this->assertSame(0, $g());
        $this->assertSame(1, $g());
        $this->assertSame(3, $f());
    }
}
