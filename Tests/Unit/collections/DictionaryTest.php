<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../Testing/Common.php';
use YaLinqo\collections\Dictionary as D, \stdClass as O;

/** @covers YaLinqo\collections\Dictionary
 */
class DictionaryTest extends \PHPUnit_Framework_TestCase
{
    function testSimpleOffsets ()
    {
        $d = new D();
        $d[1] = 2;
        $d[2] = '3';
        $d[3] = true;
        $d['4'] = 5;
        $d['a'] = 'b';

        $this->assertEquals(5, count($d));
        $this->assertSame(array(1 => 2, 2 => '3', 3 => true, 4 => 5, 'a' => 'b'), $d->toArray());

        $this->assertSame(2, $d[1]);
        $this->assertSame('3', $d[2]);
        $this->assertSame(true, $d[3]);
        $this->assertSame(5, $d['4']);
        $this->assertSame('b', $d['a']);
        $this->assertTrue(isset($d[1]));
        $this->assertTrue(isset($d[2]));
        $this->assertTrue(isset($d[3]));
        $this->assertTrue(isset($d['4']));
        $this->assertTrue(isset($d['a']));
        $this->assertFalse(isset($d[5]));
        $this->assertFalse(isset($d['b']));
        $this->assertFalse(isset($d[false]));

        unset($d[1]);
        unset($d[2]);
        unset($d[3]);

        $this->assertEquals(2, count($d));
        $this->assertSame(array(4 => 5, 'a' => 'b'), $d->toArray());

        unset($d['4']);
        unset($d['a']);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());

        unset($d[5]);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());

        $this->assertFalse(isset($d[1]));
        $this->assertFalse(isset($d[2]));
        $this->assertFalse(isset($d[3]));
        $this->assertFalse(isset($d['4']));
        $this->assertFalse(isset($d['a']));
        $this->assertFalse(isset($d[5]));
    }

    function testObjectOffsets ()
    {
        $o = array(new O, new O, new O);

        $d = new D();
        $d[$o[0]] = $o[1];
        $d[$o[2]] = 1;

        $this->assertEquals(2, count($d));
        $this->assertSame(array(id($o[0]) => a($o[0], $o[1]), id($o[2]) => a($o[2], 1)), $d->toArray());

        $this->assertSame($o[1], $d[$o[0]]);
        $this->assertSame(1, $d[$o[2]]);
        $this->assertTrue(isset($d[$o[0]]));
        $this->assertTrue(isset($d[$o[2]]));
        $this->assertFalse(isset($d[$o[1]]));

        unset($d[$o[0]]);

        $this->assertEquals(1, count($d));
        $this->assertSame(array(id($o[2]) => a($o[2], 1)), $d->toArray());

        unset($d[$o[2]]);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());

        unset($d[$o[1]]);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());

        $this->assertFalse(isset($d[$o[0]]));
        $this->assertFalse(isset($d[$o[2]]));
        $this->assertFalse(isset($d[$o[1]]));
    }

    function testMixedOffsets ()
    {
        $o = array(new O, new O);

        $d = new D();
        $d['a'] = 'b';

        $this->assertEquals(1, count($d));
        $this->assertSame(array('a' => 'b'), $d->toArray());

        $d[$o[0]] = 1;

        $this->assertEquals(2, count($d));
        $this->assertSame(array('a' => a('a', 'b'), id($o[0]) => a($o[0], 1)), $d->toArray());

        $d[2] = 3;

        $this->assertEquals(3, count($d));
        $this->assertSame(array('a' => a('a', 'b'), id($o[0]) => a($o[0], 1), 2 => a(2, 3)), $d->toArray());

        unset($d[$o[0]]);
        unset($d[2]);

        $this->assertEquals(1, count($d));
        $this->assertSame(array('a' => a('a', 'b')), $d->toArray());

        unset($d['a']);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());

        unset($d['d']);
        unset($d[$o[0]]);
        unset($d[$o[1]]);

        $this->assertEquals(0, count($d));
        $this->assertSame(array(), $d->toArray());
    }

    function testArrayOffsets ()
    {
        $this->setExpectedException('YaLinqo\exceptions\NotSupportedException', D::ERROR_ARRAY_KEYS);

        $o = array(array());

        $d = new D();
        $d[$o[0]] = 1;
    }

    function testSimpleOffsets_iteration ()
    {
        $d = new D();
        $d[1] = 2;
        $d[2] = '3';
        $d[3] = true;
        $d['4'] = 5;
        $d['a'] = 'b';

        $a = array();
        $d->rewind();
        while ($d->valid()) {
            $a[$d->key()] = $d->current();
            $d->next();
        }

        $this->assertSame(array(1 => 2, 2 => '3', 3 => true, 4 => 5, 'a' => 'b'), $a);
    }

    function testSimpleOffsets_foreach ()
    {
        $d = new D();
        $d[1] = 2;
        $d[2] = '3';
        $d[3] = true;
        $d['4'] = 5;
        $d['a'] = 'b';

        $a = array();
        foreach ($d as $k => $v) {
            $a[$k] = $v;
        }

        $this->assertSame(array(1 => 2, 2 => '3', 3 => true, 4 => 5, 'a' => 'b'), $a);
    }

    function testObjectOffsets_iteration ()
    {
        $o = array(new O, new O, new O);

        $d = new D();
        $d[$o[0]] = $o[1];
        $d[$o[2]] = 1;

        $a = array();
        $d->rewind();
        while ($d->valid()) {
            $a[] = a($d->key(), $d->current());
            $d->next();
        }

        $this->assertSame(array(a($o[0], $o[1]), a($o[2], 1)), $a);
    }

    function testMixedOffsets_iteration ()
    {
        $o = array(new O, new O, new O);

        $d = new D();
        $d[$o[0]] = $o[1];
        $d['a'] = 'b';
        $d[$o[2]] = 1;
        $d[2] = 3;

        $a = array();
        $d->rewind();
        while ($d->valid()) {
            $a[] = a($d->key(), $d->current());
            $d->next();
        }

        $this->assertSame(array(a($o[0], $o[1]), a('a', 'b'), a($o[2], 1), a(2, 3)), $a);
    }
}
