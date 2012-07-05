<?php

require_once __DIR__ . '/../../YaLinqo/Linq.php';
use YaLinqo\Enumerable as E, YaLinqo\Utils, Tests\Stubs\AggregateIteratorWrapper;

class EnumerableTest extends PHPUnit_Framework_TestCase
{
    #region Generation

    /** @covers YaLinqo\Enumerable::cycle
     */
    function testCycle ()
    {
        $this->assertEnumEquals(array(1, 1, 1), E::cycle(array(1)), 3);
        $this->assertEnumEquals(array(1, 2, 3, 1, 2), E::cycle(array(1, 2, 3)), 5);
        $this->assertEnumEquals(array(1, 2, 1, 2), E::cycle(array('a' => 1, 'b' => 2)), 4);
    }

    /** @covers YaLinqo\Enumerable::cycle
     */
    function testCycle_emptySource ()
    {
        $this->setExpectedException('InvalidArgumentException', E::ERROR_NO_ELEMENTS);
        E::cycle(array())->getIterator();
    }

    /** @covers YaLinqo\Enumerable::emptyEnum
     */
    function testEmptyEnum ()
    {
        $this->assertEnumEquals(array(), E::emptyEnum());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_array ()
    {
        $this->assertEnumEquals(array(), E::from(array()));
        $this->assertEnumEquals(array(1, 2, 3), E::from(array(1, 2, 3)));
        $this->assertEnumEquals(array(1, 'a' => 2, 3), E::from(array(1, 'a' => 2, 3)));
        $this->assertEnumEquals(array(1, 'a' => 2, '3', true), E::from(array(1, 'a' => 2, '3', true)));

        $this->assertInstanceOf('ArrayIterator', E::from(array(1, 2, 3))->getIterator());
        $this->assertInstanceOf('ArrayIterator', E::from(E::from(array(1, 2, 3)))->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_enumerable ()
    {
        $this->assertEnumEquals(array(), E::from(E::emptyEnum()));
        $this->assertEnumEquals(array(1, 2), E::from(E::cycle(array(1, 2))), 2);
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_iterator ()
    {
        $this->assertEnumEquals(array(), E::from(new \EmptyIterator));
        $this->assertEnumEquals(array(1, 2), E::from(new \ArrayIterator(array(1, 2))));

        $this->assertInstanceOf('EmptyIterator', E::from(new \EmptyIterator)->getIterator());
        $this->assertInstanceOf('ArrayIterator', E::from(new \ArrayIterator(array(1, 2)))->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_iteratorAggregate ()
    {
        $this->assertEnumEquals(array(), E::from(new AggregateIteratorWrapper(new \EmptyIterator)));
        $this->assertEnumEquals(array(1, 2), E::from(new AggregateIteratorWrapper(new \ArrayIterator(array(1, 2)))));

        $this->assertInstanceOf('EmptyIterator', E::from(new AggregateIteratorWrapper(new \EmptyIterator))->getIterator());
        $this->assertInstanceOf('ArrayIterator', E::from(new AggregateIteratorWrapper(new \ArrayIterator(array(1, 2))))->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     * @dataProvider testFrom_WrongTypes_Data
     */
    function testFrom_wrongTypes ($source)
    {
        $this->setExpectedException('InvalidArgumentException');
        E::from($source)->getIterator();
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_wrongTypes_Data ()
    {
        return array(
            array(1),
            array(2.0),
            array('3'),
            array(true),
            array(null),
            array(function() { }),
            array(new \stdClass),
        );
    }

    /** @covers YaLinqo\Enumerable::generate
     */
    function testGenerate ()
    {
        $this->assertEnumEquals(array(2, 4, 6, 8), E::generate('$v+2'), 4);
        $this->assertEnumEquals(array(0, 2, 4, 6), E::generate('$v+2', 0), 4);
        $this->assertEnumEquals(array(1, 2, 4, 8), E::generate('$v*2', 1), 4);
        $this->assertEnumEquals(array(1, 2, 3, 4), E::generate('$k+2', 1, null, 0), 4);
        $this->assertEnumEquals(array(3 => 2, 6 => 4, 9 => 6), E::generate('$v+2', null, '$k+3', null), 3);
        $this->assertEnumEquals(array(2 => 1, 5 => 3, 8 => 5), E::generate('$v+2', 1, '$k+3', 2), 3);

        $this->assertEnumEquals(array(0, 1, 3, 6, 10, 15), E::generate('$k+$v', 0, null, 0)->skip(1)->toValues(), 6); // partial sums
        $this->assertEnumEquals(array(1, 1, 2, 3, 5, 8), E::generate('array($v[1], $v[0]+$v[1])', array(0, 1))->select('$v[1]'), 6); // fibonacci
        $this->assertEnumEquals(array(1, 1, 2, 3, 5, 8), E::generate('$k+$v', 1, '$v', 1)->toKeys(), 6); // fibonacci
    }

    /** @covers YaLinqo\Enumerable::toInfinity
     */
    function testToInfinity ()
    {
        $this->assertEnumEquals(array(0, 1, 2, 3), E::toInfinity(), 4);
        $this->assertEnumEquals(array(3, 4, 5, 6), E::toInfinity(3), 4);
        $this->assertEnumEquals(array(3, 5, 7, 9), E::toInfinity(3, 2), 4);
        $this->assertEnumEquals(array(3, 1, -1, -3), E::toInfinity(3, -2), 4);
    }

    /** @covers YaLinqo\Enumerable::matches
     */
    function testMatches ()
    {
        $this->assertEnumEquals(array(), E::matches('abc def', '#\d+#'));
        $this->assertEnumEquals(array(array('123'), array('22')), E::matches('a123 22', '#\d+#'));
        $this->assertEnumEquals(array(array('123', '1'), array('22', '2')), E::matches('a123 22', '#(\d)\d*#'));
        $this->assertEnumEquals(array(array('123', '22'), array('1', '2')), E::matches('a123 22', '#(\d)\d*#', PREG_PATTERN_ORDER));
    }

    /** @covers YaLinqo\Enumerable::toNegativeInfinity
     */
    function testToNegativeInfinity ()
    {
        $this->assertEnumEquals(array(0, -1, -2, -3), E::toNegativeInfinity(), 4);
        $this->assertEnumEquals(array(-3, -4, -5, -6), E::toNegativeInfinity(-3), 4);
        $this->assertEnumEquals(array(-3, -5, -7, -9), E::toNegativeInfinity(-3, 2), 4);
        $this->assertEnumEquals(array(-3, -1, 1, 3), E::toNegativeInfinity(-3, -2), 4);
    }

    /** @covers YaLinqo\Enumerable::returnEnum
     */
    function testReturnEnum ()
    {
        $this->assertEnumEquals(array(1), E::returnEnum(1));
        $this->assertEnumEquals(array(true), E::returnEnum(true));
        $this->assertEnumEquals(array(null), E::returnEnum(null));
    }

    /** @covers YaLinqo\Enumerable::range
     */
    function testRange ()
    {
        $this->assertEnumEquals(array(), E::range(3, 0));
        $this->assertEnumEquals(array(), E::range(3, -1));
        $this->assertEnumEquals(array(3, 4, 5, 6), E::range(3, 4));
        $this->assertEnumEquals(array(3, 5, 7, 9), E::range(3, 4, 2));
        $this->assertEnumEquals(array(3, 1, -1, -3), E::range(3, 4, -2));
    }

    /** @covers YaLinqo\Enumerable::rangeDown
     */
    function testRangeDown ()
    {
        $this->assertEnumEquals(array(), E::rangeDown(-3, 0));
        $this->assertEnumEquals(array(), E::rangeDown(-3, -1));
        $this->assertEnumEquals(array(-3, -4, -5, -6), E::rangeDown(-3, 4));
        $this->assertEnumEquals(array(-3, -5, -7, -9), E::rangeDown(-3, 4, 2));
        $this->assertEnumEquals(array(-3, -1, 1, 3), E::rangeDown(-3, 4, -2));
    }

    /** @covers YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo ()
    {
        $this->assertEnumEquals(array(), E::rangeTo(3, 3));
        $this->assertEnumEquals(array(3, 4, 5, 6), E::rangeTo(3, 7));
        $this->assertEnumEquals(array(3, 5, 7, 9), E::rangeTo(3, 10, 2));
        $this->assertEnumEquals(array(-3, -4, -5, -6), E::rangeTo(-3, -7));
        $this->assertEnumEquals(array(-3, -5, -7, -9), E::rangeTo(-3, -10, 2));
    }

    /** @covers YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo_zeroStep ()
    {
        $this->setExpectedException('InvalidArgumentException', E::ERROR_STEP_NEGATIVE);
        E::rangeTo(3, 7, 0);
    }

    /** @covers YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo_negativeStep ()
    {
        $this->setExpectedException('InvalidArgumentException', E::ERROR_STEP_NEGATIVE);
        E::rangeTo(3, 7, -1);
    }

    /** @covers YaLinqo\Enumerable::repeat
     */
    function testRepeat ()
    {
        $this->assertEnumEquals(array(3, 3, 3, 3), E::repeat(3), 4);
        $this->assertEnumEquals(array(3, 3, 3, 3), E::repeat(3, 4));
        $this->assertEnumEquals(array(true, true), E::repeat(true, 2));
        $this->assertEnumEquals(array(), E::repeat(3, 0));
    }

    /** @covers YaLinqo\Enumerable::repeat
     */
    function testRepeat_negativeCount ()
    {
        $this->setExpectedException('InvalidArgumentException', E::ERROR_COUNT_LESS_THAN_ZERO);
        E::repeat(3, -2);
    }

    /** @covers YaLinqo\Enumerable::split
     */
    function testSplit ()
    {
        $this->assertEnumEquals(array('123 4 44'), E::split('123 4 44', '#, ?#'));
        $this->assertEnumEquals(array('123', '4', '44', ''), E::split('123,4, 44,', '#, ?#'));
        $this->assertEnumEquals(array('123', '4', '44'), E::split('123,4, 44,', '#, ?#', PREG_SPLIT_NO_EMPTY));
    }

    #endregion

    #region Projection and filtering

    /** @covers YaLinqo\Enumerable::ofType
     */
    function testOfType ()
    {
        $a = from(array(
            1, array(2), '6', function() { }, 1.2, null, new \stdClass, 3, 4.5, 'a', array(), from(array())
        ));
        $this->assertEnumValuesEquals(array(array(2), array()), $a->ofType('array'));
        $this->assertEnumValuesEquals(array(1, 3), $a->ofType('int'));
        $this->assertEnumValuesEquals(array(1, 3), $a->ofType('integer'));
        $this->assertEnumValuesEquals(array(1, 3), $a->ofType('long'));
        $this->assertEnumValuesEquals(array(function() { }), $a->ofType('callable'));
        $this->assertEnumValuesEquals(array(function() { }), $a->ofType('callback'));
        $this->assertEnumValuesEquals(array(1.2, 4.5), $a->ofType('float'));
        $this->assertEnumValuesEquals(array(1.2, 4.5), $a->ofType('real'));
        $this->assertEnumValuesEquals(array(1.2, 4.5), $a->ofType('double'));
        $this->assertEnumValuesEquals(array('6', 'a'), $a->ofType('string'));
        $this->assertEnumValuesEquals(array(null), $a->ofType('null'));
        $this->assertEnumValuesEquals(array(1, '6', 1.2, 3, 4.5), $a->ofType('numeric'));
        $this->assertEnumValuesEquals(array(1, '6', 1.2, 3, 4.5, 'a'), $a->ofType('scalar'));
        $this->assertEnumValuesEquals(array(function() { }, new \stdClass, from(array())), $a->ofType('object'));
        $this->assertEnumValuesEquals(array(from(array())), $a->ofType('YaLinqo\Enumerable'));
    }

    /** @covers YaLinqo\Enumerable::select
     */
    function testSelect ()
    {
        $this->assertEnumEquals(array(4, 5, 6), from(array(3, 4, 5))->select('$v+1'));
        $this->assertEnumEquals(array(3, 5, 7), from(array(3, 4, 5))->select('$v+$k'));
        $this->assertEnumEquals(array(1 => 3, 2 => 4, 3 => 5), from(array(3, 4, 5))->select('$v', '$k+1'));
        $this->assertEnumEquals(array(3 => 3, 5 => 3, 7 => 3), from(array(3, 4, 5))->select('$v-$k', '$v+$k'));
    }

    #endregion

    function assertEnumEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        $this->assertEquals($expected, $actual->take($maxLength)->toArray());
    }

    function assertEnumValuesEquals (array $expected, E $actual, $maxLength = PHP_INT_MAX)
    {
        $this->assertEquals($expected, $actual->take($maxLength)->toList());
    }
}
