<?php

namespace Tests\Unit;

require_once __DIR__ . '/../Testing/Common.php';
use YaLinqo\Enumerator as E;

/** @covers YaLinqo\Enumerator
 */
class EnumeratorTest extends \PHPUnit_Framework_TestCase
{
    function testEnumeration ()
    {
        $i = 0;
        $e = new E(function ($yield) use (&$i)
        {
            $yield($i + 1, $i + 2);
            return $i++ < 3;
        });
        $a = array();
        foreach ($e as $k => $v)
            $a[$k] = $v;
        $this->assertEquals(array(2 => 1, 3 => 2, 4 => 3), $a);
    }

    function testEnumeration_empty ()
    {
        $e = new E(function ()
        {
            return false;
        });
        $e->next();
        $this->assertEquals(false, $e->valid());
    }

    function testEnumeration_throw ()
    {
        $this->setExpectedException('InvalidArgumentException', 'test');
        new E(function ()
        {
            throw new \InvalidArgumentException('test');
        });
    }
}
