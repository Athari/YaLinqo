<?php

namespace Tests\Unit;

require_once __DIR__ . '/../Testing/Common.php';
use YaLinqo\Enumerable as E, YaLinqo\OrderedEnumerable as OE, YaLinqo\Utils, YaLinqo\Functions;

/** @covers YaLinqo\OrderedEnumerable
 */
class OrderedEnumerableTest extends \Tests\Testing\TestCase_Enumerable
{
    function testThenByDir_asc ()
    {
        // thenByDir (false)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false));
        $this->assertEnumValuesEquals(
            array(1, 11, 2, 22, 22, 333, 444),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false));
        $this->assertEnumValuesEquals(
            array(444, 333, 2, 22, 22, 1, 11),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, '-strncmp($a,$b,1)')->thenByDir(false));

        // thenByDir (false, keySelector)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy()->thenByDir(false, '$v-$k'));
        $this->assertEnumValuesEquals(
            array(a(0, 4), a(1), a(1, 0, 0, 2), a(1, 0, 3)),
            E::from(array(a(1), a(0, 4), a(1, 0, 3), a(1, 0, 0, 2)))->orderBy('$v[0]')->thenByDir(false, '$v[$k]'));

        // thenByDir (false, keySelector, comparer)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false, null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            array(22, 2, 444, 11, 22, 1, 333),
            E::from(array(333, 1, 11, 22, 2, 444, 22))
                    ->orderBy('(int)(-$k/2)')->thenByDir(false, null, 'strncmp($a,$b,1)'));
    }

    function testThenByDir_desc ()
    {
        // thenByDir (true)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true));
        $this->assertEnumValuesEquals(
            array(11, 1, 22, 22, 2, 333, 444),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true));
        $this->assertEnumValuesEquals(
            array(444, 333, 22, 22, 2, 11, 1),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, '-strncmp($a,$b,1)')->thenByDir(true));

        // thenByDir (true, keySelector)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy()->thenByDir(true, '$v-$k'));
        $this->assertEnumValuesEquals(
            array(a(0, 4), a(1, 0, 3), a(1, 0, 0, 2), a(1)),
            E::from(array(a(1), a(0, 4), a(1, 0, 3), a(1, 0, 0, 2)))->orderBy('$v[0]')->thenByDir(true, '$v[$k]'));

        // thenByDir (true, keySelector, comparer)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true, null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            array(333, 1, 22, 11, 444, 2, 22),
            E::from(array(333, 1, 11, 22, 2, 444, 22))
                    ->orderBy('(int)($k/2)')->thenByDir(true, null, 'strncmp($a,$b,1)'));
    }

    function testThenBy ()
    {
        // thenBy ()
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenBy());
        $this->assertEnumValuesEquals(
            array(1, 11, 2, 22, 22, 333, 444),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, 'strncmp($a,$b,1)')->thenBy());
        $this->assertEnumValuesEquals(
            array(444, 333, 2, 22, 22, 1, 11),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, '-strncmp($a,$b,1)')->thenBy());

        // thenBy (keySelector)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy()->thenBy('$v-$k'));
        $this->assertEnumValuesEquals(
            array(a(0, 4), a(1), a(1, 0, 0, 2), a(1, 0, 3)),
            E::from(array(a(1), a(0, 4), a(1, 0, 3), a(1, 0, 0, 2)))->orderBy('$v[0]')->thenBy('$v[$k]'));

        // thenBy (keySelector, comparer)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenBy(null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            array(22, 2, 444, 11, 22, 1, 333),
            E::from(array(333, 1, 11, 22, 2, 444, 22))
                    ->orderBy('(int)(-$k/2)')->thenBy(null, 'strncmp($a,$b,1)'));
    }

    function testThenByDescending ()
    {
        // thenByDescending ()
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending());
        $this->assertEnumValuesEquals(
            array(11, 1, 22, 22, 2, 333, 444),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending());
        $this->assertEnumValuesEquals(
            array(444, 333, 22, 22, 2, 11, 1),
            E::from(array(333, 1, 11, 22, 2, 444, 22))->orderBy(null, '-strncmp($a,$b,1)')->thenByDescending());

        // thenByDescending (keySelector)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy()->thenByDescending('$v-$k'));
        $this->assertEnumValuesEquals(
            array(a(0, 4), a(1, 0, 3), a(1, 0, 0, 2), a(1)),
            E::from(array(a(1), a(0, 4), a(1, 0, 3), a(1, 0, 0, 2)))->orderBy('$v[0]')->thenByDescending('$v[$k]'));

        // thenByDescending (keySelector, comparer)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending(null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            array(333, 1, 22, 11, 444, 2, 22),
            E::from(array(333, 1, 11, 22, 2, 444, 22))
                    ->orderBy('(int)($k/2)')->thenByDescending(null, 'strncmp($a,$b,1)'));
    }

    function testThenByAll_multiple ()
    {
        $a = array();
        for ($i = 0; $i < 2; ++$i)
            for ($j = 0; $j < 2; ++$j)
                for ($k = 0; $k < 2; ++$k)
                    $a[] = array($i, $j, $k);
        shuffle($a);

        $this->assertBinArrayEquals(
            array('000', '001', '010', '011', '100', '101', '110', '111'),
            E::from($a)->orderBy('$v[0]')->thenBy('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            array('001', '000', '011', '010', '101', '100', '111', '110'),
            E::from($a)->orderBy('$v[0]')->thenBy('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            array('010', '011', '000', '001', '110', '111', '100', '101'),
            E::from($a)->orderBy('$v[0]')->thenByDescending('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            array('011', '010', '001', '000', '111', '110', '101', '100'),
            E::from($a)->orderBy('$v[0]')->thenByDescending('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            array('100', '101', '110', '111', '000', '001', '010', '011'),
            E::from($a)->orderByDescending('$v[0]')->thenBy('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            array('101', '100', '111', '110', '001', '000', '011', '010'),
            E::from($a)->orderByDescending('$v[0]')->thenBy('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            array('110', '111', '100', '101', '010', '011', '000', '001'),
            E::from($a)->orderByDescending('$v[0]')->thenByDescending('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            array('111', '110', '101', '100', '011', '010', '001', '000'),
            E::from($a)->orderByDescending('$v[0]')->thenByDescending('$v[1]')->thenByDescending('$v[2]'));
    }

    function assertBinArrayEquals (array $expected, E $actual)
    {
        $this->assertEquals($expected, $actual->select('implode($v)')->toList());
    }
}
