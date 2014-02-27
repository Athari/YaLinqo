<?php

namespace YaLinqo;
use YaLinqo;

class Utils
{
    const ERROR_CLOSURE_NULL = 'closure must not be null.';
    const ERROR_CLOSURE_NOT_CALLABLE = 'closure must be callable';
    const ERROR_CANNOT_PARSE_LAMBDA = 'Failed to parse closure as lambda.';
    /**
     * @param callable|null $closure
     * @param string $closureArgs
     * @param \Closure|boolean|null $default
     * @throws \InvalidArgumentException Both closure and default are null.
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @return callable|null
     */
    public static function createLambda ($closure, $closureArgs, $default = null)
    {
        if ($closure === null) {
            if ($default === null)
                throw new \InvalidArgumentException(self::ERROR_CLOSURE_NULL);
            return $default;
        }
        if ($closure instanceof \Closure)
            return $closure;
        if (is_string($closure) && ($function = self::createLambdaFromString($closure, $closureArgs)))
            return $function;
        if (is_callable($closure))
            return $closure;
        throw new \InvalidArgumentException(self::ERROR_CLOSURE_NOT_CALLABLE);
    }

    /**
     * @param string $closure
     * @param string $closureArgs
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @return string|null
     */
    private static function createLambdaFromString ($closure, $closureArgs)
    {
        $posDollar = strpos($closure, '$');
        if ($posDollar !== false) {
            $posArrow = strpos($closure, '==>', $posDollar);
            if ($posArrow !== false) {
                $args = trim(substr($closure, 0, $posArrow), "() \r\n\t");
                $code = substr($closure, $posArrow + 3);
            }
            else {
                $args = '$' . str_replace(',', '=null,$', $closureArgs) . '=null';
                $code = $closure;
            }
            $code = trim($code, " \r\n\t");
            if (strlen($code) > 0 && $code[0] != '{')
                $code = "return {$code};";
            $fun = create_function($args, $code);
            if (!$fun)
                throw new \InvalidArgumentException(self::ERROR_CANNOT_PARSE_LAMBDA);
            return $fun;
        }
        return null;
    }
}
