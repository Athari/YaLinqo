<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../Testing/Common.php';
use YaLinqo\collections\Lookup as L, \stdClass as O;

/** @covers YaLinqo\collections\Lookup
 */
class LookupTest extends \PHPUnit_Framework_TestCase
{
    function testMixedOffsets ()
    {
        $o = array(new O, new O, new O, new O, new O, new O, new O);

        $l = new L();
        $l->append(2, 3);
        $l->append(2, '4');
        $l->append(2, $o[0]);

        $this->assertEquals(1, count($l));
        $this->assertSame(array(
            2 => a(3, '4', $o[0])
        ), $l->toArray());
        $this->assertSame(array(3, '4', $o[0]), $l[2]);
        $this->assertSame(array(), $l[3]);

        $l->append($o[1], 5);
        $l->append($o[1], '6');
        $l->append($o[1], $o[2]);
        $l->append($o[3], $o[2]);

        $this->assertEquals(3, count($l));
        $this->assertSame(array(
            2 => a(2, a(3, '4', $o[0])),
            id($o[1]) => a($o[1], a(5, '6', $o[2])),
            id($o[3]) => a($o[3], a($o[2])),
        ), $l->toArray());
        $this->assertSame(array(3, '4', $o[0]), $l[2]);
        $this->assertSame(array(5, '6', $o[2]), $l[$o[1]]);
        $this->assertSame(array(), $l[$o[2]]);

        $l->append('a', 7);
        $l->append('a', true);
        $l->append('a', $o[4]);
        $l->append('b', $o[5]);
        $l->append('b', $o[5]);
        $l->append('c', $o[6]);

        $this->assertEquals(6, count($l));
        $this->assertSame(array(
            2 => a(2, a(3, '4', $o[0])),
            id($o[1]) => a($o[1], a(5, '6', $o[2])),
            id($o[3]) => a($o[3], a($o[2])),
            'a' => a('a', a(7, true, $o[4])),
            'b' => a('b', a($o[5], $o[5])),
            'c' => a('c', a($o[6])),
        ), $l->toArray());
        $this->assertSame(array(3, '4', $o[0]), $l[2]);
        $this->assertSame(array(), $l[3]);
        $this->assertSame(array(5, '6', $o[2]), $l[$o[1]]);
        $this->assertSame(array(), $l[$o[2]]);
        $this->assertSame(array(7, true, $o[4]), $l['a']);
        $this->assertSame(array($o[6]), $l['c']);
        $this->assertSame(array(), $l[$o[2]]);


        unset($l[$o[1]]);
        unset($l['a']);
        unset($l[2]);

        $this->assertEquals(3, count($l));
        $this->assertSame(array(
            id($o[3]) => a($o[3], a($o[2])),
            'b' => a('b', a($o[5], $o[5])),
            'c' => a('c', a($o[6])),
        ), $l->toArray());

        unset($l[id($o[3])]);
        unset($l['b']);
        unset($l['c']);
        unset($l['d']);

        $this->assertEquals(0, count($l));
        $this->assertSame(array(), $l->toArray());
    }

    function testOffsetSet_arrayValue ()
    {
        $l = new L();
        $l[1] = array(3, 4);

        $this->assertEquals(1, count($l));
    }

    function testOffsetSet_notArrayValue ()
    {
        $this->setExpectedException('InvalidArgumentException', L::ERROR_LOOKUP_VALUE_NOT_ARRAY);

        $l = new L();
        $l[1] = 'a';
    }
}
