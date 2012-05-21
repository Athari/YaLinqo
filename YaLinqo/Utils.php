<?php

namespace YaLinqo;
use YaLinqo;

class Utils
{
    /**
     * @param Closure|array|string $closure
     * @param string $closureArgs
     * @param Closure|boolean|null $default
     * @throws \InvalidArgumentException Both closure and default are null.
     * @return Closure|array|string|null
     */
    public static function createLambda ($closure, $closureArgs, $default = null)
    {
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
                $args = '$' . str_replace(',', '=null,$', $closureArgs) . '=null';
                $code = trim($closure, " \r\n\t");
                if (strlen($code) > 0 && $code[0] != '{')
                    $code = "return {$code};";
                return create_function($args, $code);
            }
        }
        if (is_callable($closure)) {
            return $closure;
        }
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
