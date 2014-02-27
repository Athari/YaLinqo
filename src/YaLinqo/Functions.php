<?php

namespace YaLinqo;
use YaLinqo;

class Functions
{
    /** @var callable {(x) ==> x} */
    public static $identity;
    /** @var callable {(v, k) ==> k} */
    public static $key;
    /** @var callable {(v, k) ==> v} */
    public static $value;
    /** @var callable {() ==> true} */
    public static $true;
    /** @var callable {() ==> false} */
    public static $false;
    /** @var callable {() ==> {}} */
    public static $blank;
    /** @var callable */
    public static $compareStrict;
    /** @var callable */
    public static $compareLoose;

    public static function init ()
    {
        self::$identity = function ($x) { return $x; };

        /** @noinspection PhpUnusedParameterInspection */
        self::$key = function ($v, $k) { return $k; };

        /** @noinspection PhpUnusedParameterInspection */
        self::$value = function ($v, $k) { return $v; };

        self::$true = function () { return true; };

        self::$false = function () { return false; };

        self::$blank = function () { };

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

    public static function increment ()
    {
        $i = 0;
        return function () use (&$i) { return $i++; };
    }
}

Functions::init();
