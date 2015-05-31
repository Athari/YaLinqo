<?php

/**
 * Utils class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

use YaLinqo;

/**
 * Functions for creating lambdas.
 * @internal
 * @package YaLinqo
 */
class Utils
{
    const ERROR_CLOSURE_NULL = 'closure must not be null.';
    const ERROR_CLOSURE_NOT_CALLABLE = 'closure must be callable';
    const ERROR_CANNOT_PARSE_LAMBDA = 'Failed to parse closure as lambda.';

    /**
     * Cache for createLambdaFromString function. Functions indexed by function code and function arguments as strings.
     * @var array
     */
    private static $lambdaCache = [ ];

    /**
     * Convert string lambda to callable function. If callable is passed, return as is.
     * @param callable|null $closure
     * @param string $closureArgs
     * @param \Closure|callable|null $default
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
     * Convert string lambda to callable function.
     * @param string $closure
     * @param string $closureArgs
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @return string|null
     */
    private static function createLambdaFromString ($closure, $closureArgs)
    {
        $posDollar = strpos($closure, '$');
        if ($posDollar !== false) {
            if (isset(self::$lambdaCache[$closure][$closureArgs]))
                return self::$lambdaCache[$closure][$closureArgs];
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
            self::$lambdaCache[$closure][$closureArgs] = $fun;
            return $fun;
        }
        return null;
    }
}
