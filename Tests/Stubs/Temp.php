<?php

namespace YaLinqo\Tests\Stubs;

// @codeCoverageIgnoreStart

class Temp
{
    public $v;

    public function __construct ($v)
    {
        $this->v = $v;
    }

    public function foo ($a)
    {
        return $this->v + $a;
    }

    public static function bar ($a)
    {
        return $a;
    }
}
