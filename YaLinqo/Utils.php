<?php

namespace YaLinqo;
use YaLinqo;

class Utils
{
    public static function createLambda ($closure, $default = null)
    {
        // TODO String lambda syntax: 'a => a*a'
        if ($closure === null) {
            if ($default === null)
                throw new \InvalidArgumentException('closure must not be null');
            return $default; /*Functions::$identity*/
        }
        if ($closure instanceof \Closure)
            return $closure;
        if (is_callable($closure))
            return $closure;
        /*return function() use($closure)
          { return call_user_func_array($closure, func_get_args()); };*/
        throw new \InvalidArgumentException('closure must be callable');
    }

    public static function compare ($a, $b)
    {
        if ($a === $b)
            return 0;
        elseif ($a > $b)
            return 1;
        else
            return -1;
    }
}
