<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../Testing/Common.php';
use YaLinqo\exceptions\NotImplementedException as E;

/** @covers YaLinqo\exceptions\NotImplementedException
 */
class NotImplementedExceptionTest extends \PHPUnit_Framework_TestCase
{
    function testConstructor ()
    {
        $e = new E();
        $this->assertEquals(E::ERROR_NOT_IMPLEMENTED, $e->getMessage());
        $e = new E('test');
        $this->assertEquals('test', $e->getMessage());
    }
}
