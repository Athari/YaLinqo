<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../YaLinqo/Linq.php';
use Tests\Stubs\Temp;
use YaLinqo\Utils as U;

class UtilsTest extends \PHPUnit_Framework_TestCase
{
    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_nullWithoutDefault ()
    {
        $this->setExpectedException('InvalidArgumentException', U::ERROR_CLOSURE_NULL);
        U::createLambda(null, 'a,b');
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_notCallable ()
    {
        $this->setExpectedException('InvalidArgumentException', U::ERROR_CLOSURE_NOT_CALLABLE);
        U::createLambda('function does not exist', 'a,b');
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_nullWithDefault ()
    {
        $f = U::createLambda(null, 'a,b', true);
        $this->assertSame(true, $f);
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_closure ()
    {
        $f = U::createLambda(function ($a, $b) { return $a + $b; }, 'a,b');
        $this->assertSame(5, $f(2, 3));
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableString ()
    {
        $f = U::createLambda('strlen', 's');
        $this->assertSame(3, $f('abc'));
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableArray ()
    {
        $o = new Temp(2);
        $f = U::createLambda([ $o, 'foo' ], 'a');
        $this->assertSame(5, $f(3));
        $f = U::createLambda([ 'Tests\Stubs\Temp', 'bar' ], 'a');
        $this->assertSame(4, $f(4));
        $f = U::createLambda([ get_class($o), 'bar' ], 'a');
        $this->assertSame(6, $f(6));
    }

    /** @covers YaLinqo\Utils::createLambda
     * @covers YaLinqo\Utils::createLambdaFromString
     */
    function testCreateLambda_lambdaString ()
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
    }
}
