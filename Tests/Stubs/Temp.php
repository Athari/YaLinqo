<?php

namespace Tests\Stubs;

class Temp
{
    var $v;

    function __construct ($v)
    {
        $this->v = $v;
    }

    function foo ($a)
    {
        return $this->v + $a;
    }

    static function bar ($a)
    {
        return $a;
    }
}
