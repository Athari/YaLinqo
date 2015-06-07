<?php

namespace Tests\Unit;

class LinqTest extends \PHPUnit_Framework_TestCase
{
    function testFunctions ()
    {
        $this->assertInstanceOf('YaLinqo\Enumerable', from(new \EmptyIterator));
    }
}
