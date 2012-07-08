<?php

require_once __DIR__ . '/../../YaLinqo/Linq.php';
use YaLinqo\Enumerable as E, YaLinqo\Utils, YaLinqo\Functions, Tests\Stubs\AggregateIteratorWrapper;

function a ()
{
    return func_get_args();
}

class EnumerableTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass ()
    {
        \PHPUnit_Framework_ComparatorFactory::getDefaultInstance()->register(new Tests\Testing\Comparator_ArrayEnumerable);
    }

    #region Generation

    /** @covers YaLinqo\Enumerable::cycle
     */
    function testCycle ()
    {
        $this->assertEnumEquals(
            array(1, 1, 1),
            E::cycle(array(1)),
            3);
        $this->assertEnumEquals(
            array(1, 2, 3, 1, 2),
            E::cycle(array(1, 2, 3)),
            5);
        $this->assertEnumEquals(
            array(1, 2, 1, 2),
            E::cycle(array('a' => 1, 'b' => 2)),
            4);
    }

    /** @covers YaLinqo\Enumerable::cycle
     */
    function testCycle_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        E::cycle(array())->getIterator();
    }

    /** @covers YaLinqo\Enumerable::emptyEnum
     * @covers YaLinqo\Enumerable::__construct
     * @covers YaLinqo\Enumerable::getIterator
     */
    function testEmptyEnum ()
    {
        $this->assertEnumEquals(
            array(),
            E::emptyEnum());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_array ()
    {
        // from (array)
        $this->assertEnumEquals(
            array(),
            E::from(array()));
        $this->assertEnumEquals(
            array(1, 2, 3),
            E::from(array(1, 2, 3)));
        $this->assertEnumEquals(
            array(1, 'a' => 2, 3),
            E::from(array(1, 'a' => 2, 3)));
        $this->assertEnumEquals(
            array(1, 'a' => 2, '3', true),
            E::from(array(1, 'a' => 2, '3', true)));

        // iterators must be ArrayIterators
        $this->assertInstanceOf('ArrayIterator',
            E::from(array(1, 2, 3))->getIterator());
        $this->assertInstanceOf('ArrayIterator',
            E::from(E::from(array(1, 2, 3)))->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_enumerable ()
    {
        // from (Enumerable)
        $this->assertEnumEquals(
            array(),
            E::from(E::emptyEnum()));
        $this->assertEnumEquals(
            array(1, 2),
            E::from(E::cycle(array(1, 2))),
            2);
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_iterator ()
    {
        // from (Iterator)
        $this->assertEnumEquals(
            array(),
            E::from(new \EmptyIterator));
        $this->assertEnumEquals(
            array(1, 2),
            E::from(new \ArrayIterator(array(1, 2))));

        // iterators must be the iterators passed
        $this->assertSame(
            $i = new \EmptyIterator,
            E::from($i)->getIterator());
        $this->assertSame(
            $i = new \ArrayIterator(array(1, 2)),
            E::from($i)->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     */
    function testFrom_iteratorAggregate ()
    {
        // from (IteratorAggregate)
        $this->assertEnumEquals(
            array(),
            E::from(new AggregateIteratorWrapper(new \EmptyIterator)));
        $this->assertEnumEquals(
            array(1, 2),
            E::from(new AggregateIteratorWrapper(new \ArrayIterator(array(1, 2)))));

        // iterators must be the iterators passed
        $this->assertSame(
            $i = new \EmptyIterator,
            E::from(new AggregateIteratorWrapper($i))->getIterator());
        $this->assertSame(
            $i = new \ArrayIterator(array(1, 2)),
            E::from(new AggregateIteratorWrapper($i))->getIterator());
    }

    /** @covers YaLinqo\Enumerable::from
     * @dataProvider testFrom_WrongTypes_Data
     */
    function testFrom_wrongTypes ($source)
    {
        // from (unsupported type)
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
        // generate (funcValue)
        $this->assertEnumEquals(
            array(0, 0, 0, 0),
            E::generate('$v==>0'),
            4);
        $this->assertEnumEquals(
            array(2, 4, 6, 8),
            E::generate('$v+2'),
            4);

        // generate (funcValue, seedValue)
        $this->assertEnumEquals(
            array(0, 2, 4, 6),
            E::generate('$v+2', 0),
            4);
        $this->assertEnumEquals(
            array(1, 2, 4, 8),
            E::generate('$v*2', 1),
            4);

        // generate (funcValue, seedValue, funcKey, seedKey)
        $this->assertEnumEquals(
            array(1, 2, 3, 4),
            E::generate('$k+2', 1, null, 0),
            4);
        $this->assertEnumEquals(
            array(3 => 2, 6 => 4, 9 => 6),
            E::generate('$v+2', null, '$k+3', null),
            3);
        $this->assertEnumEquals(
            array(2 => 1, 5 => 3, 8 => 5),
            E::generate('$v+2', 1, '$k+3', 2),
            3);
    }

    /** @covers YaLinqo\Enumerable::generate
     */
    function testGenerate_meaningful ()
    {
        // Partial sums
        $this->assertEnumEquals(
            array(0, 1, 3, 6, 10, 15),
            E::generate('$k+$v', 0, null, 0)->skip(1)->toValues(),
            6);
        // Fibonacci
        $this->assertEnumEquals(
            array(1, 1, 2, 3, 5, 8),
            E::generate('array($v[1], $v[0]+$v[1])', array(0, 1))->select('$v[1]'),
            6);
        // Fibonacci
        $this->assertEnumEquals(
            array(1, 1, 2, 3, 5, 8),
            E::generate('$k+$v', 1, '$v', 1)->toKeys(),
            6);
    }

    /** @covers YaLinqo\Enumerable::toInfinity
     */
    function testToInfinity ()
    {
        // toInfinity ()
        $this->assertEnumEquals(
            array(0, 1, 2, 3),
            E::toInfinity(),
            4);

        // toInfinity (start)
        $this->assertEnumEquals(
            array(3, 4, 5, 6),
            E::toInfinity(3),
            4);

        // toInfinity (start, step)
        $this->assertEnumEquals(
            array(3, 5, 7, 9),
            E::toInfinity(3, 2),
            4);
        $this->assertEnumEquals(
            array(3, 1, -1, -3),
            E::toInfinity(3, -2),
            4);
    }

    /** @covers YaLinqo\Enumerable::matches
     */
    function testMatches ()
    {
        // without matches
        $this->assertEnumEquals(
            array(),
            E::matches('abc def', '#\d+#'));
        // with matches, without groups
        $this->assertEnumEquals(
            array(a('123'), a('22')),
            E::matches('a123 22', '#\d+#'));
        // with matches, with groups
        $this->assertEnumEquals(
            array(a('123', '1'), a('22', '2')),
            E::matches('a123 22', '#(\d)\d*#'));
        // with matches, with groups, pattern order
        $this->assertEnumEquals(
            array(a('123', '22'), a('1', '2')),
            E::matches('a123 22', '#(\d)\d*#', PREG_PATTERN_ORDER));
    }

    /** @covers YaLinqo\Enumerable::toNegativeInfinity
     */
    function testToNegativeInfinity ()
    {
        // toNegativeInfinity ()
        $this->assertEnumEquals(
            array(0, -1, -2, -3),
            E::toNegativeInfinity(),
            4);

        // toNegativeInfinity (start)
        $this->assertEnumEquals(
            array(-3, -4, -5, -6),
            E::toNegativeInfinity(-3),
            4);

        // toNegativeInfinity (start, step)
        $this->assertEnumEquals(
            array(-3, -5, -7, -9),
            E::toNegativeInfinity(-3, 2),
            4);
        $this->assertEnumEquals(
            array(-3, -1, 1, 3),
            E::toNegativeInfinity(-3, -2),
            4);
    }

    /** @covers YaLinqo\Enumerable::returnEnum
     */
    function testReturnEnum ()
    {
        $this->assertEnumEquals(
            array(1),
            E::returnEnum(1));
        $this->assertEnumEquals(
            array(true),
            E::returnEnum(true));
        $this->assertEnumEquals(
            array(null),
            E::returnEnum(null));
    }

    /** @covers YaLinqo\Enumerable::range
     */
    function testRange ()
    {
        // range (start, count)
        $this->assertEnumEquals(
            array(),
            E::range(3, 0));
        $this->assertEnumEquals(
            array(),
            E::range(3, -1));
        $this->assertEnumEquals(
            array(3, 4, 5, 6),
            E::range(3, 4));

        // range (start, count, step)
        $this->assertEnumEquals(
            array(3, 5, 7, 9),
            E::range(3, 4, 2));
        $this->assertEnumEquals(
            array(3, 1, -1, -3),
            E::range(3, 4, -2));
    }

    /** @covers YaLinqo\Enumerable::rangeDown
     */
    function testRangeDown ()
    {
        // rangeDown (start, count)
        $this->assertEnumEquals(
            array(),
            E::rangeDown(-3, 0));
        $this->assertEnumEquals(
            array(),
            E::rangeDown(-3, -1));
        $this->assertEnumEquals(
            array(-3, -4, -5, -6),
            E::rangeDown(-3, 4));

        // rangeDown (start, count, step)
        $this->assertEnumEquals(
            array(-3, -5, -7, -9),
            E::rangeDown(-3, 4, 2));
        $this->assertEnumEquals(
            array(-3, -1, 1, 3),
            E::rangeDown(-3, 4, -2));
    }

    /** @covers YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo ()
    {
        // rangeTo (start, end)
        $this->assertEnumEquals(
            array(),
            E::rangeTo(3, 3));
        $this->assertEnumEquals(
            array(3, 4, 5, 6),
            E::rangeTo(3, 7));

        // rangeTo (start, end, step)
        $this->assertEnumEquals(
            array(3, 5, 7, 9),
            E::rangeTo(3, 10, 2));
        $this->assertEnumEquals(
            array(-3, -4, -5, -6),
            E::rangeTo(-3, -7));
        $this->assertEnumEquals(
            array(-3, -5, -7, -9),
            E::rangeTo(-3, -10, 2));
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
        // repeat (element)
        $this->assertEnumEquals(
            array(3, 3, 3, 3),
            E::repeat(3),
            4);

        // repeat (element, count)
        $this->assertEnumEquals(
            array(3, 3, 3, 3),
            E::repeat(3, 4));
        $this->assertEnumEquals(
            array(true, true),
            E::repeat(true, 2));
        $this->assertEnumEquals(
            array(),
            E::repeat(3, 0));
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
        // without empty
        $this->assertEnumEquals(
            array('123 4 44'),
            E::split('123 4 44', '#, ?#'));
        // with empty
        $this->assertEnumEquals(
            array('123', '4', '44', ''),
            E::split('123,4, 44,', '#, ?#'));
        // with empty, empty skipped
        $this->assertEnumEquals(
            array('123', '4', '44'),
            E::split('123,4, 44,', '#, ?#', PREG_SPLIT_NO_EMPTY));
        // with empty, empty skipped, no results
        $this->assertEnumEquals(
            array(),
            E::split(',', '#, ?#', PREG_SPLIT_NO_EMPTY));
    }

    #endregion

    #region Projection and filtering

    /** @covers YaLinqo\Enumerable::ofType
     */
    function testOfType ()
    {
        $a = from(array(
            1, array(2), '6', function() { }, 1.2, null, new \stdClass, 3, 4.5, 'ab', array(), new \Exception
        ));

        // ofType (empty)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->ofType('array'));

        // ofType (array)
        $this->assertEnumValuesEquals(
            array(a(2), a()),
            $a->ofType('array'));

        // ofType (int)
        $this->assertEnumValuesEquals(
            array(1, 3),
            $a->ofType('int'));
        $this->assertEnumValuesEquals(
            array(1, 3),
            $a->ofType('integer'));
        $this->assertEnumValuesEquals(
            array(1, 3),
            $a->ofType('long'));

        // ofType (callable)
        $this->assertEnumValuesEquals(
            array(function() { }),
            $a->ofType('callable'));
        $this->assertEnumValuesEquals(
            array(function() { }),
            $a->ofType('callback'));

        // ofType (float)
        $this->assertEnumValuesEquals(
            array(1.2, 4.5),
            $a->ofType('float'));
        $this->assertEnumValuesEquals(
            array(1.2, 4.5),
            $a->ofType('real'));
        $this->assertEnumValuesEquals(
            array(1.2, 4.5),
            $a->ofType('double'));

        // ofType (string)
        $this->assertEnumValuesEquals(
            array('6', 'ab'),
            $a->ofType('string'));

        // ofType (null)
        $this->assertEnumValuesEquals(
            array(null),
            $a->ofType('null'));

        // ofType (numeric)
        $this->assertEnumValuesEquals(
            array(1, '6', 1.2, 3, 4.5),
            $a->ofType('numeric'));

        // ofType (scalar)
        $this->assertEnumValuesEquals(
            array(1, '6', 1.2, 3, 4.5, 'ab'),
            $a->ofType('scalar'));

        // ofType (object)
        $this->assertEnumValuesEquals(
            array(function() { }, new \stdClass, new \Exception),
            $a->ofType('object'));

        // ofType (Exception)
        $this->assertEnumValuesEquals(
            array(new \Exception),
            $a->ofType('Exception'));
    }

    /** @covers YaLinqo\Enumerable::select
     */
    function testSelect ()
    {
        // select (selectorValue)
        $this->assertEnumEquals(
            array(),
            E::from(array())->select('$v+1'));
        $this->assertEnumEquals(
            array(4, 5, 6),
            E::from(array(3, 4, 5))->select('$v+1'));
        $this->assertEnumEquals(
            array(3, 5, 7),
            E::from(array(3, 4, 5))->select('$v+$k'));

        // select (selectorValue, selectorKey)
        $this->assertEnumEquals(
            array(1 => 3, 2 => 4, 3 => 5),
            E::from(array(3, 4, 5))->select('$v', '$k+1'));
        $this->assertEnumEquals(
            array(3 => 3, 5 => 3, 7 => 3),
            E::from(array(3, 4, 5))->select('$v-$k', '$v+$k'));
    }

    /** @covers YaLinqo\Enumerable::selectMany
     */
    function testSelectMany ()
    {
        // selectMany (collectionSelector)
        $this->assertEnumEquals(
            array(1, 2, 3, 4),
            E::from(array(array(1, 2), array(3, 4)))->selectMany('$v'));
        $this->assertEnumEquals(
            array(1, 2, 3),
            E::from(array(array(1), array(2), array(3)))->selectMany('$v'));
        $this->assertEnumEquals(
            array(1, 2),
            E::from(array(array(), array(), array(1, 2)))->selectMany('$v'));
        $this->assertEnumEquals(
            array(1, 2),
            E::from(array(array(1, 2), array(), array()))->selectMany('$v'));
        $this->assertEnumEquals(
            array(),
            E::from(array(array(), array()))->selectMany('$v'));
        $this->assertEnumEquals(
            array(),
            E::from(array())->selectMany('$v'));

        // selectMany (collectionSelector, resultSelectorValue)
        $this->assertEnumEquals(
            array(0, 0, 1, 1),
            E::from(array(array(1, 2), array(3, 4)))->selectMany('$v', '$k1'));
        $this->assertEnumEquals(
            array(1, 3, 3, 5),
            E::from(array(array(1, 2), array(3, 4)))->selectMany('$v', '$v+$k2'));

        // selectMany (collectionSelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            array('00' => 1, '01' => 2, '10' => 3, '11' => 4),
            E::from(array(array(1, 2), array(3, 4)))->selectMany('$v', null, '"$k1$k2"'));
        $this->assertEnumEquals(
            array('00' => 1, '01' => 2, '10' => 4, '11' => 5),
            E::from(array(array(1, 2), array(3, 4)))->selectMany('$v', '$v+$k1', '"$k1$k2"'));
    }

    /** @covers YaLinqo\Enumerable::where
     */
    function testWhere ()
    {
        // where (predicate)
        $this->assertEnumEquals(
            array(),
            E::from(array())->where(Functions::$true));
        $this->assertEnumEquals(
            array(),
            E::from(array())->where(Functions::$false));
        $this->assertEnumEquals(
            array(1, 2, 3, 4),
            E::from(array(1, 2, 3, 4))->where(Functions::$true));
        $this->assertEnumEquals(
            array(),
            E::from(array(1, 2, 3, 4))->where(Functions::$false));
        $this->assertEnumEquals(
            array(2 => 3, 3 => 4),
            E::from(array(1, 2, 3, 4))->where('$v>2'));
        $this->assertEnumEquals(
            array(0 => '1', 1 => '2'),
            E::from(array('1', '2', '3', '4'))->where('$k<2'));
    }

    #endregion

    #region Ordering

    /** @covers YaLinqo\Enumerable::orderByDir
     */
    function testOrderByDir_asc ()
    {
        // orderByDir (false)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderByDir(false));
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderByDir(false));

        // orderByDir (false, keySelector)
        $this->assertEnumValuesEquals(
            array(6, 5, 4, 3),
            E::from(array(4, 6, 5, 3))->orderByDir(false, '-$v'));

        // orderByDir (false, keySelector, comparer)
        $compareLen = function ($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            array(2, 33, 111, 4444),
            E::from(array(111, 2, 33, 4444))->orderByDir(false, null, $compareLen));
        $this->assertEnumValuesEquals(
            array(33, 3, 999, 4444),
            E::from(array(999, 3, 33, 4444))->orderByDir(false, '$v-33', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            array(a(0, 3), a(2, 4), a(1, 5)),
            E::from(array(3, 5, 4))->orderByDir(false));
    }

    /** @covers YaLinqo\Enumerable::orderByDir
     */
    function testOrderByDir_desc ()
    {
        // orderByDir (true)
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderByDir(true));
        $this->assertEnumValuesEquals(
            array(6, 5, 4, 3),
            E::from(array(4, 6, 5, 3))->orderByDir(true));

        // orderByDir (true, keySelector)
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderByDir(true, '-$v'));

        // orderByDir (true, keySelector, comparer)
        $compareLen = function ($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            array(4444, 111, 33, 2),
            E::from(array(111, 2, 33, 4444))->orderByDir(true, null, $compareLen));
        $this->assertEnumValuesEquals(
            array(4444, 999, 30, 33),
            E::from(array(999, 30, 33, 4444))->orderByDir(true, '$v-33', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            array(a(1, 5), a(2, 4), a(0, 3)),
            from(array(3, 5, 4))->orderByDir(true));
    }

    /** @covers YaLinqo\Enumerable::orderBy
     */
    function testOrderBy ()
    {
        // orderBy ()
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderBy());
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderBy());

        // orderBy (keySelector)
        $this->assertEnumValuesEquals(
            array(6, 5, 4, 3),
            E::from(array(4, 6, 5, 3))->orderBy('-$v'));

        // orderBy (keySelector, comparer)
        $compareLen = function ($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            array(2, 33, 111, 4444),
            E::from(array(111, 2, 33, 4444))->orderBy(null, $compareLen));
        $this->assertEnumValuesEquals(
            array(33, 30, 999, 4444),
            E::from(array(999, 30, 33, 4444))->orderBy('$v-33', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            array(a(0, 3), a(2, 4), a(1, 5)),
            E::from(array(3, 5, 4))->orderBy());
    }

    /** @covers YaLinqo\Enumerable::orderByDescending
     */
    function testOrderByDescending ()
    {
        // orderByDescending ()
        $this->assertEnumValuesEquals(
            array(),
            E::from(array())->orderByDescending());
        $this->assertEnumValuesEquals(
            array(6, 5, 4, 3),
            E::from(array(4, 6, 5, 3))->orderByDescending());

        // orderByDescending (keySelector)
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderByDescending('-$v'));

        // orderByDescending (keySelector, comparer)
        $compareLen = function ($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            array(4444, 111, 33, 2),
            E::from(array(111, 2, 33, 4444))->orderByDescending(null, $compareLen));
        $this->assertEnumValuesEquals(
            array(4444, 999, 30, 33),
            E::from(array(999, 30, 33, 4444))->orderByDescending('$v-33', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            array(a(1, 5), a(2, 4), a(0, 3)),
            E::from(array(3, 5, 4))->orderByDescending());
    }

    /** @covers YaLinqo\Enumerable::orderBy
     * @covers YaLinqo\Enumerable::orderByDescending
     */
    function testOrderBy_onlyLastConsidered ()
    {
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderBy('-$v')->orderBy('$v'));
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderBy('-$v')->orderByDescending('-$v'));
        $this->assertEnumValuesEquals(
            array(3, 4, 5, 6),
            E::from(array(4, 6, 5, 3))->orderByDescending('$v')->orderByDescending('-$v'));
    }

    #endregion

    #region Joining and grouping

    /** @covers YaLinqo\Enumerable::groupJoin
     */
    function testGroupJoin ()
    {
        // groupJoin (inner)
        $this->assertEnumEquals(
            array(),
            E::from(array())->groupJoin(array()));
        $this->assertEnumEquals(
            array(),
            E::from(array())->groupJoin(array(6, 7, 8)));
        $this->assertEnumEquals(
            array(a(3, a()), a(4, a()), a(5, a())),
            E::from(array(3, 4, 5))->groupJoin(array()));
        $this->assertEnumEquals(
            array(a(3, a(6)), a(4, a(7)), a(5, a(8))),
            E::from(array(3, 4, 5))->groupJoin(array(6, 7, 8)));
        $this->assertEnumEquals(
            array('a' => a(3, a(6)), 'b' => a(4, a(7)), 'c' => a(5, a(8))),
            E::from(array('a' => 3, 'b' => 4, 'c' => 5))->groupJoin(array('a' => 6, 'b' => 7, 'c' => 8)));

        // groupJoin (inner, outerKeySelector)
        $this->assertEnumEquals(
            array(3 => a(a(3, 4), a(6)), 6 => a(a(5, 6), a(7)), 9 => a(a(7, 8), a(8))),
            E::from(array(a(3, 4), a(5, 6), a(7, 8)))->groupJoin(array(3 => 6, 6 => 7, 9 => 8), '$v[0]+$k'));

        // groupJoin (inner, outerKeySelector, innerKeySelector)
        $this->assertEnumEquals(
            array(4 => a(1, a(3)), 6 => a(2, a(4)), 8 => a(3, a(5))),
            E::from(array(4 => 1, 6 => 2, 8 => 3))->groupJoin(array(1 => 3, 2 => 4, 3 => 5), null, '$v+$k'));
        $this->assertEnumEquals(
            array(4 => a(4, a(3)), 6 => a(6, a(4)), 8 => a(8, a(5))),
            E::from(array(3 => 4, 5 => 6, 7 => 8))->groupJoin(array(1 => 3, 2 => 4, 3 => 5), '$v', '$v+$k'));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        $this->assertEnumEquals(
            array(a(3, a(6)), a(5, a(7)), a(7, a(8))),
            E::from(array(3, 4, 5))->groupJoin(array(6, 7, 8), null, null, 'array($v+$k, $e)'));
        $this->assertEnumEquals(
            array(1 => a(a(6), 3), 2 => a(a(7), 4), 3 => a(a(8), 5)),
            E::from(array('a1' => 3, 'a2' => 4, 'a3' => 5))->groupJoin(
                array('1b' => 6, '2b' => 7, '3b' => 8), '$k[1]', 'intval($k)', 'array($e, $v)'));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            array(6 => a('a'), 7 => a('b', 'c'), 8 => a()),
            E::from(array(a(1, 6), a(2, 7), a(3, 8)))->groupJoin(
                array(a(1, 'a'), a(2, 'b'), a(2, 'c'), a(4, 'd')),
                '$v[0]', '$v[0]', '$e->select("\$v[1]")', '$v[1]'));
        $this->assertEnumEquals(
            array(a(6, a('a')), a(7, a('b', 'c')), a(8, a())),
            E::from(array(a(1, 6), a(2, 7), a(3, 8)))->groupJoin(
                array(a(1, 'a'), a(2, 'b'), a(2, 'c'), a(4, 'd')),
                '$v[0]', '$v[0]', 'array($v[1], $e->select("\$v[1]"))', Functions::increment()));
    }

    /** @covers YaLinqo\Enumerable::join
     */
    function testJoin ()
    {
        // join (inner)
        $this->assertEnumEquals(
            array(),
            E::from(array())->join(array()));
        $this->assertEnumEquals(
            array(),
            E::from(array())->join(array(6, 7, 8)));
        $this->assertEnumEquals(
            array(),
            E::from(array(3, 4, 5))->join(array()));
        $this->assertEnumEquals(
            array(a(3, 6), a(4, 7), a(5, 8)),
            E::from(array(3, 4, 5))->join(array(6, 7, 8)));
        $this->assertEnumEquals(
            array('a' => a(3, 6), 'b' => a(4, 7), 'c' => a(5, 8)),
            E::from(array('a' => 3, 'b' => 4, 'c' => 5))->join(array('a' => 6, 'b' => 7, 'c' => 8)));

        // join (inner, outerKeySelector)
        $this->assertEnumEquals(
            array(3 => a(a(3, 4), 6), 6 => a(a(5, 6), 7), 9 => a(a(7, 8), 8)),
            E::from(array(a(3, 4), a(5, 6), a(7, 8)))->join(array(3 => 6, 6 => 7, 9 => 8), '$v[0]+$k'));

        // join (inner, outerKeySelector, innerKeySelector)
        $this->assertEnumEquals(
            array(4 => a(1, 3), 6 => a(2, 4), 8 => a(3, 5)),
            E::from(array(4 => 1, 6 => 2, 8 => 3))->join(array(1 => 3, 2 => 4, 3 => 5), null, '$v+$k'));
        $this->assertEnumEquals(
            array(4 => a(4, 3), 6 => a(6, 4), 8 => a(8, 5)),
            E::from(array(3 => 4, 5 => 6, 7 => 8))->join(array(1 => 3, 2 => 4, 3 => 5), '$v', '$v+$k'));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        $this->assertEnumEquals(
            array(a(3, 6), a(5, 7), a(7, 8)),
            E::from(array(3, 4, 5))->join(array(6, 7, 8), null, null, 'array($v1+$k, $v2)'));
        $this->assertEnumEquals(
            array(1 => a(6, 3), 2 => a(7, 4), 3 => a(8, 5)),
            E::from(array('a1' => 3, 'a2' => 4, 'a3' => 5))->join(
                array('1b' => 6, '2b' => 7, '3b' => 8), '$k[1]', 'intval($k)', 'array($v2, $v1)'));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumOrderEquals(
            array(a(6, 'a'), a(7, 'b'), a(7, 'c')),
            E::from(array(a(1, 6), a(2, 7), a(3, 8)))->join(
                array(a(1, 'a'), a(2, 'b'), a(2, 'c'), a(4, 'd')),
                '$v[0]', '$v[0]', '$v2[1]', '$v1[1]'));
        $this->assertEnumEquals(
            array(a(6, 'a'), a(7, 'b'), a(7, 'c')),
            E::from(array(a(1, 6), a(2, 7), a(3, 8)))->join(
                array(a(1, 'a'), a(2, 'b'), a(2, 'c'), a(4, 'd')),
                '$v[0]', '$v[0]', 'array($v1[1], $v2[1])', Functions::increment()));
    }

    /** @covers YaLinqo\Enumerable::groupBy
     */
    function testGroupBy ()
    {
        // groupBy ()
        $this->assertEnumEquals(
            array(),
            E::from(array())->groupBy());
        $this->assertEnumEquals(
            array(a(3), a(4), a(5)),
            E::from(array(3, 4, 5))->groupBy());
        $this->assertEnumEquals(
            array('a' => a(3), 'b' => a(4), 'c' => a(5)),
            E::from(array('a' => 3, 'b' => 4, 'c' => 5))->groupBy());

        // groupBy (keySelector)
        $this->assertEnumEquals(
            array(0 => a(4, 6, 8), 1 => a(3, 5, 7)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('$v&1'));
        $this->assertEnumEquals(
            array(0 => a(4, 6, 8), 1 => a(3, 5, 7)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('!($k%2)'));

        // groupBy (keySelector, valueSelector)
        $this->assertEnumEquals(
            array(a(3), a(5), a(7), a(9), a(11), a(13)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy(null, '$v+$k'));
        $this->assertEnumEquals(
            array(0 => a(5, 9, 13), 1 => a(3, 7, 11)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('$v&1', '$v+$k'));
        $this->assertEnumEquals(
            array(0 => a(3, 3, 5), 1 => a(3, 3, 4)),
            E::from(array(3, 4, 5, 6, 8, 10))->groupBy('!($k%2)', '$v-$k'));

        // groupBy (keySelector, valueSelector, resultSelectorValue)
        $this->assertEnumEquals(
            array(a(3, 0), a(4, 1), a(5, 2), a(6, 3), a(7, 4), a(8, 5)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy(null, null, '$e+array(1=>$k)'));
        $this->assertEnumEquals(
            array(0 => array(4, 6, 8, 'k' => 0), 1 => array(3, 5, 7, 'k' => 1)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('$v&1', null, '$e+array("k"=>$k)'));
        $this->assertEnumEquals(
            array(a(3, 0), a(5, 1), a(7, 2), a(9, 3), a(11, 4), a(13, 5)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy(null, '$v+$k', '$e+array(1=>$k)'));
        $this->assertEnumEquals(
            array(0 => array(5, 9, 13, 'k' => 0), 1 => array(3, 7, 11, 'k' => 1)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('$v&1', '$v+$k', '$e+array("k"=>$k)'));

        // groupBy (keySelector, valueSelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            array(3 => a(3), 5 => a(4), 7 => a(5), 9 => a(6), 11 => a(7), 13 => a(8)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy(null, null, null, '$e[0]+$k'));
        $this->assertEnumEquals(
            array(5 => array(5, 9, 13, 'k' => 0), 4 => array(3, 7, 11, 'k' => 1)),
            E::from(array(3, 4, 5, 6, 7, 8))->groupBy('$v&1', '$v+$k', '$e+array("k"=>$k)', '$e[0]+$k'));
    }

    /** @covers YaLinqo\Enumerable::aggregate
     */
    function testAggregate ()
    {
        // aggregate (func)
        $this->assertEquals(
            12,
            E::from(array(3, 4, 5))->aggregate('$a+$v'));
        $this->assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from(array(3 => 3, 2 => 4, 1 => 5))->aggregate('$a+$v-$k'));

        // aggregate (func, seed)
        $this->assertEquals(
            10,
            E::from(array())->aggregate('$a+$v', 10));
        $this->assertEquals(
            22,
            E::from(array(3, 4, 5))->aggregate('$a+$v', 10));
        $this->assertEquals(
            6,
            E::from(array(3 => 3, 2 => 4, 1 => 5))->aggregate('$a+$v-$k', 0));
    }

    /** @covers YaLinqo\Enumerable::aggregate
     */
    function testAggregate_emptySourceNoSeed ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        E::from(array())->aggregate('$a+$v');
    }

    /** @covers YaLinqo\Enumerable::aggregateOrDefault
     */
    function testAggregateOrDefault ()
    {
        // aggregate (func)
        $this->assertEquals(
            null,
            E::from(array())->aggregateOrDefault('$a+$v'));
        $this->assertEquals(
            12,
            E::from(array(3, 4, 5))->aggregateOrDefault('$a+$v'));
        $this->assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from(array(3 => 3, 2 => 4, 1 => 5))->aggregateOrDefault('$a+$v-$k'));

        // aggregate (func, seed)
        $this->assertEquals(
            null,
            E::from(array())->aggregateOrDefault('$a+$v', 10));
        $this->assertEquals(
            22,
            E::from(array(3, 4, 5))->aggregateOrDefault('$a+$v', 10));
        $this->assertEquals(
            6,
            E::from(array(3 => 3, 2 => 4, 1 => 5))->aggregateOrDefault('$a+$v-$k', 0));

        // aggregate (func, seed, default)
        $this->assertEquals(
            'empty',
            E::from(array())->aggregateOrDefault('$a+$v', 10, 'empty'));
        $this->assertEquals(
            22,
            E::from(array(3, 4, 5))->aggregateOrDefault('$a+$v', 10, 'empty'));
    }

    /** @covers YaLinqo\Enumerable::average
     */
    function testAverage ()
    {
        // average ()
        $this->assertEquals(
            4,
            E::from(array(3, 4, 5))->average());
        $this->assertEquals(
            3,
            E::from(array(3, '4', '5b', 'a'))->average());

        // average (selector)
        $this->assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2) / 3,
            E::from(array(3, 4, 5))->average('$v*2+$k'));
        $this->assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3) / 4,
            E::from(array(3, '4', '5b', 'a'))->average('$v*2+$k'));
    }

    /** @covers YaLinqo\Enumerable::average
     */
    function testAverage_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        E::from(array())->average();
    }

    /** @covers YaLinqo\Enumerable::count
     */
    function testCount ()
    {
        // count ()
        $this->assertEquals(
            0,
            E::from(array())->count());
        $this->assertEquals(
            3,
            E::from(array(3, 4, 5))->count());
        $this->assertEquals(
            4,
            E::from(array(3, '4', '5b', 'a'))->count());

        // count (predicate)
        $this->assertEquals(
            2,
            E::from(array(3, 4, 5))->count('$v*2+$k<10'));
        $this->assertEquals(
            3,
            E::from(array(3, '4', '5b', 'a'))->count('$v*2+$k<10'));
    }

    /** @covers YaLinqo\Enumerable::max
     */
    function testMax ()
    {
        // max ()
        $this->assertEquals(
            5,
            E::from(array(3, 5, 4))->max());

        // max (selector)
        $this->assertEquals(
            5,
            E::from(array(3, 5, 4))->max('$v-$k*3+2')); // 5 4 0
        $this->assertEquals(
            5,
            E::from(array(3, '5b', '4', 'a'))->max('$v-$k*3+2')); // 5 4 0 -7
    }

    /** @covers YaLinqo\Enumerable::max
     */
    function testMax_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        E::from(array())->max();
    }

    /** @covers YaLinqo\Enumerable::maxBy
     */
    function testMaxBy ()
    {
        $compare = function ($a, $b) { return strcmp($a * $a, $b * $b); };

        // max ()
        $this->assertEquals(
            3,
            E::from(array(2, 3, 5, 4))->maxBy($compare));

        // max (selector)
        $this->assertEquals(
            8,
            E::from(array(2, 0, 3, 5, 6))->maxBy($compare, '$v+$k')); // 2 1 5 8 10
        $this->assertEquals(
            7,
            E::from(array('5b', 3, 'a', '4'))->maxBy($compare, '$v+$k')); // 5 4 2 7
    }

    /** @covers YaLinqo\Enumerable::maxBy
     */
    function testMaxBy_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        $compare = function ($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from(array())->maxBy($compare);
    }

    /** @covers YaLinqo\Enumerable::min
     */
    function testMin ()
    {
        // min ()
        $this->assertEquals(
            3,
            E::from(array(3, 5, 4))->min());

        // min (selector)
        $this->assertEquals(
            0,
            E::from(array(3, 5, 4))->min('$v-$k*3+2')); // 5 4 0
        $this->assertEquals(
            -7,
            E::from(array(3, '5b', '4', 'a'))->min('$v-$k*3+2')); // 5 4 0 -7
    }

    /** @covers YaLinqo\Enumerable::min
     */
    function testMin_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        E::from(array())->min();
    }

    /** @covers YaLinqo\Enumerable::minBy
     */
    function testMinBy ()
    {
        $compare = function ($a, $b) { return strcmp($a * $a, $b * $b); };

        // min ()
        $this->assertEquals(
            4,
            E::from(array(2, 3, 5, 4))->minBy($compare));

        // min (selector)
        $this->assertEquals(
            1,
            E::from(array(2, 0, 3, 5, 6))->minBy($compare, '$v+$k')); // 2 1 5 8 10
        $this->assertEquals(
            4,
            E::from(array('5b', 3, 'a', '4'))->minBy($compare, '$v+$k')); // 5 4 2 7
    }

    /** @covers YaLinqo\Enumerable::minBy
     */
    function testMinBy_emptySource ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_ELEMENTS);
        $compare = function ($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from(array())->minBy($compare);
    }

    /** @covers YaLinqo\Enumerable::sum
     */
    function testSum ()
    {
        // sum ()
        $this->assertEquals(
            0,
            E::from(array())->sum());
        $this->assertEquals(
            12,
            E::from(array(3, 4, 5))->sum());
        $this->assertEquals(
            12,
            E::from(array(3, '4', '5b', 'a'))->sum());

        // sum (selector)
        $this->assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2,
            E::from(array(3, 4, 5))->sum('$v*2+$k'));
        $this->assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3,
            E::from(array(3, '4', '5b', 'a'))->sum('$v*2+$k'));
    }

    /** @covers YaLinqo\Enumerable::all
     */
    function testAll ()
    {
        // all (predicate)
        $this->assertEquals(
            true,
            E::from(array())->all('$v>0'));
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->all('$v>0'));
        $this->assertEquals(
            false,
            E::from(array(1, -2, 3))->all('$v>0'));
        $this->assertEquals(
            false,
            E::from(array(-1, -2, -3))->all('$v>0'));
    }

    /** @covers YaLinqo\Enumerable::any
     */
    function testAny_fromArray ()
    {
        // any ()
        $this->assertEquals(
            false,
            E::from(array())->any());
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->any());

        // any (predicate)
        $this->assertEquals(
            false,
            E::from(array())->any('$v>0'));
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->any('$v>0'));
        $this->assertEquals(
            true,
            E::from(array(1, -2, 3))->any('$v>0'));
        $this->assertEquals(
            false,
            E::from(array(-1, -2, -3))->any('$v>0'));
    }

    /** @covers YaLinqo\Enumerable::any
     */
    function testAny_fromEnumerable ()
    {
        // any ()
        $this->assertEquals(
            false,
            E::from(array())->select('$v')->any());
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->select('$v')->any());

        // any (predicate)
        $this->assertEquals(
            false,
            E::from(array())->select('$v')->any('$v>0'));
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->select('$v')->any('$v>0'));
        $this->assertEquals(
            true,
            E::from(array(1, -2, 3))->select('$v')->any('$v>0'));
        $this->assertEquals(
            false,
            E::from(array(-1, -2, -3))->select('$v')->any('$v>0'));
    }

    /** @covers YaLinqo\Enumerable::contains
     */
    function testAny_contains ()
    {
        // contains (value)
        $this->assertEquals(
            false,
            E::from(array())->contains(2));
        $this->assertEquals(
            true,
            E::from(array(1, 2, 3))->contains(2));
        $this->assertEquals(
            false,
            E::from(array(1, 2, 3))->contains(4));
    }

    #endregion

    #region Pagination

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_array ()
    {
        // elementAt (key)
        $this->assertEquals(
            2,
            E::from(array(1, 2, 3))->elementAt(1));
        $this->assertEquals(
            2,
            E::from(array(3 => 1, 2, 'a' => 3))->elementAt(4));
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_emptyArray ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array())->elementAt(1);
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_indexedArray ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array(1, 2, 3))->elementAt(4);
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_assocArray ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array('a' => 1, 'b' => 2, 'c' => 3))->elementAt(0);
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_Enumerable ()
    {
        // elementAt (key)
        $this->assertEquals(
            2,
            E::from(array(1, 2, 3))->select('$v')->elementAt(1));
        $this->assertEquals(
            2,
            E::from(array(3 => 1, 2, 'a' => 3))->select('$v')->elementAt(4));
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_emptyEnumerable ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array())->select('$v')->elementAt(1);
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_indexedEnumerable ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array(1, 2, 3))->select('$v')->elementAt(4);
    }

    /** @covers YaLinqo\Enumerable::elementAt
     */
    function testElementAt_noKey_assocEnumerable ()
    {
        $this->setExpectedException('UnexpectedValueException', E::ERROR_NO_KEY);
        E::from(array('a' => 1, 'b' => 2, 'c' => 3))->select('$v')->elementAt(0);
    }

    /** @covers YaLinqo\Enumerable::elementAtOrDefault
     */
    function testElementAtOrDefault_array ()
    {
        // contains (key)
        $this->assertEquals(
            null,
            E::from(array())->elementAtOrDefault(1));
        $this->assertEquals(
            2,
            E::from(array(1, 2, 3))->elementAtOrDefault(1));
        $this->assertEquals(
            null,
            E::from(array(1, 2, 3))->elementAtOrDefault(4));
        $this->assertEquals(
            2,
            E::from(array(3 => 1, 2, 'a' => 3))->elementAtOrDefault(4));
        $this->assertEquals(
            null,
            E::from(array('a' => 1, 'b' => 2, 'c' => 3))->elementAtOrDefault(0));
    }

    /** @covers YaLinqo\Enumerable::elementAtOrDefault
     */
    function testElementAtOrDefault_Enumerable ()
    {
        // contains (key)
        $this->assertEquals(
            null,
            E::from(array())->select('$v')->elementAtOrDefault(1));
        $this->assertEquals(
            2,
            E::from(array(1, 2, 3))->select('$v')->elementAtOrDefault(1));
        $this->assertEquals(
            null,
            E::from(array(1, 2, 3))->select('$v')->elementAtOrDefault(4));
        $this->assertEquals(
            2,
            E::from(array(3 => 1, 2, 'a' => 3))->select('$v')->elementAtOrDefault(4));
        $this->assertEquals(
            null,
            E::from(array('a' => 1, 'b' => 2, 'c' => 3))->select('$v')->elementAtOrDefault(0));
    }

    #endregion

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
