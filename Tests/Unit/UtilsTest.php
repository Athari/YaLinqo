<?php

namespace Tests\Unit;

require_once __DIR__ . '/../Testing/Common.php';
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
        /** @var $f callback */
        $f = U::createLambda(function ($a, $b) { return $a + $b; }, 'a,b');
        $this->assertSame(5, $f(2, 3));
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableString ()
    {
        /** @var $f callback */
        $f = U::createLambda('strlen', 's');
        $this->assertSame(3, $f('abc'));
    }

    /** @covers YaLinqo\Utils::createLambda
     */
    function testCreateLambda_callableArray ()
    {
        $o = new \Tests\Stubs\Temp(2);
        /** @var $f callback */
        $f = U::createLambda(array($o, 'foo'), 'a');
        $this->assertSame(5, call_user_func($f, 3)); // PHP doesn't support $f() syntax for arrays yet
        $f = U::createLambda(array('Tests\Stubs\Temp', 'bar'), 'a');
        $this->assertSame(4, call_user_func($f, 4));
        $f = U::createLambda(array(get_class($o), 'bar'), 'a');
        $this->assertSame(6, call_user_func($f, 6));
    }

    /** @covers YaLinqo\Utils::createLambda
     * @covers YaLinqo\Utils::createLambdaFromString
     */
    function testCreateLambda_lambdaString ()
    {
        $o = new \Tests\Stubs\Temp(2);
        /** @var $f callback */
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
