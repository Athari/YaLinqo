<?php

namespace YaLinqo;
use YaLinqo;

class Functions
{
    public static $identity;
    public static $true;
    public static $blank;

    public static function init ()
    {
        self::$identity = function ($x)
        { return $x; };
        self::$true = function ()
        { return true; };
        self::$blank = function ()
        { };
    }
}

Functions::init();
