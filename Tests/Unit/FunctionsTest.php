<?php

require_once __DIR__ . '/../Testing/Common.php';
use YaLinqo\Functions as F;

class FunctionsTest extends PHPUnit_Framework_TestCase
{
    /** @covers YaLinqo\Functions
     */
    function testFunctions ()
    {
        /** @var $f callback */
        $f = F::$identity;
        $this->assertEquals(2, $f(2));
        $f = F::$key;
        $this->assertEquals(3, $f(2, 3));
        $f = F::$value;
        $this->assertEquals(2, $f(2, 3));
        $f = F::$true;
        $this->assertEquals(true, $f());
        $f = F::$false;
        $this->assertEquals(false, $f());
        $f = F::$blank;
        $this->assertEquals(null, $f());
        $f = F::$compareStrict;
        $this->assertEquals(-1, $f(2, 3));
        $this->assertEquals(-1, $f(2, '2'));
        $this->assertEquals(0, $f(2, 2));
        $this->assertEquals(1, $f(3, 2));
        $f = F::$compareLoose;
        $this->assertEquals(-1, $f(2, 3));
        $this->assertEquals(0, $f(2, '2'));
        $this->assertEquals(0, $f(2, 2));
        $this->assertEquals(1, $f(3, 2));
        $f = F::increment();
        $this->assertEquals(0, $f());
        $this->assertEquals(1, $f());
        $this->assertEquals(2, $f());
    }
}
