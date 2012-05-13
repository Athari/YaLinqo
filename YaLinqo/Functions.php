<?php

namespace YaLinqo;
use YaLinqo;

class Functions
{
    public static $identity;
    public static $true;
    public static $blank;
    public static $compare;

    public static function init ()
    {
        self::$identity = function ($x)
        {
            return $x;
        };

        self::$true = function ()
        {
            return true;
        };

        self::$blank = function ()
        {
        };

        self::$compare = function ($a, $b)
        {
            if ($a === $b)
                return 0;
            elseif ($a > $b)
                return 1;
            else
                return -1;
        };
    }
}

Functions::init();
