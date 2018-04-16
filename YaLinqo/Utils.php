<?php

/**
 * Utils class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

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

    /** Cache for createLambdaFromString function. Functions indexed by function code and function arguments as strings. @var array */
    private static $lambdaCache;
    /** Map from comparison functions names to sort flags. Used in lambdaToSortFlagsAndOrder.  @var array */
    private static $compareFunctionToSortFlags = [
        null => SORT_REGULAR,
        'strcmp' => SORT_STRING,
        'strcasecmp' => 10 /*SORT_STRING | SORT_FLAG_CASE*/,
        'strcoll' => SORT_LOCALE_STRING,
        'strnatcmp' => SORT_NATURAL,
        'strnatcasecmp' => 14 /*SORT_NATURAL | SORT_FLAG_CASE*/,
    ];

    /**
     * @codeCoverageIgnore
     * @internal
     */
    public static function init()
    {
        self::$lambdaCache = [
            '$v' => [ 'v,k' => Functions::$value ],
            '$k' => [ 'v,k' => Functions::$key ],
        ];
    }

    /**
     * Convert string lambda to callable function. If callable is passed, return as is.
     * @param callable|null $closure
     * @param string $closureArgs
     * @param \Closure|callable|null $default
     * @throws \InvalidArgumentException Both closure and default are null.
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @return callable|null
     */
    public static function createLambda($closure, string $closureArgs, $default = null)
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
     * Convert string lambda or SORT_ flags to callable function. Sets isReversed to false if descending is reversed.
     * @param callable|int|null $closure
     * @param int $sortOrder
     * @param bool $isReversed
     * @return callable|string|null
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @throws \InvalidArgumentException Incorrect SORT_ flags.
     */
    public static function createComparer($closure, $sortOrder, &$isReversed)
    {
        if ($closure === null) {
            $isReversed = false;
            return $sortOrder === SORT_DESC ? Functions::$compareStrictReversed : Functions::$compareStrict;
        }
        elseif (is_int($closure)) {
            switch ($closure) {
                case SORT_REGULAR:
                    return Functions::$compareStrict;
                case SORT_NUMERIC:
                    $isReversed = false;
                    return $sortOrder === SORT_DESC ? Functions::$compareIntReversed : Functions::$compareInt;
                case SORT_STRING:
                    return 'strcmp';
                case SORT_STRING | SORT_FLAG_CASE:
                    return 'strcasecmp';
                case SORT_LOCALE_STRING:
                    return 'strcoll';
                case SORT_NATURAL:
                    return 'strnatcmp';
                case SORT_NATURAL | SORT_FLAG_CASE:
                    return 'strnatcasecmp';
                default:
                    throw new \InvalidArgumentException("Unexpected sort flag: {$closure}.");
            }
        }
        return self::createLambda($closure, 'a,b');
    }

    /**
     * Convert string lambda to SORT_ flags. Convert sortOrder from bool to SORT_ order.
     * @param callable|string|int|null $closure
     * @param int|bool $sortOrder
     * @return callable|string|int|null
     */
    public static function lambdaToSortFlagsAndOrder($closure, &$sortOrder)
    {
        if ($sortOrder !== SORT_ASC && $sortOrder !== SORT_DESC)
            $sortOrder = $sortOrder ? SORT_DESC : SORT_ASC;
        if (is_int($closure))
            return $closure;
        elseif (($closure === null || is_string($closure)) && isset(self::$compareFunctionToSortFlags[$closure]))
            return self::$compareFunctionToSortFlags[$closure];
        else
            return null;
    }

    /**
     * Convert string lambda to callable function.
     * @param string $closure
     * @param string $closureArgs
     * @throws \InvalidArgumentException Incorrect lambda syntax.
     * @return string|null
     */
    private static function createLambdaFromString(string $closure, string $closureArgs)
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
            $fun = @create_function($args, $code);
            // @codeCoverageIgnoreStart
            if (!$fun)
                throw new \InvalidArgumentException(self::ERROR_CANNOT_PARSE_LAMBDA);
            // @codeCoverageIgnoreEnd
            self::$lambdaCache[$closure][$closureArgs] = $fun;
            return $fun;
        }
        return null;
    }
}
