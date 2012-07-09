<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../Testing/Common.php';
use YaLinqo\exceptions\NotSupportedException as E;

/** @covers YaLinqo\exceptions\NotSupportedException
 */
class NotSupportedExceptionTest extends \PHPUnit_Framework_TestCase
{
    function testConstructor ()
    {
        $e = new E();
        $this->assertEquals(E::ERROR_NOT_SUPPORTED, $e->getMessage());
        $e = new E('test');
        $this->assertEquals('test', $e->getMessage());
    }
}
