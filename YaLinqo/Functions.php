<?php

namespace YaLinqo;
use YaLinqo;

class Functions
{
    /** @var callback {(x) ==> x} */
    public static $identity;
    /** @var callback {(v, k) ==> k} */
    public static $key;
    /** @var callback {() ==> true} */
    public static $true;
    /** @var callback {() ==> {}} */
    public static $blank;
    /** @var callback */
    public static $compareStrict;
    /** @var callback */
    public static $compareLoose;

    public static function init ()
    {
        self::$identity = function ($x)
        {
            return $x;
        };

        /** @noinspection PhpUnusedParameterInspection */
        self::$key = function ($v, $k)
        {
            return $k;
        };

        self::$true = function ()
        {
            return true;
        };

        self::$blank = function ()
        {
        };

        self::$compareStrict = function ($a, $b)
        {
            if ($a === $b)
                return 0;
            elseif ($a > $b)
                return 1;
            else
                return -1;
        };

        self::$compareLoose = function ($a, $b)
        {
            if ($a == $b)
                return 0;
            elseif ($a > $b)
                return 1;
            else
                return -1;
        };
    }
}

Functions::init();
