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
                throw new \InvalidArgumentException;
            return $default; /*Functions::$identity*/
        }
        if ($closure instanceof \Closure)
            return $closure;
        if (is_callable($closure))
            return $closure;
        /*return function() use($closure)
          { return call_user_func_array($closure, func_get_args()); };*/
        if ($default === null)
            throw new \InvalidArgumentException;
        return $default;
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

    /**
     * @param \Iterator $enum
     * @return bool
     */
    public static function next (\Iterator $enum)
    {
        $valid = $enum->valid();
        $key = null;
        $value = null;
        if ($valid) {
            $enum->next();
        }
        return $valid;
    }
}
