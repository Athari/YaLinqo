<?php

namespace YaLinqo\Tests\Unit;

use YaLinqo\Utils as U, YaLinqo\Functions as F;
use YaLinqo\Tests\Stubs\Temp, YaLinqo\Tests\Testing\TestCaseEnumerable;

class UtilsTest extends TestCaseEnumerable
{
    /** @covers \YaLinqo\Utils::init
     */
    function testInit()
    {
        U::init();
        $this->assertInstanceOf(\Closure::class, U::createLambda('$v', 'v,k'));
        $this->assertInstanceOf(\Closure::class, U::createLambda('$k', 'v,k'));
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_nullWithoutDefault()
    {
        $this->setExpectedException('InvalidArgumentException', U::ERROR_CLOSURE_NULL);
        U::createLambda(null, 'a,b');
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_notCallable()
    {
        $this->setExpectedException('InvalidArgumentException', U::ERROR_CLOSURE_NOT_CALLABLE);
        U::createLambda('function does not exist', 'a,b');
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_nullWithDefault()
    {
        $f = U::createLambda(null, 'a,b', true);
        $this->assertSame(true, $f);
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_closure()
    {
        $f = U::createLambda(function($a, $b) { return $a + $b; }, 'a,b');
        $this->assertSame(5, $f(2, 3));
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableString()
    {
        $f = U::createLambda('strlen', 's');
        $this->assertSame(3, $f('abc'));
    }

    /** @covers \YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableArray()
    {
        $o = new Temp(2);
        $f = U::createLambda([ $o, 'foo' ], 'a');
        $this->assertSame(5, $f(3));
        $f = U::createLambda([ 'YaLinqo\Tests\Stubs\Temp', 'bar' ], 'a');
        $this->assertSame(4, $f(4));
        $f = U::createLambda([ get_class($o), 'bar' ], 'a');
        $this->assertSame(6, $f(6));
    }

    /** @covers \YaLinqo\Utils::createLambda
     * @covers \YaLinqo\Utils::createLambdaFromString
     */
    function testCreateLambda_lambdaString()
    {
        $f = U::createLambda('strcmp', 'a,b');
        $this->assertSame(0, $f('a', 'a'));

        $f = U::createLambda('$val+1', 'val');
        $this->assertSame(3, $f(2));
        $f = U::createLambda('$a+$b', 'a,b');
        $this->assertSame(5, $f(2, 3));
        $f = U::createLambda('$b+$c', 'a,b,c,d');
        $this->assertSame(5, $f(1, 2, 3, 4));
        $this->assertSame(5, $f(1, 2, 3, 4, 5));

        $f = U::createLambda('{ return $val+1; }', 'val');
        $this->assertSame(3, $f(2));
        $f = U::createLambda('{ return $a+$b; }', 'a,b');
        $this->assertSame(5, $f(2, 3));
        $f = U::createLambda('{ return $b+$c; }', 'a,b,c,d');
        $this->assertSame(5, $f(1, 2, 3, 4));
        $this->assertSame(5, $f(1, 2, 3, 4, 5));

        $f = U::createLambda('$val2 ==> $val2+1', 'val');
        $this->assertSame(3, $f(2));
        $f = U::createLambda('($v1, $v2) ==> $v1+$v2', 'a,b');
        $this->assertSame(5, $f(2, 3));
        $f = U::createLambda('($q, $w, $e, $r) ==> $w+$e', 'a,b,c,d');
        $this->assertSame(5, $f(1, 2, 3, 4));
        $this->assertSame(5, $f(1, 2, 3, 4, 5));

        $f = U::createLambda('$val2 ==> { return $val2+1; }', 'val');
        $this->assertSame(3, $f(2));
        $f = U::createLambda('($v1, $v2) ==> { return $v1+$v2; }', 'a,b');
        $this->assertSame(5, $f(2, 3));
        $f = U::createLambda('($q, $w, $e, $r) ==> { return $w+$e; }', 'a,b,c,d');
        $this->assertSame(5, $f(1, 2, 3, 4));
        $this->assertSame(5, $f(1, 2, 3, 4, 5));

        $f2 = U::createLambda('($q, $w, $e, $r) ==> { return $w+$e; }', 'a,b,c,d');
        $this->assertSame($f, $f2);
        $this->assertSame(5, $f2(1, 2, 3, 4));
        $this->assertSame(5, $f2(1, 2, 3, 4, 5));
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    function testCreateComparer_default()
    {
        $isReversed = null;
        $f = U::createComparer(null, SORT_ASC, $isReversed);
        $this->assertSame(F::$compareStrict, $f);
        $this->assertSame(false, $isReversed);

        $isReversed = null;
        $f = U::createComparer(null, SORT_DESC, $isReversed);
        $this->assertSame(F::$compareStrictReversed, $f);
        $this->assertSame(false, $isReversed);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    function testCreateComparer_sortFlags()
    {
        $isReversed = null;

        $f = U::createComparer(SORT_REGULAR, SORT_ASC, $isReversed);
        $this->assertSame(F::$compareStrict, $f);

        $f = U::createComparer(SORT_STRING, SORT_ASC, $isReversed);
        $this->assertSame('strcmp', $f);

        $f = U::createComparer(SORT_STRING | SORT_FLAG_CASE, SORT_ASC, $isReversed);
        $this->assertSame('strcasecmp', $f);

        $f = U::createComparer(SORT_LOCALE_STRING, SORT_ASC, $isReversed);
        $this->assertSame('strcoll', $f);

        $f = U::createComparer(SORT_NATURAL, SORT_ASC, $isReversed);
        $this->assertSame('strnatcmp', $f);

        $f = U::createComparer(SORT_NATURAL | SORT_FLAG_CASE, SORT_ASC, $isReversed);
        $this->assertSame('strnatcasecmp', $f);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    function testCreateComparer_sortFlags_numeric()
    {
        $isReversed = null;
        $f = U::createComparer(SORT_NUMERIC, SORT_ASC, $isReversed);
        $this->assertSame(F::$compareInt, $f);
        $this->assertSame(false, $isReversed);

        $isReversed = null;
        $f = U::createComparer(SORT_NUMERIC, SORT_DESC, $isReversed);
        $this->assertSame(F::$compareIntReversed, $f);
        $this->assertSame(false, $isReversed);
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    function testCreateComparer_sortFlags_closure()
    {
        $isReversed = null;
        $f = U::createComparer('$a-$b', SORT_ASC, $isReversed);
        $this->assertSame(7, $f(10, 3));
    }

    /** @covers \YaLinqo\Utils::createComparer
     */
    function testCreateComparer_sortFlags_invalid()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $isReversed = null;
        U::createComparer(666, SORT_ASC, $isReversed);
    }

    /** @covers \YaLinqo\Utils::lambdaToSortFlagsAndOrder
     */
    function testLambdaToSortFlagsAndOrder_sortFlags()
    {
        $order = SORT_ASC;
        $this->assertSame(null, U::lambdaToSortFlagsAndOrder('$v', $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_REGULAR, U::lambdaToSortFlagsAndOrder(null, $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_STRING, U::lambdaToSortFlagsAndOrder('strcmp', $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_STRING | SORT_FLAG_CASE, U::lambdaToSortFlagsAndOrder('strcasecmp', $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_LOCALE_STRING, U::lambdaToSortFlagsAndOrder('strcoll', $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_NATURAL, U::lambdaToSortFlagsAndOrder('strnatcmp', $order));

        $order = SORT_ASC;
        $this->assertSame(SORT_NATURAL | SORT_FLAG_CASE, U::lambdaToSortFlagsAndOrder('strnatcasecmp', $order));
    }

    /** @covers \YaLinqo\Utils::lambdaToSortFlagsAndOrder
     */
    function testLambdaToSortFlagsAndOrder_sortOrder()
    {
        $order = false;
        U::lambdaToSortFlagsAndOrder(null, $order);
        $this->assertSame(SORT_ASC, $order);

        $order = true;
        U::lambdaToSortFlagsAndOrder(null, $order);
        $this->assertSame(SORT_DESC, $order);

        $order = SORT_ASC;
        U::lambdaToSortFlagsAndOrder(null, $order);
        $this->assertSame(SORT_ASC, $order);

        $order = SORT_DESC;
        U::lambdaToSortFlagsAndOrder(null, $order);
        $this->assertSame(SORT_DESC, $order);

        $this->assertSame(1, U::lambdaToSortFlagsAndOrder(1, $order));
    }
}
