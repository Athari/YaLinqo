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
        if ($closure instanceof \Closure) {
            return $closure;
        }
        if (is_string($closure)) {
            $pos = strpos($closure, '=>');
            if ($pos !== false) {
                $args = trim(substr($closure, 0, $pos), "() \r\n\t");
                $code = trim(substr($closure, $pos + 2), " \r\n\t");
                if (strlen($code) > 0 && $code[0] != '{')
                    $code = "return {$code};";
                return create_function($args, $code);
            }
            $pos = strpos($closure, '$');
            if ($pos !== false) {
                $args = '$a1=null,$a2=null,$a3=null,$a4=null';
                $code = strtr($closure, array(
                    '$' => '$a1',
                    '$$' => '$a2',
                    '$$$' => '$a3',
                    '$$$$' => '$a4',
                ));
                if (strlen($code) > 0 && $code[0] != '{')
                    $code = "return {$code};";
                return create_function($args, $code);
            }
        }
        if (is_callable($closure)) {
            return $closure;
        }
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
