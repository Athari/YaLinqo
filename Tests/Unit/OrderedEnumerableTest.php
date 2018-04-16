<?php

namespace YaLinqo\Tests\Unit;

use YaLinqo\Enumerable as E, YaLinqo\Utils, YaLinqo\Functions;
use YaLinqo\Tests\Testing\TestCaseEnumerable;

/** @covers \YaLinqo\OrderedEnumerable
 */
class OrderedEnumerableTest extends TestCaseEnumerable
{
    function testThenByDir_asc()
    {
        // thenByDir (false)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false));
        $this->assertEnumValuesEquals(
            [ 1, 11, 2, 22, 22, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false));
        $this->assertEnumValuesEquals(
            [ 444, 333, 2, 22, 22, 1, 11 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, '-strncmp($a,$b,1)')->thenByDir(false));

        // thenByDir (false, keySelector)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDir(false, '$v-$k'));
        $this->assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1 ], [ 1, 0, 0, 2 ], [ 1, 0, 3 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy('$v[0]')->thenByDir(false, '$v[$k]'));

        // thenByDir (false, keySelector, comparer)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(false, null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            [ 22, 2, 444, 11, 22, 1, 333 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy('(int)(-$k/2)')->thenByDir(false, null, 'strncmp($a,$b,1)'));
    }

    function testThenByDir_desc()
    {
        // thenByDir (true)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true));
        $this->assertEnumValuesEquals(
            [ 11, 1, 22, 22, 2, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true));
        $this->assertEnumValuesEquals(
            [ 444, 333, 22, 22, 2, 11, 1 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, '-strncmp($a,$b,1)')->thenByDir(true));

        // thenByDir (true, keySelector)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDir(true, '$v-$k'));
        $this->assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ], [ 1 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy('$v[0]')->thenByDir(true, '$v[$k]'));

        // thenByDir (true, keySelector, comparer)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDir(true, null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            [ 333, 1, 22, 11, 444, 2, 22 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy('(int)($k/2)')->thenByDir(true, null, 'strncmp($a,$b,1)'));
    }

    function testThenBy()
    {
        // thenBy ()
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenBy());
        $this->assertEnumValuesEquals(
            [ 1, 11, 2, 22, 22, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, 'strncmp($a,$b,1)')->thenBy());
        $this->assertEnumValuesEquals(
            [ 444, 333, 2, 22, 22, 1, 11 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, '-strncmp($a,$b,1)')->thenBy());

        // thenBy (keySelector)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenBy('$v-$k'));
        $this->assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1 ], [ 1, 0, 0, 2 ], [ 1, 0, 3 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy('$v[0]')->thenBy('$v[$k]'));

        // thenBy (keySelector, comparer)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenBy(null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            [ 22, 2, 444, 11, 22, 1, 333 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy('(int)(-$k/2)')->thenBy(null, 'strncmp($a,$b,1)'));
    }

    function testThenByDescending()
    {
        // thenByDescending ()
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending());
        $this->assertEnumValuesEquals(
            [ 11, 1, 22, 22, 2, 333, 444 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending());
        $this->assertEnumValuesEquals(
            [ 444, 333, 22, 22, 2, 11, 1 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])->orderBy(null, '-strncmp($a,$b,1)')->thenByDescending());

        // thenByDescending (keySelector)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy()->thenByDescending('$v-$k'));
        $this->assertEnumValuesEquals(
            [ [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ], [ 1 ] ],
            E::from([ [ 1 ], [ 0, 4 ], [ 1, 0, 3 ], [ 1, 0, 0, 2 ] ])->orderBy('$v[0]')->thenByDescending('$v[$k]'));

        // thenByDescending (keySelector, comparer)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy(null, 'strncmp($a,$b,1)')->thenByDescending(null, 'strncmp($a,$b,1)'));
        $this->assertEnumValuesEquals(
            [ 333, 1, 22, 11, 444, 2, 22 ],
            E::from([ 333, 1, 11, 22, 2, 444, 22 ])
                ->orderBy('(int)($k/2)')->thenByDescending(null, 'strncmp($a,$b,1)'));
    }

    function testThenByAll_multiple()
    {
        $a = [];
        for ($i = 0; $i < 2; ++$i)
            for ($j = 0; $j < 2; ++$j)
                for ($k = 0; $k < 2; ++$k)
                    $a[] = [ $i, $j, $k ];
        shuffle($a);

        $this->assertBinArrayEquals(
            [ '000', '001', '010', '011', '100', '101', '110', '111' ],
            E::from($a)->orderBy('$v[0]')->thenBy('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            [ '001', '000', '011', '010', '101', '100', '111', '110' ],
            E::from($a)->orderBy('$v[0]')->thenBy('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            [ '010', '011', '000', '001', '110', '111', '100', '101' ],
            E::from($a)->orderBy('$v[0]')->thenByDescending('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            [ '011', '010', '001', '000', '111', '110', '101', '100' ],
            E::from($a)->orderBy('$v[0]')->thenByDescending('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            [ '100', '101', '110', '111', '000', '001', '010', '011' ],
            E::from($a)->orderByDescending('$v[0]')->thenBy('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            [ '101', '100', '111', '110', '001', '000', '011', '010' ],
            E::from($a)->orderByDescending('$v[0]')->thenBy('$v[1]')->thenByDescending('$v[2]'));
        $this->assertBinArrayEquals(
            [ '110', '111', '100', '101', '010', '011', '000', '001' ],
            E::from($a)->orderByDescending('$v[0]')->thenByDescending('$v[1]')->thenBy('$v[2]'));
        $this->assertBinArrayEquals(
            [ '111', '110', '101', '100', '011', '010', '001', '000' ],
            E::from($a)->orderByDescending('$v[0]')->thenByDescending('$v[1]')->thenByDescending('$v[2]'));
    }

    function assertBinArrayEquals(array $expected, E $actual)
    {
        $this->assertEquals($expected, $actual->select('implode($v)')->toList());
    }
}
