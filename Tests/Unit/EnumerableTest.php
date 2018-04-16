<?php

namespace YaLinqo\Tests\Unit;

use YaLinqo\Enumerable as E, YaLinqo\Utils, YaLinqo\Functions, YaLinqo\Errors;
use YaLinqo\Tests\Stubs\AggregateIteratorWrapper, YaLinqo\Tests\Testing\TestCaseEnumerable;

/** @covers \YaLinqo\Enumerable
 */
class EnumerableTest extends TestCaseEnumerable
{
    #region Generation

    /** @covers \YaLinqo\Enumerable::cycle
     */
    function testCycle()
    {
        $this->assertEnumEquals(
            [ 1, 1, 1 ],
            E::cycle([ 1 ]),
            3);
        $this->assertEnumEquals(
            [ 1, 2, 3, 1, 2 ],
            E::cycle([ 1, 2, 3 ]),
            5);
        $this->assertEnumEquals(
            [ 1, 2, 1, 2 ],
            E::cycle([ 'a' => 1, 'b' => 2 ]),
            4);
    }

    /** @covers \YaLinqo\Enumerable::cycle
     */
    function testCycle_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        E::cycle([])->toArray();
    }

    /** @covers \YaLinqo\Enumerable::emptyEnum
     * @covers \YaLinqo\Enumerable::__construct
     * @covers \YaLinqo\Enumerable::getIterator
     */
    function testEmptyEnum()
    {
        $this->assertEnumEquals(
            [],
            E::emptyEnum());
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function testFrom_array()
    {
        // from (array)
        $this->assertEnumEquals(
            [],
            E::from([]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ]));
        $this->assertEnumEquals(
            [ 1, 'a' => 2, '3', true ],
            E::from([ 1, 'a' => 2, '3', true ]));

        // iterators must be ArrayIterators
        $this->assertInstanceOf('ArrayIterator',
            E::from([ 1, 2, 3 ])->getIterator());
        $this->assertInstanceOf('ArrayIterator',
            E::from(E::from([ 1, 2, 3 ]))->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function testFrom_enumerable()
    {
        // from (Enumerable)
        $this->assertEnumEquals(
            [],
            E::from(E::emptyEnum()));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from(E::cycle([ 1, 2 ])),
            2);
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function testFrom_iterator()
    {
        // from (Iterator)
        $this->assertEnumEquals(
            [],
            E::from(new \EmptyIterator));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from(new \ArrayIterator([ 1, 2 ])));

        // iterators must be the iterators passed
        $this->assertSame(
            $i = new \EmptyIterator,
            E::from($i)->getIterator());
        $this->assertSame(
            $i = new \ArrayIterator([ 1, 2 ]),
            E::from($i)->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function testFrom_iteratorAggregate()
    {
        // from (IteratorAggregate)
        $this->assertEnumEquals(
            [],
            E::from(new AggregateIteratorWrapper(new \EmptyIterator)));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from(new AggregateIteratorWrapper(new \ArrayIterator([ 1, 2 ]))));

        // iterators must be the iterators passed
        $this->assertSame(
            $i = new \EmptyIterator,
            E::from(new AggregateIteratorWrapper($i))->getIterator());
        $this->assertSame(
            $i = new \ArrayIterator([ 1, 2 ]),
            E::from(new AggregateIteratorWrapper($i))->getIterator());
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function testFrom_SimpleXMLElement()
    {
        // from (SimpleXMLElement)
        $this->assertEnumEquals(
            [],
            E::from(new \SimpleXMLElement('<r></r>')));
        $this->assertEnumValuesEquals(
            [ 'h', 'h', 'g' ],
            E::from(new \SimpleXMLElement('<r><h/><h/><g/></r>'))->select('$k'));
    }

    /** @covers \YaLinqo\Enumerable::from
     * @dataProvider dataProvider_testFrom_wrongTypes
     */
    function testFrom_wrongTypes($source)
    {
        // from (unsupported type)
        $this->setExpectedException('InvalidArgumentException');
        E::from($source)->getIterator();
    }

    /** @covers \YaLinqo\Enumerable::from
     */
    function dataProvider_testFrom_wrongTypes()
    {
        return [
            [ 1 ],
            [ 2.0 ],
            [ '3' ],
            [ true ],
            [ null ],
            [ function() { } ],
            [ new \stdClass ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::generate
     */
    function testGenerate()
    {
        // generate (funcValue)
        $this->assertEnumEquals(
            [ 0, 0, 0, 0 ],
            E::generate('$v==>0'),
            4);
        $this->assertEnumEquals(
            [ 2, 4, 6, 8 ],
            E::generate('$v+2'),
            4);

        // generate (funcValue, seedValue)
        $this->assertEnumEquals(
            [ 0, 2, 4, 6 ],
            E::generate('$v+2', 0),
            4);
        $this->assertEnumEquals(
            [ 1, 2, 4, 8 ],
            E::generate('$v*2', 1),
            4);

        // generate (funcValue, seedValue, funcKey, seedKey)
        $this->assertEnumEquals(
            [ 1, 2, 3, 4 ],
            E::generate('$k+2', 1, null, 0),
            4);
        $this->assertEnumEquals(
            [ 3 => 2, 6 => 4, 9 => 6 ],
            E::generate('$v+2', null, '$k+3', null),
            3);
        $this->assertEnumEquals(
            [ 2 => 1, 5 => 3, 8 => 5 ],
            E::generate('$v+2', 1, '$k+3', 2),
            3);
    }

    /** @covers \YaLinqo\Enumerable::generate
     */
    function testGenerate_meaningful()
    {
        // Partial sums
        $this->assertEnumEquals(
            [ 0, 1, 3, 6, 10, 15 ],
            E::generate('$k+$v', 0, null, 0)->skip(1)->toValues(),
            6);
        // Fibonacci
        $this->assertEnumEquals(
            [ 1, 1, 2, 3, 5, 8 ],
            E::generate('[ $v[1], $v[0]+$v[1] ]', [ 0, 1 ])->select('$v[1]'),
            6);
        // Fibonacci
        $this->assertEnumEquals(
            [ 1, 1, 2, 3, 5, 8 ],
            E::generate('$k+$v', 1, '$v', 1)->toKeys(),
            6);
    }

    /** @covers \YaLinqo\Enumerable::toInfinity
     */
    function testToInfinity()
    {
        // toInfinity ()
        $this->assertEnumEquals(
            [ 0, 1, 2, 3 ],
            E::toInfinity(),
            4);

        // toInfinity (start)
        $this->assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::toInfinity(3),
            4);

        // toInfinity (start, step)
        $this->assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::toInfinity(3, 2),
            4);
        $this->assertEnumEquals(
            [ 3, 1, -1, -3 ],
            E::toInfinity(3, -2),
            4);
    }

    /** @covers \YaLinqo\Enumerable::matches
     */
    function testMatches()
    {
        // without matches
        $this->assertEnumEquals(
            [],
            E::matches('abc def', '#\d+#'));
        // with matches, without groups
        $this->assertEnumEquals(
            [ [ '123' ], [ '22' ] ],
            E::matches('a123 22', '#\d+#'));
        // with matches, with groups
        $this->assertEnumEquals(
            [ [ '123', '1' ], [ '22', '2' ] ],
            E::matches('a123 22', '#(\d)\d*#'));
        // with matches, with groups, pattern order
        $this->assertEnumEquals(
            [ [ '123', '22' ], [ '1', '2' ] ],
            E::matches('a123 22', '#(\d)\d*#', PREG_PATTERN_ORDER));
    }

    /** @covers \YaLinqo\Enumerable::toNegativeInfinity
     */
    function testToNegativeInfinity()
    {
        // toNegativeInfinity ()
        $this->assertEnumEquals(
            [ 0, -1, -2, -3 ],
            E::toNegativeInfinity(),
            4);

        // toNegativeInfinity (start)
        $this->assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::toNegativeInfinity(-3),
            4);

        // toNegativeInfinity (start, step)
        $this->assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::toNegativeInfinity(-3, 2),
            4);
        $this->assertEnumEquals(
            [ -3, -1, 1, 3 ],
            E::toNegativeInfinity(-3, -2),
            4);
    }

    /** @covers \YaLinqo\Enumerable::returnEnum
     */
    function testReturnEnum()
    {
        $this->assertEnumEquals(
            [ 1 ],
            E::returnEnum(1));
        $this->assertEnumEquals(
            [ true ],
            E::returnEnum(true));
        $this->assertEnumEquals(
            [ null ],
            E::returnEnum(null));
    }

    /** @covers \YaLinqo\Enumerable::range
     */
    function testRange()
    {
        // range (start, count)
        $this->assertEnumEquals(
            [],
            E::range(3, 0));
        $this->assertEnumEquals(
            [],
            E::range(3, -1));
        $this->assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::range(3, 4));

        // range (start, count, step)
        $this->assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::range(3, 4, 2));
        $this->assertEnumEquals(
            [ 3, 1, -1, -3 ],
            E::range(3, 4, -2));
    }

    /** @covers \YaLinqo\Enumerable::rangeDown
     */
    function testRangeDown()
    {
        // rangeDown (start, count)
        $this->assertEnumEquals(
            [],
            E::rangeDown(-3, 0));
        $this->assertEnumEquals(
            [],
            E::rangeDown(-3, -1));
        $this->assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::rangeDown(-3, 4));

        // rangeDown (start, count, step)
        $this->assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::rangeDown(-3, 4, 2));
        $this->assertEnumEquals(
            [ -3, -1, 1, 3 ],
            E::rangeDown(-3, 4, -2));
    }

    /** @covers \YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo()
    {
        // rangeTo (start, end)
        $this->assertEnumEquals(
            [],
            E::rangeTo(3, 3));
        $this->assertEnumEquals(
            [ 3, 4, 5, 6 ],
            E::rangeTo(3, 7));

        // rangeTo (start, end, step)
        $this->assertEnumEquals(
            [ 3, 5, 7, 9 ],
            E::rangeTo(3, 10, 2));
        $this->assertEnumEquals(
            [ -3, -4, -5, -6 ],
            E::rangeTo(-3, -7));
        $this->assertEnumEquals(
            [ -3, -5, -7, -9 ],
            E::rangeTo(-3, -10, 2));
    }

    /** @covers \YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo_zeroStep()
    {
        $this->setExpectedException('InvalidArgumentException', Errors::STEP_NEGATIVE);
        E::rangeTo(3, 7, 0);
    }

    /** @covers \YaLinqo\Enumerable::rangeTo
     */
    function testRangeTo_negativeStep()
    {
        $this->setExpectedException('InvalidArgumentException', Errors::STEP_NEGATIVE);
        E::rangeTo(3, 7, -1);
    }

    /** @covers \YaLinqo\Enumerable::repeat
     */
    function testRepeat()
    {
        // repeat (element)
        $this->assertEnumEquals(
            [ 3, 3, 3, 3 ],
            E::repeat(3),
            4);

        // repeat (element, count)
        $this->assertEnumEquals(
            [ 3, 3, 3, 3 ],
            E::repeat(3, 4));
        $this->assertEnumEquals(
            [ true, true ],
            E::repeat(true, 2));
        $this->assertEnumEquals(
            [],
            E::repeat(3, 0));
    }

    /** @covers \YaLinqo\Enumerable::repeat
     */
    function testRepeat_negativeCount()
    {
        $this->setExpectedException('InvalidArgumentException', Errors::COUNT_LESS_THAN_ZERO);
        E::repeat(3, -2);
    }

    /** @covers \YaLinqo\Enumerable::split
     */
    function testSplit()
    {
        // without empty
        $this->assertEnumEquals(
            [ '123 4 44' ],
            E::split('123 4 44', '#, ?#'));
        // with empty
        $this->assertEnumEquals(
            [ '123', '4', '44', '' ],
            E::split('123,4, 44,', '#, ?#'));
        // with empty, empty skipped
        $this->assertEnumEquals(
            [ '123', '4', '44' ],
            E::split('123,4, 44,', '#, ?#', PREG_SPLIT_NO_EMPTY));
        // with empty, empty skipped, no results
        $this->assertEnumEquals(
            [],
            E::split(',', '#, ?#', PREG_SPLIT_NO_EMPTY));
    }

    #endregion

    #region Projection and filtering

    /** @covers \YaLinqo\Enumerable::cast
     */
    function testCast()
    {
        $c = new \stdClass;
        $c->c = 'd';
        $o = new \stdClass;
        $e = new \Exception;
        $v = function($v) {
            $r = new \stdClass;
            $r->scalar = $v;
            return $r;
        };

        // cast (empty)
        $this->assertEnumValuesEquals([], E::from([])->cast('array'));

        // cast (array)
        $sourceArrays = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ], $c ];
        $expectedArrays = [ [], [ 1 ], [ 1.2 ], [ '1.3' ], [ 'abc' ], [ true ], [ false ], [], [ 1 => 2 ], [ 'a' => 'b' ], [ 'c' => 'd' ] ];
        $this->assertEnumValuesEquals($expectedArrays, from($sourceArrays)->cast('array'));

        // cast (int)
        $sourceInts = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ] ];
        $expectedInts = [ 0, 1, 1, 1, 0, 1, 0, 0, 1, 1 ];
        $this->assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('int'));
        $this->assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('integer'));
        $this->assertEnumValuesEquals($expectedInts, from($sourceInts)->cast('long'));

        // cast (float)
        $sourceFloats = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ] ];
        $expectedFloats = [ 0, 1, 1.2, 1.3, 0, 1, 0, 0, 1, 1 ];
        $this->assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('float'));
        $this->assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('real'));
        $this->assertEnumValuesEquals($expectedFloats, from($sourceFloats)->cast('double'));

        // cast (null)
        $sourceNulls = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ], $c, $e ];
        $expectedNulls = [ null, null, null, null, null, null, null, null, null, null, null, null ];
        $this->assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('null'));
        $this->assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('unset'));

        // cast (null)
        $sourceNulls = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ], $c, $e ];
        $expectedNulls = [ null, null, null, null, null, null, null, null, null, null, null, null ];
        $this->assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('null'));
        $this->assertEnumValuesEquals($expectedNulls, from($sourceNulls)->cast('unset'));

        // cast (object)
        $sourceObjects = [ null, 1, 1.2, '1.3', 'abc', true, false, [], [ 1 => 2 ], [ 'a' => 'b' ], $c, $e ];
        $expectedObjects = [ $o, $v(1), $v(1.2), $v('1.3'), $v('abc'), $v(true), $v(false), $o, (object)[ 1 => 2 ], (object)[ 'a' => 'b' ], $c, $e ];
        $this->assertEnumValuesEquals($expectedObjects, from($sourceObjects)->cast('object'));

        // cast (string)
        $sourceObjects = [ null, 1, 1.2, '1.3', 'abc', true, false, $e ];
        $expectedObjects = [ '', '1', '1.2', '1.3', 'abc', '1', '', (string)$e ];
        $this->assertEnumValuesEquals($expectedObjects, from($sourceObjects)->cast('string'));
    }

    /** @covers \YaLinqo\Enumerable::cast
     */
    function testCast_notBuiltinType()
    {
        $this->setExpectedException('\InvalidArgumentException', Errors::UNSUPPORTED_BUILTIN_TYPE);
        from([ 0 ])->cast('unsupported');
    }

    /** @covers \YaLinqo\Enumerable::ofType
     */
    function testOfType()
    {
        $f = function() { };
        $a = from([
            1, [ 2 ], '6', $f, 1.2, null, new \stdClass, 3, 4.5, 'ab', [], new \Exception
        ]);

        // ofType (empty)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->ofType('array'));

        // ofType (array)
        $this->assertEnumValuesEquals(
            [ [ 2 ], [] ],
            $a->ofType('array'));

        // ofType (int)
        $this->assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('int'));
        $this->assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('integer'));
        $this->assertEnumValuesEquals(
            [ 1, 3 ],
            $a->ofType('long'));

        // ofType (callable)
        $this->assertEnumValuesEquals(
            [ $f ],
            $a->ofType('callable'));
        $this->assertEnumValuesEquals(
            [ $f ],
            $a->ofType('callback'));

        // ofType (float)
        $this->assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('float'));
        $this->assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('real'));
        $this->assertEnumValuesEquals(
            [ 1.2, 4.5 ],
            $a->ofType('double'));

        // ofType (string)
        $this->assertEnumValuesEquals(
            [ '6', 'ab' ],
            $a->ofType('string'));

        // ofType (null)
        $this->assertEnumValuesEquals(
            [ null ],
            $a->ofType('null'));

        // ofType (numeric)
        $this->assertEnumValuesEquals(
            [ 1, '6', 1.2, 3, 4.5 ],
            $a->ofType('numeric'));

        // ofType (scalar)
        $this->assertEnumValuesEquals(
            [ 1, '6', 1.2, 3, 4.5, 'ab' ],
            $a->ofType('scalar'));

        // ofType (object)
        $this->assertEnumValuesEquals(
            [ $f, new \stdClass, new \Exception ],
            $a->ofType('object'));

        // ofType (Exception)
        $this->assertEnumValuesEquals(
            [ new \Exception ],
            $a->ofType('Exception'));
    }

    /** @covers \YaLinqo\Enumerable::select
     */
    function testSelect()
    {
        // select (selectorValue)
        $this->assertEnumEquals(
            [],
            E::from([])->select('$v+1'));
        $this->assertEnumEquals(
            [ 4, 5, 6 ],
            E::from([ 3, 4, 5 ])->select('$v+1'));
        $this->assertEnumEquals(
            [ 3, 5, 7 ],
            E::from([ 3, 4, 5 ])->select('$v+$k'));

        // select (selectorValue, selectorKey)
        $this->assertEnumEquals(
            [ 1 => 3, 2 => 4, 3 => 5 ],
            E::from([ 3, 4, 5 ])->select('$v', '$k+1'));
        $this->assertEnumEquals(
            [ 3 => 3, 5 => 3, 7 => 3 ],
            E::from([ 3, 4, 5 ])->select('$v-$k', '$v+$k'));
    }

    /** @covers \YaLinqo\Enumerable::selectMany
     */
    function testSelectMany()
    {
        // selectMany (collectionSelector)
        $this->assertEnumEquals(
            [ 1, 2, 3, 4 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany('$v'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ [ 1 ], [ 2 ], [ 3 ] ])->selectMany('$v'));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from([ [], [], [ 1, 2 ] ])->selectMany('$v'));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from([ [ 1, 2 ], [], [] ])->selectMany('$v'));
        $this->assertEnumEquals(
            [],
            E::from([ [], [] ])->selectMany('$v'));
        $this->assertEnumEquals(
            [],
            E::from([])->selectMany('$v'));

        // selectMany (collectionSelector, resultSelectorValue)
        $this->assertEnumEquals(
            [ 0, 0, 1, 1 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany('$v', '$k1'));
        $this->assertEnumEquals(
            [ 1, 3, 3, 5 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany('$v', '$v+$k2'));

        // selectMany (collectionSelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            [ '00' => 1, '01' => 2, '10' => 3, '11' => 4 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany('$v', null, '"$k1$k2"'));
        $this->assertEnumEquals(
            [ '00' => 1, '01' => 2, '10' => 4, '11' => 5 ],
            E::from([ [ 1, 2 ], [ 3, 4 ] ])->selectMany('$v', '$v+$k1', '"$k1$k2"'));
    }

    /** @covers \YaLinqo\Enumerable::where
     */
    function testWhere()
    {
        // where (predicate)
        $this->assertEnumEquals(
            [],
            E::from([])->where(Functions::$true));
        $this->assertEnumEquals(
            [],
            E::from([])->where(Functions::$false));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4 ],
            E::from([ 1, 2, 3, 4 ])->where(Functions::$true));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4 ])->where(Functions::$false));
        $this->assertEnumEquals(
            [ 2 => 3, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->where('$v>2'));
        $this->assertEnumEquals(
            [ 0 => '1', 1 => '2' ],
            E::from([ '1', '2', '3', '4' ])->where('$k<2'));
    }

    #endregion

    #region Ordering

    /** @covers \YaLinqo\Enumerable::orderByDir
     * @covers \YaLinqo\OrderedEnumerable
     */
    function testOrderByDir_asc()
    {
        // orderByDir (false)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderByDir(false));
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(false));

        // orderByDir (false, keySelector)
        $this->assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(false, '-$v'));
        $this->assertEnumValuesEquals(
            [ 2, 3, 1 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDir(false, '$k'));

        // orderByDir (false, keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            [ 2, 33, 111, 4444 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDir(false, null, $compareLen));
        $this->assertEnumValuesEquals(
            [ 33, 30, 999, 4444 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDir(false, '$v-33', $compareLen));
        $this->assertEnumValuesEquals(
            [ 2, 3, 9, 4 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDir(false, '$k', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            [ [ 0, 3 ], [ 2, 4 ], [ 1, 5 ] ],
            E::from([ 3, 5, 4 ])->orderByDir(false));
    }

    /** @covers \YaLinqo\Enumerable::orderByDir
     * @covers \YaLinqo\OrderedEnumerable
     */
    function testOrderByDir_desc()
    {
        // orderByDir (true)
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderByDir(true));
        $this->assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(true));

        // orderByDir (true, keySelector)
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDir(true, '-$v'));
        $this->assertEnumValuesEquals(
            [ 1, 3, 2 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDir(true, '$k'));

        // orderByDir (true, keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            [ 4444, 111, 33, 2 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDir(true, null, $compareLen));
        $this->assertEnumValuesEquals(
            [ 4444, 999, 30, 33 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDir(true, '$v-33', $compareLen));
        $this->assertEnumValuesEquals(
            [ 4, 9, 3, 2 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDir(true, '$k', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            [ [ 1, 5 ], [ 2, 4 ], [ 0, 3 ] ],
            from([ 3, 5, 4 ])->orderByDir(true));
    }

    /** @covers \YaLinqo\Enumerable::orderBy
     * @covers \YaLinqo\OrderedEnumerable
     */
    function testOrderBy()
    {
        // orderBy ()
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderBy());
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy());

        // orderBy (keySelector)
        $this->assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderBy('-$v'));
        $this->assertEnumValuesEquals(
            [ 2, 3, 1 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderBy('$k'));

        // orderBy (keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            [ 2, 33, 111, 4444 ],
            E::from([ 111, 2, 33, 4444 ])->orderBy(null, $compareLen));
        $this->assertEnumValuesEquals(
            [ 33, 30, 999, 4444 ],
            E::from([ 999, 30, 33, 4444 ])->orderBy('$v-33', $compareLen));
        $this->assertEnumValuesEquals(
            [ 2, 3, 9, 4 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderBy('$k', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            [ [ 0, 3 ], [ 2, 4 ], [ 1, 5 ] ],
            E::from([ 3, 5, 4 ])->orderBy());
    }

    /** @covers \YaLinqo\Enumerable::orderByDescending
     * @covers \YaLinqo\OrderedEnumerable
     */
    function testOrderByDescending()
    {
        // orderByDescending ()
        $this->assertEnumValuesEquals(
            [],
            E::from([])->orderByDescending());
        $this->assertEnumValuesEquals(
            [ 6, 5, 4, 3 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending());

        // orderByDescending (keySelector)
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending('-$v'));
        $this->assertEnumValuesEquals(
            [ 1, 3, 2 ],
            E::from([ 'c' => 1, 'a' => 2, 'b' => 3 ])->orderByDescending('$k'));

        // orderByDescending (keySelector, comparer)
        $compareLen = function($a, $b) { return strlen($a) - strlen($b); };
        $this->assertEnumValuesEquals(
            [ 4444, 111, 33, 2 ],
            E::from([ 111, 2, 33, 4444 ])->orderByDescending(null, $compareLen));
        $this->assertEnumValuesEquals(
            [ 4444, 999, 30, 33 ],
            E::from([ 999, 30, 33, 4444 ])->orderByDescending('$v-33', $compareLen));
        $this->assertEnumValuesEquals(
            [ 4, 9, 3, 2 ],
            E::from([ 999 => 9, 2 => 2, 33 => 3, 4444 => 4 ])->orderByDescending('$k', $compareLen));

        // both keys and values sorted
        $this->assertEnumOrderEquals(
            [ [ 1, 5 ], [ 2, 4 ], [ 0, 3 ] ],
            E::from([ 3, 5, 4 ])->orderByDescending());
    }

    /** @covers \YaLinqo\Enumerable::orderBy
     * @covers \YaLinqo\Enumerable::orderByDescending
     * @covers \YaLinqo\OrderedEnumerable
     */
    function testOrderBy_onlyLastConsidered()
    {
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy('-$v')->orderBy('$v'));
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderBy('-$v')->orderByDescending('-$v'));
        $this->assertEnumValuesEquals(
            [ 3, 4, 5, 6 ],
            E::from([ 4, 6, 5, 3 ])->orderByDescending('$v')->orderByDescending('-$v'));
    }

    #endregion

    #region Joining and grouping

    /** @covers \YaLinqo\Enumerable::groupJoin
     */
    function testGroupJoin()
    {
        // groupJoin (inner)
        $this->assertEnumEquals(
            [],
            E::from([])->groupJoin([]));
        $this->assertEnumEquals(
            [],
            E::from([])->groupJoin([ 6, 7, 8 ]));
        $this->assertEnumEquals(
            [ [ 3, [] ], [ 4, [] ], [ 5, [] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([]));
        $this->assertEnumEquals(
            [ [ 3, [ 6 ] ], [ 4, [ 7 ] ], [ 5, [ 8 ] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([ 6, 7, 8 ]));
        $this->assertEnumEquals(
            [ 'a' => [ 3, [ 6 ] ], 'b' => [ 4, [ 7 ] ], 'c' => [ 5, [ 8 ] ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->groupJoin([ 'a' => 6, 'b' => 7, 'c' => 8 ]));

        // groupJoin (inner, outerKeySelector)
        $this->assertEnumEquals(
            [ 3 => [ [ 3, 4 ], [ 6 ] ], 6 => [ [ 5, 6 ], [ 7 ] ], 9 => [ [ 7, 8 ], [ 8 ] ] ],
            E::from([ [ 3, 4 ], [ 5, 6 ], [ 7, 8 ] ])->groupJoin([ 3 => 6, 6 => 7, 9 => 8 ], '$v[0]+$k'));

        // groupJoin (inner, outerKeySelector, innerKeySelector)
        $this->assertEnumEquals(
            [ 4 => [ 1, [ 3 ] ], 6 => [ 2, [ 4 ] ], 8 => [ 3, [ 5 ] ] ],
            E::from([ 4 => 1, 6 => 2, 8 => 3 ])->groupJoin([ 1 => 3, 2 => 4, 3 => 5 ], null, '$v+$k'));
        $this->assertEnumEquals(
            [ 4 => [ 4, [ 3 ] ], 6 => [ 6, [ 4 ] ], 8 => [ 8, [ 5 ] ] ],
            E::from([ 3 => 4, 5 => 6, 7 => 8 ])->groupJoin([ 1 => 3, 2 => 4, 3 => 5 ], '$v', '$v+$k'));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        $this->assertEnumEquals(
            [ [ 3, [ 6 ] ], [ 5, [ 7 ] ], [ 7, [ 8 ] ] ],
            E::from([ 3, 4, 5 ])->groupJoin([ 6, 7, 8 ], null, null, '[ $v+$k, $e ]'));
        $this->assertEnumEquals(
            [ 1 => [ [ 6 ], 3 ], 2 => [ [ 7 ], 4 ], 3 => [ [ 8 ], 5 ] ],
            E::from([ 'a1' => 3, 'a2' => 4, 'a3' => 5 ])->groupJoin(
                [ '1b' => 6, '2b' => 7, '3b' => 8 ], '$k[1]', 'intval($k)', '[ $e, $v ]'));

        // groupJoin (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            [ 6 => [ 'a' ], 7 => [ 'b', 'c' ], 8 => [] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->groupJoin(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                '$v[0]', '$v[0]', '$e->select("\$v[1]")', '$v[1]'));
        $this->assertEnumEquals(
            [ [ 6, [ 'a' ] ], [ 7, [ 'b', 'c' ] ], [ 8, [] ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->groupJoin(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                '$v[0]', '$v[0]', '[ $v[1], $e->select("\$v[1]") ]', Functions::increment()));
    }

    /** @covers \YaLinqo\Enumerable::join
     */
    function testJoin()
    {
        // join (inner)
        $this->assertEnumEquals(
            [],
            E::from([])->join([]));
        $this->assertEnumEquals(
            [],
            E::from([])->join([ 6, 7, 8 ]));
        $this->assertEnumEquals(
            [],
            E::from([ 3, 4, 5 ])->join([]));
        $this->assertEnumEquals(
            [ [ 3, 6 ], [ 4, 7 ], [ 5, 8 ] ],
            E::from([ 3, 4, 5 ])->join([ 6, 7, 8 ]));
        $this->assertEnumEquals(
            [ 'a' => [ 3, 6 ], 'b' => [ 4, 7 ], 'c' => [ 5, 8 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->join([ 'a' => 6, 'b' => 7, 'c' => 8 ]));

        // join (inner, outerKeySelector)
        $this->assertEnumEquals(
            [ 3 => [ [ 3, 4 ], 6 ], 6 => [ [ 5, 6 ], 7 ], 9 => [ [ 7, 8 ], 8 ] ],
            E::from([ [ 3, 4 ], [ 5, 6 ], [ 7, 8 ] ])->join([ 3 => 6, 6 => 7, 9 => 8 ], '$v[0]+$k'));

        // join (inner, outerKeySelector, innerKeySelector)
        $this->assertEnumEquals(
            [ 4 => [ 1, 3 ], 6 => [ 2, 4 ], 8 => [ 3, 5 ] ],
            E::from([ 4 => 1, 6 => 2, 8 => 3 ])->join([ 1 => 3, 2 => 4, 3 => 5 ], null, '$v+$k'));
        $this->assertEnumEquals(
            [ 4 => [ 4, 3 ], 6 => [ 6, 4 ], 8 => [ 8, 5 ] ],
            E::from([ 3 => 4, 5 => 6, 7 => 8 ])->join([ 1 => 3, 2 => 4, 3 => 5 ], '$v', '$v+$k'));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue)
        $this->assertEnumEquals(
            [ [ 3, 6 ], [ 5, 7 ], [ 7, 8 ] ],
            E::from([ 3, 4, 5 ])->join([ 6, 7, 8 ], null, null, '[ $v1+$k, $v2 ]'));
        $this->assertEnumEquals(
            [ 1 => [ 6, 3 ], 2 => [ 7, 4 ], 3 => [ 8, 5 ] ],
            E::from([ 'a1' => 3, 'a2' => 4, 'a3' => 5 ])->join(
                [ '1b' => 6, '2b' => 7, '3b' => 8 ], '$k[1]', 'intval($k)', '[ $v2, $v1 ]'));

        // join (inner, outerKeySelector, innerKeySelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumOrderEquals(
            [ [ 6, 'a' ], [ 7, 'b' ], [ 7, 'c' ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->join(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                '$v[0]', '$v[0]', '$v2[1]', '$v1[1]'));
        $this->assertEnumEquals(
            [ [ 6, 'a' ], [ 7, 'b' ], [ 7, 'c' ] ],
            E::from([ [ 1, 6 ], [ 2, 7 ], [ 3, 8 ] ])->join(
                [ [ 1, 'a' ], [ 2, 'b' ], [ 2, 'c' ], [ 4, 'd' ] ],
                '$v[0]', '$v[0]', '[ $v1[1], $v2[1] ]', Functions::increment()));
    }

    /** @covers \YaLinqo\Enumerable::groupBy
     */
    function testGroupBy()
    {
        // groupBy ()
        $this->assertEnumEquals(
            [],
            E::from([])->groupBy());
        $this->assertEnumEquals(
            [ [ 3 ], [ 4 ], [ 5 ] ],
            E::from([ 3, 4, 5 ])->groupBy());
        $this->assertEnumEquals(
            [ 'a' => [ 3 ], 'b' => [ 4 ], 'c' => [ 5 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->groupBy());

        // groupBy (keySelector)
        $this->assertEnumEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('$v&1'));
        $this->assertEnumEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('!($k%2)'));

        // groupBy (keySelector, valueSelector)
        $this->assertEnumEquals(
            [ [ 3 ], [ 5 ], [ 7 ], [ 9 ], [ 11 ], [ 13 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, '$v+$k'));
        $this->assertEnumEquals(
            [ 0 => [ 5, 9, 13 ], 1 => [ 3, 7, 11 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('$v&1', '$v+$k'));
        $this->assertEnumEquals(
            [ 0 => [ 3, 3, 5 ], 1 => [ 3, 3, 4 ] ],
            E::from([ 3, 4, 5, 6, 8, 10 ])->groupBy('!($k%2)', '$v-$k'));

        // groupBy (keySelector, valueSelector, resultSelectorValue)
        $this->assertEnumEquals(
            [ [ 3, 0 ], [ 4, 1 ], [ 5, 2 ], [ 6, 3 ], [ 7, 4 ], [ 8, 5 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, null, '$e+[ 1=>$k ]'));
        $this->assertEnumEquals(
            [ 0 => [ 4, 6, 8, 'k' => 0 ], 1 => [ 3, 5, 7, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('$v&1', null, '$e+[ "k"=>$k ]'));
        $this->assertEnumEquals(
            [ [ 3, 0 ], [ 5, 1 ], [ 7, 2 ], [ 9, 3 ], [ 11, 4 ], [ 13, 5 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, '$v+$k', '$e+[ 1=>$k ]'));
        $this->assertEnumEquals(
            [ 0 => [ 5, 9, 13, 'k' => 0 ], 1 => [ 3, 7, 11, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('$v&1', '$v+$k', '$e+[ "k"=>$k ]'));

        // groupBy (keySelector, valueSelector, resultSelectorValue, resultSelectorKey)
        $this->assertEnumEquals(
            [ 3 => [ 3 ], 5 => [ 4 ], 7 => [ 5 ], 9 => [ 6 ], 11 => [ 7 ], 13 => [ 8 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy(null, null, null, '$e[0]+$k'));
        $this->assertEnumEquals(
            [ 5 => [ 5, 9, 13, 'k' => 0 ], 4 => [ 3, 7, 11, 'k' => 1 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->groupBy('$v&1', '$v+$k', '$e+[ "k"=>$k ]', '$e[0]+$k'));
    }

    /** @covers \YaLinqo\Enumerable::aggregate
     */
    function testAggregate()
    {
        // aggregate (func)
        $this->assertEquals(
            12,
            E::from([ 3, 4, 5 ])->aggregate('$a+$v'));
        $this->assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregate('$a+$v-$k'));

        // aggregate (func, seed)
        $this->assertEquals(
            10,
            E::from([])->aggregate('$a+$v', 10));
        $this->assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregate('$a+$v', 10));
        $this->assertEquals(
            6,
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregate('$a+$v-$k', 0));
    }

    /** @covers \YaLinqo\Enumerable::aggregate
     */
    function testAggregate_emptySourceNoSeed()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        E::from([])->aggregate('$a+$v');
    }

    /** @covers \YaLinqo\Enumerable::aggregateOrDefault
     */
    function testAggregateOrDefault()
    {
        // aggregate (func)
        $this->assertEquals(
            null,
            E::from([])->aggregateOrDefault('$a+$v'));
        $this->assertEquals(
            12,
            E::from([ 3, 4, 5 ])->aggregateOrDefault('$a+$v'));
        $this->assertEquals(
            9, // callback is not called on 1st element, just value is used
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregateOrDefault('$a+$v-$k'));

        // aggregate (func, seed)
        $this->assertEquals(
            null,
            E::from([])->aggregateOrDefault('$a+$v', 10));
        $this->assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregateOrDefault('$a+$v', 10));
        $this->assertEquals(
            6,
            E::from([ 3 => 3, 2 => 4, 1 => 5 ])->aggregateOrDefault('$a+$v-$k', 0));

        // aggregate (func, seed, default)
        $this->assertEquals(
            'empty',
            E::from([])->aggregateOrDefault('$a+$v', 10, 'empty'));
        $this->assertEquals(
            22,
            E::from([ 3, 4, 5 ])->aggregateOrDefault('$a+$v', 10, 'empty'));
    }

    /** @covers \YaLinqo\Enumerable::average
     */
    function testAverage()
    {
        // average ()
        $this->assertEquals(
            4,
            E::from([ 3, 4, 5 ])->average());
        $this->assertEquals(
            3,
            E::from([ 3, '4', '5', 0 ])->average());

        // average (selector)
        $this->assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2) / 3,
            E::from([ 3, 4, 5 ])->average('$v*2+$k'));
        $this->assertEquals(
            (3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3) / 4,
            E::from([ 3, '4', '5', 0 ])->average('$v*2+$k'));
    }

    /** @covers \YaLinqo\Enumerable::average
     */
    function testAverage_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        E::from([])->average();
    }

    /** @covers \YaLinqo\Enumerable::count
     */
    function testCount()
    {
        // count ()
        $this->assertEquals(
            0,
            E::from([])->count());
        $this->assertEquals(
            3,
            E::from([ 3, 4, 5 ])->count());
        $this->assertEquals(
            4,
            E::from([ 3, '4', '5', 0 ])->count());

        // count (predicate)
        $this->assertEquals(
            2,
            E::from([ 3, 4, 5 ])->count('$v*2+$k<10'));
        $this->assertEquals(
            3,
            E::from([ 3, '4', '5', 0 ])->count('$v*2+$k<10'));
    }

    /** @covers \YaLinqo\Enumerable::max
     */
    function testMax()
    {
        // max ()
        $this->assertEquals(
            5,
            E::from([ 3, 5, 4 ])->max());

        // max (selector)
        $this->assertEquals(
            5,
            E::from([ 3, 5, 4 ])->max('$v-$k*3+2')); // 5 4 0
        $this->assertEquals(
            5,
            E::from([ 3, '5', '4', 0 ])->max('$v-$k*3+2')); // 5 4 0 -7
    }

    /** @covers \YaLinqo\Enumerable::max
     */
    function testMax_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        E::from([])->max();
    }

    /** @covers \YaLinqo\Enumerable::maxBy
     */
    function testMaxBy()
    {
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };

        // max ()
        $this->assertEquals(
            3,
            E::from([ 2, 3, 5, 4 ])->maxBy($compare));

        // max (selector)
        $this->assertEquals(
            8,
            E::from([ 2, 0, 3, 5, 6 ])->maxBy($compare, '$v+$k')); // 2 1 5 8 10
        $this->assertEquals(
            7,
            E::from([ '5', 3, false, '4' ])->maxBy($compare, '$v+$k')); // 5 4 2 7
    }

    /** @covers \YaLinqo\Enumerable::maxBy
     */
    function testMaxBy_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from([])->maxBy($compare);
    }

    /** @covers \YaLinqo\Enumerable::min
     */
    function testMin()
    {
        // min ()
        $this->assertEquals(
            3,
            E::from([ 3, 5, 4 ])->min());

        // min (selector)
        $this->assertEquals(
            0,
            E::from([ 3, 5, 4 ])->min('$v-$k*3+2')); // 5 4 0
        $this->assertEquals(
            -7,
            E::from([ 3, '5', '4', false ])->min('$v-$k*3+2')); // 5 4 0 -7
    }

    /** @covers \YaLinqo\Enumerable::min
     */
    function testMin_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        E::from([])->min();
    }

    /** @covers \YaLinqo\Enumerable::minBy
     */
    function testMinBy()
    {
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };

        // min ()
        $this->assertEquals(
            4,
            E::from([ 2, 3, 5, 4 ])->minBy($compare));

        // min (selector)
        $this->assertEquals(
            1,
            E::from([ 2, 0, 3, 5, 6 ])->minBy($compare, '$v+$k')); // 2 1 5 8 10
        $this->assertEquals(
            4,
            E::from([ '5', 3, 0, '4' ])->minBy($compare, '$v+$k')); // 5 4 2 7
    }

    /** @covers \YaLinqo\Enumerable::minBy
     */
    function testMinBy_emptySource()
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_ELEMENTS);
        $compare = function($a, $b) { return strcmp($a * $a, $b * $b); };
        E::from([])->minBy($compare);
    }

    /** @covers \YaLinqo\Enumerable::sum
     */
    function testSum()
    {
        // sum ()
        $this->assertEquals(
            0,
            E::from([])->sum());
        $this->assertEquals(
            12,
            E::from([ 3, 4, 5 ])->sum());
        $this->assertEquals(
            12,
            E::from([ 3, '4', '5', false ])->sum());

        // sum (selector)
        $this->assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2,
            E::from([ 3, 4, 5 ])->sum('$v*2+$k'));
        $this->assertEquals(
            3 * 2 + 0 + 4 * 2 + 1 + 5 * 2 + 2 + 0 * 2 + 3,
            E::from([ 3, '4', '5', null ])->sum('$v*2+$k'));
    }

    /** @covers \YaLinqo\Enumerable::all
     */
    function testAll()
    {
        // all (predicate)
        $this->assertEquals(
            true,
            E::from([])->all('$v>0'));
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->all('$v>0'));
        $this->assertEquals(
            false,
            E::from([ 1, -2, 3 ])->all('$v>0'));
        $this->assertEquals(
            false,
            E::from([ -1, -2, -3 ])->all('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::any
     */
    function testAny_array()
    {
        // any ()
        $this->assertEquals(
            false,
            E::from([])->any());
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->any());

        // any (predicate)
        $this->assertEquals(
            false,
            E::from([])->any('$v>0'));
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->any('$v>0'));
        $this->assertEquals(
            true,
            E::from([ 1, -2, 3 ])->any('$v>0'));
        $this->assertEquals(
            false,
            E::from([ -1, -2, -3 ])->any('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::any
     */
    function testAny_fromEnumerable()
    {
        // any ()
        $this->assertEquals(
            false,
            E::from([])->select('$v')->any());
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->select('$v')->any());

        // any (predicate)
        $this->assertEquals(
            false,
            E::from([])->select('$v')->any('$v>0'));
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->select('$v')->any('$v>0'));
        $this->assertEquals(
            true,
            E::from([ 1, -2, 3 ])->select('$v')->any('$v>0'));
        $this->assertEquals(
            false,
            E::from([ -1, -2, -3 ])->select('$v')->any('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::append
     */
    function testAppend()
    {
        // append (value)
        $this->assertEnumEquals(
            [ null => 9 ],
            E::from([])->append(9));
        $this->assertEnumEquals(
            [ 0 => 1, 1 => 3, null => 9 ],
            E::from([ 1, 3 ])->append(9));

        // append (value, key)
        $this->assertEnumEquals(
            [ 2 => 9 ],
            E::from([])->append(9, 2));
        $this->assertEnumEquals(
            [ 0 => 1, 1 => 3, 8 => 9 ],
            E::from([ 1, 3 ])->append(9, 8));
    }

    /** @covers \YaLinqo\Enumerable::concat
     */
    function testConcat()
    {
        // concat ()
        $this->assertEnumEquals(
            [],
            E::from([])->concat([]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->concat([]));
        $this->assertEnumEquals(
            [ 1, 2, 3, 3 ],
            E::from([])->concat([ 1, 2, 3, 3 ]));
        $this->assertEnumOrderEquals(
            [ [ 0, 1 ], [ 1, 2 ], [ 2, 2 ], [ 0, 1 ], [ 1, 3 ] ],
            E::from([ 1, 2, 2 ])->concat([ 1, 3 ]));
    }

    /** @covers \YaLinqo\Enumerable::contains
     */
    function testContains()
    {
        // contains (value)
        $this->assertEquals(
            false,
            E::from([])->contains(2));
        $this->assertEquals(
            true,
            E::from([ 1, 2, 3 ])->contains(2));
        $this->assertEquals(
            false,
            E::from([ 1, 2, 3 ])->contains(4));
    }

    /** @covers \YaLinqo\Enumerable::distinct
     */
    function testDistinct()
    {
        // distinct ()
        $this->assertEnumEquals(
            [],
            E::from([])->distinct());
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->distinct());
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3, 1, 2 ])->distinct());

        // distinct (keySelector)
        $this->assertEnumEquals(
            [],
            E::from([])->distinct('$v*$k'));
        $this->assertEnumEquals(
            [ 3 => 1, 2 => 2, 1 => 5 ],
            E::from([ 3 => 1, 2 => 2, 1 => 5 ])->distinct('$v*$k'));
        $this->assertEnumEquals(
            [ 4 => 1, 1 => 3 ],
            E::from([ 4 => 1, 2 => 2, 1 => 3 ])->distinct('$v*$k'));
    }

    /** @covers \YaLinqo\Enumerable::except
     */
    function testExcept()
    {
        // except ()
        $this->assertEnumEquals(
            [],
            E::from([])->except([]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->except([]));
        $this->assertEnumEquals(
            [],
            E::from([])->except([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ 1, 2, 3 ]));

        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ '1', '2', '3' ]));
        $this->assertEnumEquals(
            [],
            E::from([ '1', '2', '3' ])->except([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [],
            E::from([ 1, '2', 3 ])->except([ '1', 2, '3' ]));

        $this->assertEnumEquals(
            [ 1 => 2, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3 ]));
        $this->assertEnumEquals(
            [ 1 => 2, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3, 5, 7 ]));

        // except (keySelector)
        $this->assertEnumEquals(
            [],
            E::from([])->except([], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->except([], '$k'));
        $this->assertEnumEquals(
            [],
            E::from([])->except([ 1, 2, 3 ], '$k'));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->except([ 1, 2, 3 ], '$k'));

        $this->assertEnumEquals(
            [ 2 => 3, 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3 ], '$k'));
        $this->assertEnumEquals(
            [ 3 => 4 ],
            E::from([ 1, 2, 3, 4 ])->except([ 1, 3, 5 ], '$k'));
    }

    /** @covers \YaLinqo\Enumerable::intersect
     */
    function testIntersect()
    {
        // intersect ()
        $this->assertEnumEquals(
            [],
            E::from([])->intersect([]));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->intersect([]));
        $this->assertEnumEquals(
            [],
            E::from([])->intersect([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ 1, 2, 3 ]));

        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ '1', '2', '3' ]));
        $this->assertEnumEquals(
            [ '1', '2', '3' ],
            E::from([ '1', '2', '3' ])->intersect([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [ 1, '2', 3 ],
            E::from([ 1, '2', 3 ])->intersect([ '1', 2, '3' ]));

        $this->assertEnumEquals(
            [ 0 => 1, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3 ]));
        $this->assertEnumEquals(
            [ 0 => 1, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3, 5, 7 ]));

        // intersect (keySelector)
        $this->assertEnumEquals(
            [],
            E::from([])->intersect([], '$k'));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3 ])->intersect([], '$k'));
        $this->assertEnumEquals(
            [],
            E::from([])->intersect([ 1, 2, 3 ], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->intersect([ 1, 2, 3 ], '$k'));

        $this->assertEnumEquals(
            [ 0 => 1, 1 => 2 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3 ], '$k'));
        $this->assertEnumEquals(
            [ 0 => 1, 1 => 2, 2 => 3 ],
            E::from([ 1, 2, 3, 4 ])->intersect([ 1, 3, 5 ], '$k'));
    }

    /** @covers \YaLinqo\Enumerable::prepend
     */
    function testPrepend()
    {
        // prepend (value)
        $this->assertEnumEquals(
            [ null => 9 ],
            E::from([])->prepend(9));
        $this->assertEnumEquals(
            [ null => 9, 0 => 1, 1 => 3 ],
            E::from([ 1, 3 ])->prepend(9));

        // prepend (value, key)
        $this->assertEnumEquals(
            [ 2 => 9 ],
            E::from([])->prepend(9, 2));
        $this->assertEnumEquals(
            [ 8 => 9, 0 => 1, 1 => 3 ],
            E::from([ 1, 3 ])->prepend(9, 8));
    }

    /** @covers \YaLinqo\Enumerable::union
     */
    function testUnion()
    {
        // union ()
        $this->assertEnumEquals(
            [],
            E::from([])->union([]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([])->union([ 1, 2, 3, 3 ]));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3, 3 ])->union([ 1, 2, 3 ]));

        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ '1', '2', '3' ]));
        $this->assertEnumEquals(
            [ '1', '2', '3' ],
            E::from([ '1', '2', '3' ])->union([ 1, 2, 3 ]));
        $this->assertEnumEquals(
            [ 1, '2', 3 ],
            E::from([ 1, '2', 3 ])->union([ '1', 2, '3' ]));

        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3 ]));
        $this->assertEnumOrderEquals(
            [ [ 0, 1 ], [ 1, 2 ], [ 2, 3 ], [ 2, 5 ] ],
            E::from([ 1, 2, 3 ])->union([ 1, 3, 5 ]));

        // union (keySelector)
        $this->assertEnumEquals(
            [],
            E::from([])->union([], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([])->union([ 1, 2, 3 ], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 2, 3 ], '$k'));

        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3 ], '$k'));
        $this->assertEnumEquals(
            [ 1, 2, 3, 7 ],
            E::from([ 1, 2, 3 ])->union([ 1, 3, 5, 7 ], '$k'));
    }

    #endregion

    #region Pagination

    /** @covers \YaLinqo\Enumerable::elementAt
     */
    function testElementAt_array()
    {
        // elementAt (key)
        $this->assertEquals(
            2,
            E::from([ 1, 2, 3 ])->elementAt(1));
        $this->assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->elementAt(4));
    }

    /** @covers \YaLinqo\Enumerable::elementAt
     */
    function testElementAt_enumerable()
    {
        // elementAt (key)
        $this->assertEquals(
            2,
            E::from([ 1, 2, 3 ])->select('$v')->elementAt(1));
        $this->assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->select('$v')->elementAt(4));
    }

    /** @covers \YaLinqo\Enumerable::elementAt
     * @dataProvider dataProvider_testElementAt_noKey
     * @param E $enum
     * @param mixed $key
     */
    function testElementAt_noKey($enum, $key)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_KEY);
        $enum->elementAt($key);
    }

    function dataProvider_testElementAt_noKey()
    {
        return [
            // array source
            [ E::from([]), 1 ],
            [ E::from([ 1, 2, 3 ]), 4 ],
            [ E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ]), 0 ],
            // Enumerable source
            [ E::from([])->select('$v'), 1 ],
            [ E::from([ 1, 2, 3 ])->select('$v'), 4 ],
            [ E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->select('$v'), 0 ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::elementAtOrDefault
     */
    function testElementAtOrDefault_array()
    {
        // elementAtOrDefault (key)
        $this->assertEquals(
            null,
            E::from([])->elementAtOrDefault(1));
        $this->assertEquals(
            2,
            E::from([ 1, 2, 3 ])->elementAtOrDefault(1));
        $this->assertEquals(
            null,
            E::from([ 1, 2, 3 ])->elementAtOrDefault(4));
        $this->assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->elementAtOrDefault(4));
        $this->assertEquals(
            null,
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->elementAtOrDefault(0));
    }

    /** @covers \YaLinqo\Enumerable::elementAtOrDefault
     */
    function testElementAtOrDefault_enumerable()
    {
        // elementAtOrDefault (key)
        $this->assertEquals(
            null,
            E::from([])->select('$v')->elementAtOrDefault(1));
        $this->assertEquals(
            2,
            E::from([ 1, 2, 3 ])->select('$v')->elementAtOrDefault(1));
        $this->assertEquals(
            null,
            E::from([ 1, 2, 3 ])->select('$v')->elementAtOrDefault(4));
        $this->assertEquals(
            2,
            E::from([ 3 => 1, 2, 'a' => 3 ])->select('$v')->elementAtOrDefault(4));
        $this->assertEquals(
            null,
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3 ])->select('$v')->elementAtOrDefault(0));
    }

    /** @covers \YaLinqo\Enumerable::first
     */
    function testFirst()
    {
        // first ()
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->first());
        $this->assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->first());

        // first (predicate)
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->first('$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, 3 ])->first('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::first
     * @dataProvider dataProvider_testFirst_noMatches
     */
    function testFirst_noMatches($source, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_MATCHES);
        E::from($source)->first($predicate);
    }

    function dataProvider_testFirst_noMatches()
    {
        return [
            // first ()
            [ [], null ],
            // first (predicate)
            [ [], '$v>0' ],
            [ [ -1, -2, -3 ], '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::firstOrDefault
     */
    function testFirstOrDefault()
    {
        // firstOrDefault ()
        $this->assertEquals(
            null,
            E::from([])->firstOrDefault());
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault());
        $this->assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrDefault());

        // firstOrDefault (default)
        $this->assertEquals(
            'a',
            E::from([])->firstOrDefault('a'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault('a'));
        $this->assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrDefault('a'));

        // firstOrDefault (default, predicate)
        $this->assertEquals(
            'a',
            E::from([])->firstOrDefault('a', '$v>0'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrDefault('a', '$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, 3 ])->firstOrDefault('a', '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->firstOrDefault('a', '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::firstOrFallback
     */
    function testFirstOrFallback()
    {
        $fallback = function() { return 'a'; };

        // firstOrFallback (fallback)
        $this->assertEquals(
            'a',
            E::from([])->firstOrFallback($fallback));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrFallback($fallback));
        $this->assertEquals(
            1,
            E::from([ 3 => 1, 2, 'a' => 3 ])->firstOrFallback($fallback));

        // firstOrFallback (fallback, predicate)
        $this->assertEquals(
            'a',
            E::from([])->firstOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->firstOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, 3 ])->firstOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->firstOrFallback($fallback, '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::last
     */
    function testLast()
    {
        // last ()
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->last());
        $this->assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->last());

        // last (predicate)
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->last('$v>0'));
        $this->assertEquals(
            2,
            E::from([ 1, 2, -3 ])->last('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::last
     * @dataProvider dataProvider_testLast_noMatches
     */
    function testLast_noMatches($source, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_MATCHES);
        E::from($source)->last($predicate);
    }

    function dataProvider_testLast_noMatches()
    {
        return [
            // last ()
            [ [], null ],
            // last (predicate)
            [ [], '$v>0' ],
            [ [ -1, -2, -3 ], '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::lastOrDefault
     */
    function testLastOrDefault()
    {
        // lastOrDefault ()
        $this->assertEquals(
            null,
            E::from([])->lastOrDefault());
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault());
        $this->assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrDefault());

        // lastOrDefault (default)
        $this->assertEquals(
            'a',
            E::from([])->lastOrDefault('a'));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault('a'));
        $this->assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrDefault('a'));

        // lastOrDefault (default, predicate)
        $this->assertEquals(
            'a',
            E::from([])->lastOrDefault('a', '$v>0'));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrDefault('a', '$v>0'));
        $this->assertEquals(
            2,
            E::from([ 1, 2, -3 ])->lastOrDefault('a', '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->lastOrDefault('a', '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::lastOrFallback
     */
    function testLastOrFallback()
    {
        $fallback = function() { return 'a'; };

        // lastOrFallback (fallback)
        $this->assertEquals(
            'a',
            E::from([])->lastOrFallback($fallback));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrFallback($fallback));
        $this->assertEquals(
            3,
            E::from([ 3 => 1, 2, 'a' => 3 ])->lastOrFallback($fallback));

        // lastOrFallback (fallback, predicate)
        $this->assertEquals(
            'a',
            E::from([])->lastOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3 ])->lastOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            2,
            E::from([ 1, 2, -3 ])->lastOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->lastOrFallback($fallback, '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::single
     */
    function testSingle()
    {
        // single ()
        $this->assertEquals(
            2,
            E::from([ 2 ])->single());

        // single (predicate)
        $this->assertEquals(
            2,
            E::from([ -1, 2, -3 ])->single('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::single
     * @dataProvider dataProvider_testSingle_noMatches
     */
    function testSingle_noMatches($source, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::NO_MATCHES);
        E::from($source)->single($predicate);
    }

    function dataProvider_testSingle_noMatches()
    {
        return [
            // single ()
            [ [], null ],
            // single (predicate)
            [ [], '$v>0' ],
            [ [ -1, -2, -3 ], '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::single
     * @dataProvider dataProvider_testSingle_manyMatches
     */
    function testSingle_manyMatches($source, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::MANY_MATCHES);
        E::from($source)->single($predicate);
    }

    function dataProvider_testSingle_manyMatches()
    {
        return [
            // single ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // single (predicate)
            [ [ 1, 2, 3 ], '$v>0' ],
            [ [ 1, 2, -3 ], '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::singleOrDefault
     */
    function testSingleOrDefault()
    {
        // singleOrDefault ()
        $this->assertEquals(
            null,
            E::from([])->singleOrDefault());
        $this->assertEquals(
            2,
            E::from([ 2 ])->singleOrDefault());

        // singleOrDefault (default)
        $this->assertEquals(
            'a',
            E::from([])->singleOrDefault('a'));
        $this->assertEquals(
            2,
            E::from([ 2 ])->singleOrDefault('a'));

        // singleOrDefault (default, predicate)
        $this->assertEquals(
            'a',
            E::from([])->singleOrDefault('a', '$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, -3 ])->singleOrDefault('a', '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->singleOrDefault('a', '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::singleOrDefault
     * @dataProvider dataProvider_testSingleOrDefault_manyMatches
     */
    function testSingleOrDefault_manyMatches($source, $default, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::MANY_MATCHES);
        E::from($source)->singleOrDefault($default, $predicate);
    }

    function dataProvider_testSingleOrDefault_manyMatches()
    {
        return [
            // singleOrDefault ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // singleOrDefault (default)
            [ [ 1, 2, 3 ], 'a', null ],
            [ [ 3 => 1, 2, 'a' => 3 ], 'a', null ],
            // singleOrDefault (default, predicate)
            [ [ 1, 2, 3 ], 'a', '$v>0' ],
            [ [ 1, 2, -3 ], 'a', '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::singleOrFallback
     */
    function testSingleOrFallback()
    {
        $fallback = function() { return 'a'; };

        // singleOrFallback (fallback)
        $this->assertEquals(
            'a',
            E::from([])->singleOrFallback($fallback));
        $this->assertEquals(
            2,
            E::from([ 2 ])->singleOrFallback($fallback));

        // singleOrFallback (fallback, predicate)
        $this->assertEquals(
            'a',
            E::from([])->singleOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, -3 ])->singleOrFallback($fallback, '$v>0'));
        $this->assertEquals(
            'a',
            E::from([ -1, -2, -3 ])->singleOrFallback($fallback, '$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::singleOrFallback
     * @dataProvider dataProvider_testSingleOrFallback_manyMatches
     */
    function testSingleOrFallback_manyMatches($source, $fallback, $predicate)
    {
        $this->setExpectedException('UnexpectedValueException', Errors::MANY_MATCHES);
        E::from($source)->singleOrFallback($fallback, $predicate);
    }

    function dataProvider_testSingleOrFallback_manyMatches()
    {
        $fallback = function() { return 'a'; };

        return [
            // singleOrFallback ()
            [ [ 1, 2, 3 ], null, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], null, null ],
            // singleOrFallback (fallback)
            [ [ 1, 2, 3 ], $fallback, null ],
            [ [ 3 => 1, 2, 'a' => 3 ], $fallback, null ],
            // singleOrFallback (fallback, predicate)
            [ [ 1, 2, 3 ], $fallback, '$v>0' ],
            [ [ 1, 2, -3 ], $fallback, '$v>0' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::indexOf
     */
    function testIndexOf()
    {
        $i = function($v) { return $v; };

        // array.indexOf (value)
        $this->assertEquals(
            false,
            E::from([])->indexOf('a'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->indexOf(2));
        $this->assertEquals(
            false,
            E::from([ 1, 2, 3 ])->indexOf(4));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3, 2, 1 ])->indexOf(2));
        $this->assertEquals(
            4,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->indexOf(2));

        // iterator.indexOf (value)
        $this->assertEquals(
            false,
            E::from([])->select($i)->indexOf('a'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->select($i)->indexOf(2));
        $this->assertEquals(
            false,
            E::from([ 1, 2, 3 ])->select($i)->indexOf(4));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3, 2, 1 ])->select($i)->indexOf(2));
        $this->assertEquals(
            4,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->select($i)->indexOf(2));
    }

    /** @covers \YaLinqo\Enumerable::lastIndexOf
     */
    function testLastIndexOf()
    {
        // indexOf (value)
        $this->assertEquals(
            null,
            E::from([])->lastIndexOf('a'));
        $this->assertEquals(
            1,
            E::from([ 1, 2, 3 ])->lastIndexOf(2));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3, 2, 1 ])->lastIndexOf(2));
        $this->assertEquals(
            5,
            E::from([ 3 => 1, 2, 2, 'a' => 3 ])->lastIndexOf(2));
    }

    /** @covers \YaLinqo\Enumerable::findIndex
     */
    function testFindIndex()
    {
        $this->assertEquals(
            null,
            E::from([])->findIndex('$v>0'));
        $this->assertEquals(
            0,
            E::from([ 1, 2, 3, 4 ])->findIndex('$v>0'));
        $this->assertEquals(
            1,
            E::from([ -1, 2, 3, -4 ])->findIndex('$v>0'));
        $this->assertEquals(
            null,
            E::from([ -1, -2, -3, -4 ])->findIndex('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::findLastIndex
     */
    function testFindLastIndex()
    {
        $this->assertEquals(
            null,
            E::from([])->findLastIndex('$v>0'));
        $this->assertEquals(
            3,
            E::from([ 1, 2, 3, 4 ])->findLastIndex('$v>0'));
        $this->assertEquals(
            2,
            E::from([ -1, 2, 3, -4 ])->findLastIndex('$v>0'));
        $this->assertEquals(
            null,
            E::from([ -1, -2, -3, -4 ])->findLastIndex('$v>0'));
    }

    /** @covers \YaLinqo\Enumerable::skip
     */
    function testSkip()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->skip(-2));
        $this->assertEnumEquals(
            [],
            E::from([])->skip(0));
        $this->assertEnumEquals(
            [],
            E::from([])->skip(2));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(-2));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(0));
        $this->assertEnumEquals(
            [ 2 => 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skip(2));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skip(5));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skip(6));
        $this->assertEnumEquals(
            [ 'c' => 3, 'd' => 4, 'e' => 5 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->skip(2));
    }

    /** @covers \YaLinqo\Enumerable::skipWhile
     */
    function testSkipWhile()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->skipWhile('$v>2'));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile('$v<0'));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile('$k==-1'));
        $this->assertEnumEquals(
            [ 2 => 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile('$v+$k<4'));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->skipWhile('$v>0'));
        $this->assertEnumEquals(
            [ 'c' => 3, 'd' => 4, 'e' => 5 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->skipWhile('$k<"c"'));
    }

    /** @covers \YaLinqo\Enumerable::take
     */
    function testTake()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->take(-2));
        $this->assertEnumEquals(
            [],
            E::from([])->take(0));
        $this->assertEnumEquals(
            [],
            E::from([])->take(2));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->take(-2));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->take(0));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(2));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(5));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->take(6));
        $this->assertEnumEquals(
            [ 'a' => 1, 'b' => 2 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->take(2));
    }

    /** @covers \YaLinqo\Enumerable::takeWhile
     */
    function testTakeWhile()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->takeWhile('$v>2'));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile('$v<0'));
        $this->assertEnumEquals(
            [],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile('$k==-1'));
        $this->assertEnumEquals(
            [ 1, 2 ],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile('$v+$k<4'));
        $this->assertEnumEquals(
            [ 1, 2, 3, 4, 5 ],
            E::from([ 1, 2, 3, 4, 5 ])->takeWhile('$v>0'));
        $this->assertEnumEquals(
            [ 'a' => 1, 'b' => 2 ],
            E::from([ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ])->takeWhile('$k<"c"'));
    }

    #endregion

    #region Conversion

    /** @covers \YaLinqo\Enumerable::toArray
     */
    function testToArray_array()
    {
        $this->assertEquals(
            [],
            E::from([])->toArray());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toArray());
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toArray());
    }

    /** @covers \YaLinqo\Enumerable::toArray
     */
    function testToArray_enumerable()
    {
        $this->assertEquals(
            [],
            E::from([])->select('$v')->toArray());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->select('$v')->toArray());
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->select('$v')->toArray());
    }

    /** @covers \YaLinqo\Enumerable::toArrayDeep
     * @covers \YaLinqo\Enumerable::toArrayDeepProc
     */
    function testToArrayDeep()
    {
        $this->assertEquals(
            [],
            E::from([])->toArrayDeep());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toArrayDeep());
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toArrayDeep());
        $this->assertEquals(
            [ 1, 2, 6 => [ 7 => [ 'a' => 'a' ], [ 8 => 4, 5 ] ] ],
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toArrayDeep());
    }

    /** @covers \YaLinqo\Enumerable::toList
     */
    function testToList_array()
    {
        $this->assertEquals(
            [],
            E::from([])->toList());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toList());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toList());
    }

    /** @covers \YaLinqo\Enumerable::toList
     */
    function testToList_enumerable()
    {
        $this->assertEquals(
            [],
            E::from([])->select('$v')->toList());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->select('$v')->toList());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->select('$v')->toList());
    }

    /** @covers \YaLinqo\Enumerable::toListDeep
     * @covers \YaLinqo\Enumerable::toListDeepProc
     */
    function testToListDeep()
    {
        $this->assertEquals(
            [],
            E::from([])->toListDeep());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toListDeep());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toListDeep());
        $this->assertEquals(
            [ 1, 2, [ [ 'a' ], [ 4, 5 ] ] ],
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toListDeep());
    }

    /** @covers \YaLinqo\Enumerable::toDictionary
     */
    function testToDictionary()
    {
        // toDictionary ()
        $this->assertEquals(
            [],
            E::from([])->toDictionary());
        $this->assertEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toDictionary());
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary());

        // toDictionary (keySelector)
        $this->assertEquals(
            [],
            E::from([])->toDictionary('$v'));
        $this->assertEquals(
            [ 1 => 1, 2 => 2, 3 => 3 ],
            E::from([ 1, 2, 3 ])->toDictionary('$v'));
        $this->assertEquals(
            [ 1 => 1, 2 => 2, 3 => 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary('$v'));

        // toDictionary (keySelector, valueSelector)
        $this->assertEquals(
            [],
            E::from([])->toDictionary('$v', '$k'));
        $this->assertEquals(
            [ 1 => 0, 2 => 1, 3 => 2 ],
            E::from([ 1, 2, 3 ])->toDictionary('$v', '$k'));
        $this->assertEquals(
            [ 1 => 0, 2 => 'a', 3 => 1 ],
            E::from([ 1, 'a' => 2, 3 ])->toDictionary('$v', '$k'));
    }

    /** @covers \YaLinqo\Enumerable::toJSON
     */
    function testToJSON()
    {
        $this->assertEquals(
            '[]',
            E::from([])->toJSON());
        $this->assertEquals(
            '[1,2,3]',
            E::from([ 1, 2, 3 ])->toJSON());
        $this->assertEquals(
            '{"0":1,"a":2,"1":3}',
            E::from([ 1, 'a' => 2, 3 ])->toJSON());
        $this->assertEquals(
            '{"0":1,"1":2,"6":{"7":{"a":"a"},"8":{"8":4,"9":5}}}',
            E::from([ 1, 2, 6 => E::from([ 7 => [ 'a' => 'a' ], E::from([ 8 => 4, 5 ]) ]) ])->toJSON());
    }

    /** @covers \YaLinqo\Enumerable::toLookup
     */
    function testToLookup()
    {
        // toLookup ()
        $this->assertEquals(
            [],
            E::from([])->toLookup());
        $this->assertEquals(
            [ [ 3 ], [ 4 ], [ 5 ] ],
            E::from([ 3, 4, 5 ])->toLookup());
        $this->assertEquals(
            [ 'a' => [ 3 ], 'b' => [ 4 ], 'c' => [ 5 ] ],
            E::from([ 'a' => 3, 'b' => 4, 'c' => 5 ])->toLookup());

        // toLookup (keySelector)
        $this->assertEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup('$v&1'));
        $this->assertEquals(
            [ 0 => [ 4, 6, 8 ], 1 => [ 3, 5, 7 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup('!($k%2)'));

        // toLookup (keySelector, valueSelector)
        $this->assertEquals(
            [ [ 3 ], [ 5 ], [ 7 ], [ 9 ], [ 11 ], [ 13 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup(null, '$v+$k'));
        $this->assertEquals(
            [ 0 => [ 5, 9, 13 ], 1 => [ 3, 7, 11 ] ],
            E::from([ 3, 4, 5, 6, 7, 8 ])->toLookup('$v&1', '$v+$k'));
        $this->assertEquals(
            [ 0 => [ 3, 3, 5 ], 1 => [ 3, 3, 4 ] ],
            E::from([ 3, 4, 5, 6, 8, 10 ])->toLookup('!($k%2)', '$v-$k'));
    }

    /** @covers \YaLinqo\Enumerable::toKeys
     */
    function testToKeys()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->toKeys());
        $this->assertEnumEquals(
            [ 0, 1, 2 ],
            E::from([ 1, 2, 3 ])->toKeys());
        $this->assertEnumEquals(
            [ 0, 'a', 1 ],
            E::from([ 1, 'a' => 2, 3 ])->toKeys());
    }

    /** @covers \YaLinqo\Enumerable::toValues
     */
    function testToValues()
    {
        $this->assertEnumEquals(
            [],
            E::from([])->toValues());
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 2, 3 ])->toValues());
        $this->assertEnumEquals(
            [ 1, 2, 3 ],
            E::from([ 1, 'a' => 2, 3 ])->toValues());
    }

    /** @covers \YaLinqo\Enumerable::toObject
     */
    function testToObject()
    {
        // toObject
        $this->assertEquals(
            new \stdClass,
            E::from([])->toObject());
        $this->assertEquals(
            (object)[ 'a' => 1, 'b' => true, 'c' => 'd' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject());

        // toObject (propertySelector)
        $i = 0;
        $this->assertEquals(
            (object)[ 'prop1' => 1, 'prop2' => true, 'prop3' => 'd' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject(function() use (&$i) {
                $i++;
                return "prop$i";
            }));

        // toObject (valueSelector)
        $this->assertEquals(
            (object)[ 'propa1' => 'a=1', 'propb1' => 'b=1', 'propcd' => 'c=d' ],
            E::from([ 'a' => 1, 'b' => true, 'c' => 'd' ])->toObject('"prop$k$v"', '"$k=$v"'));
    }

    /** @covers \YaLinqo\Enumerable::toString
     */
    function testToString()
    {
        // toString ()
        $this->assertEquals(
            '',
            E::from([])->toString());
        $this->assertEquals(
            '123',
            E::from([ 1, 2, 3 ])->toString());
        $this->assertEquals(
            '123',
            E::from([ 1, 'a' => 2, 3 ])->toString());
        $this->assertEquals(
            '123',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select('$v[1]', '$v[0]')->toString());

        // toString (separator)
        $this->assertEquals(
            '',
            E::from([])->toString(', '));
        $this->assertEquals(
            '1, 2, 3',
            E::from([ 1, 2, 3 ])->toString(', '));
        $this->assertEquals(
            '1, 2, 3',
            E::from([ 1, 'a' => 2, 3 ])->toString(', '));
        $this->assertEquals(
            '1, 2, 3',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select('$v[1]', '$v[0]')->toString(', '));

        // toString (separator, selector)
        $this->assertEquals(
            '',
            E::from([])->toString(', ', '"$k=$v"'));
        $this->assertEquals(
            '0=1, 1=2, 2=3',
            E::from([ 1, 2, 3 ])->toString(', ', '"$k=$v"'));
        $this->assertEquals(
            '0=1, a=2, 1=3',
            E::from([ 1, 'a' => 2, 3 ])->toString(', ', '"$k=$v"'));
        $this->assertEquals(
            '0=1, 0=2, 1=3',
            E::from([ [ 0, 1 ], [ 0, 2 ], [ 1, 3 ] ])->select('$v[1]', '$v[0]')->toString(', ', '"$k=$v"'));
    }

    #endregion

    #region Actions

    /** @covers \YaLinqo\Enumerable::call
     */
    function testCall()
    {
        // call (action)
        $a = [];
        foreach (E::from([])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_) ;
        $this->assertEquals(
            [],
            $a);
        $a = [];
        foreach (E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_) ;
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            $a);
        $a = [];
        foreach (E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; }) as $_) break;
        $this->assertEquals(
            [ 1 ],
            $a);
        $a = [];
        E::from([ 1, 'a' => 2, 3 ])->call(function($v, $k) use (&$a) { $a[$k] = $v; });
        $this->assertEquals(
            [],
            $a);
    }

    /** @covers \YaLinqo\Enumerable::each
     */
    function testEach()
    {
        // call (action)
        $a = [];
        E::from([])->each(function($v, $k) use (&$a) { $a[$k] = $v; });
        $this->assertEquals(
            [],
            $a);
        $a = [];
        E::from([ 1, 'a' => 2, 3 ])->each(function($v, $k) use (&$a) { $a[$k] = $v; });
        $this->assertEquals(
            [ 1, 'a' => 2, 3 ],
            $a);
    }

    /** @covers \YaLinqo\Enumerable::write
     * @dataProvider dataProvider_testWrite
     */
    function testWrite($output, $source, $separator, $selector)
    {
        // toString ()
        $this->expectOutputString($output);
        E::from($source)->write($separator, $selector);
    }

    function dataProvider_testWrite()
    {
        return [
            // write ()
            [ '', [], '', null ],
            [ '123', [ 1, 2, 3 ], '', null ],
            [ '123', [ 1, 'a' => 2, 3 ], '', null ],
            // write (separator)
            [ '', [], ', ', null ],
            [ '1, 2, 3', [ 1, 2, 3 ], ', ', null ],
            [ '1, 2, 3', [ 1, 'a' => 2, 3 ], ', ', null ],
            // write (separator, selector)
            [ '', [], ', ', '"$k=$v"' ],
            [ '0=1, 1=2, 2=3', [ 1, 2, 3 ], ', ', '"$k=$v"' ],
            [ '0=1, a=2, 1=3', [ 1, 'a' => 2, 3 ], ', ', '"$k=$v"' ],
        ];
    }

    /** @covers \YaLinqo\Enumerable::writeLine
     * @dataProvider dataProvider_testWriteLine
     */
    function testWriteLine($output, $source, $selector)
    {
        // toString ()
        $this->expectOutputString($output);
        E::from($source)->writeLine($selector);
    }

    function dataProvider_testWriteLine()
    {
        return [
            // writeLine ()
            [ "", [], null ],
            [ "1\n2\n3\n", [ 1, 2, 3 ], null ],
            [ "1\n2\n3\n", [ 1, 'a' => 2, 3 ], null ],
            // writeLine (selector)
            [ "", [], '"$k=$v"' ],
            [ "0=1\n1=2\n2=3\n", [ 1, 2, 3 ], '"$k=$v"' ],
            [ "0=1\na=2\n1=3\n", [ 1, 'a' => 2, 3 ], '"$k=$v"' ],
        ];
    }

    #endregion
}
