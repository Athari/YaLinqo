<?php

namespace YaLinqo;
use YaLinqo;
use YaLinqo\exceptions as e;

spl_autoload_register(function($class)
{
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file))
        require_once($file);
});

class Enumerable implements \IteratorAggregate
{
    const ERROR_NO_ELEMENTS = 'Sequence contains no elements.';

    private $iterator;

    public function __construct ($iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return \YaLinqo\Enumerator
     */
    public function getIterator ()
    {
        /** @var $it \Iterator */
        $it = call_user_func($this->iterator);
        $it->rewind();
        return $it;
    }

    /**
     * @param mixed $obj
     * @throws \Exception
     * @return \YaLinqo\Enumerable
     */
    public static function from ($obj)
    {
        if ($obj instanceof \Iterator) {
            return new Enumerable(function () use ($obj)
            {
                return $obj;
            });
        }
        if (is_array($obj)) {
            return new Enumerable(function () use ($obj)
            {
                return new \ArrayIterator($obj);
            });
        }
        throw new \Exception;
    }

    /**
     * <p>select (selector {{value => result}) => enum
     * <p>select (selector {{value, key => result}) => enum
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @return \YaLinqo\Enumerable
     */
    public function select ($selector)
    {
        $self = $this;
        $selector = Utils::createLambda($selector);

        return new Enumerable(function () use ($self, $selector)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(
                function ($yield) use ($it, $selector)
                {
                    /** @var $it \Iterator */
                    if (!$it->valid())
                        return false;
                    $yield(call_user_func($selector, $it->current(), $it->key()), $it->key());
                    $it->next();
                    return true;
                });
        });
    }

    /**
     * <p>where (predicate {{value => result}) => enum
     * <p>where (predicate {{value, key => result}) => enum
     * @param Closure|array|string $predicate {value => result} | {value, key => result}
     * @return \YaLinqo\Enumerable
     */
    public function where ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate);

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(
                function ($yield) use ($it, $predicate)
                {
                    /** @var $it \Iterator */
                    if (!$it->valid())
                        return false;
                    do {
                        if (call_user_func($predicate, $it->current(), $it->key())) {
                            $yield($it->current(), $it->key());
                            $it->next();
                            return true;
                        }
                        $it->next();
                    } while ($it->valid());
                    return false;
                });
        });
    }

    /**
     * <p>aggregate (func {{accum, value => result}) => result
     * <p>aggregate (func {{accum, value, key => result}) => result
     * <p>aggregate (func {{accum, value => result}, seed) => result
     * <p>aggregate (func {{accum, value, key => result}, seed) => result
     * @param Closure|array|string $func {accum, value => result} | {accum, value, key => result}
     * @param mixed $seed If seed is not null, the first element is used as seed. Default: null.
     * @throws \InvalidOperationException If seed is null and sequence contains no elements.
     * @return mixed
     */
    public function aggregate ($func, $seed = null)
    {
        $func = Utils::createLambda($func);

        $result = $seed;
        if ($seed !== null) {
            foreach ($this as $k => $v)
                $result = call_user_func($func, $result, $v, $k);
        }
        else {
            $assigned = false;
            foreach ($this as $k => $v) {
                if ($assigned)
                    $result = call_user_func($func, $result, $v, $k);
                else {
                    $result = $v;
                    $assigned = true;
                }
            }
            if (!$assigned)
                throw new \InvalidOperationException(self::ERROR_NO_ELEMENTS);
        }
        return $result;
    }

    /**
     * <p>aggregate (func {{accum, value => result}, default) => result
     * <p>aggregate (func {{accum, value, key => result}, default) => result
     * @param Closure|array|string $func {accum, value => result} | {accum, value, key => result}
     * @param mixed $default Value to return if sequence is empty.
     * @return mixed
     */
    public function aggregateOrDefault ($func, $default = null)
    {
        $func = Utils::createLambda($func);
        $result = null;
        $assigned = false;

        foreach ($this as $k => $v) {
            if ($assigned)
                $result = call_user_func($func, $result, $v, $k);
            else {
                $result = $v;
                $assigned = true;
            }
        }
        return $assigned ? $result : $default;
    }

    /**
     * <p>average () => result
     * <p>average (selector {{value => result}) => result
     * <p>average (selector {{value, key => result}) => result
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function average ($selector = null)
    {
        $selector = Utils::createLambda($selector, Functions::$identity);
        $sum = $count = 0;

        foreach ($this as $k => $v) {
            $sum += call_user_func($selector, $v, $k);
            $count++;
        }
        return $count === 0 ? NAN : $sum / $count;
    }

    /**
     * <p>count () => num
     * <p>count (selector {{value => result}) => num
     * <p>count (selector {{value, key => result}) => num
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @return int
     */
    public function count ($selector = null)
    {
        $it = $this->getIterator();

        if ($it instanceof \Countable && $selector === null)
            return count($it);

        $selector = Utils::createLambda($selector, Functions::$identity);
        $count = 0;

        foreach ($this as $k => $v)
            if (call_user_func($selector, $v, $k))
                $count++;
        return $count;
    }

    /**
     * <p>max () => result
     * <p>max (selector {{value => result}) => result
     * <p>max (selector {{value, key => result}) => result
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function max ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b)
        { return max($a, $b); });
    }

    /**
     * <p>maxBy (comparer {{a, b => diff}) => result
     * <p>maxBy (comparer {{a, b => diff}, selector {{value => result}) => result
     * <p>maxBy (comparer {{a, b => diff}, selector {{value, key => result}) => result
     * @param Closure|array|string $comparer {a, b => diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function maxBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, Functions::$compare);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer)
        { return call_user_func($comparer, $a, $b) > 0 ? $a : $b; });
    }

    /**
     * <p>min () => result
     * <p>min (selector {{value => result}) => result
     * <p>min (selector {{value, key => result}) => result
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function min ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function($a, $b)
        { return min($a, $b); });
    }

    /**
     * <p>minBy (comparer {{a, b => diff}) => result
     * <p>minBy (comparer {{a, b => diff}, selector {{value => result}) => result
     * <p>minBy (comparer {{a, b => diff}, selector {{value, key => result}) => result
     * @param Closure|array|string $comparer {a, b => diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function minBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, Functions::$compare);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer)
        { return call_user_func($comparer, $a, $b) > 0 ? $a : $b; });
    }
}

$enum = Enumerable::from(array('a', 'bbb', 'c', 1, 'a' => 2, '10' => 3))
        ->where(
    function($v, $k)
    { return is_numeric($k); })
        ->select(
    function($v, $k)
    { return "$v($k)"; });

function compare_strlen ($a, $b)
{
    return strlen($a) - strlen($b);
}

foreach ($enum as $k => $v)
    echo "($k): ($v)\n";

var_dump($enum->aggregate(function($a, $b)
{ return $a . '|' . $b; }, 'ooo'));
var_dump($enum->aggregate(function($a, $b)
{ return $a . '|' . $b; }));

var_dump($enum->average(function($v, $k)
{ return $v + $k; }));
var_dump($enum->average(function($v, $k)
{ return $k; }));
var_dump($enum->average());
var_dump(Enumerable::from(new \EmptyIterator)->average());

var_dump($enum->count(function($v)
{ return intval($v) != 0; }));
var_dump(Enumerable::from(array(1, 2, 3))->count(function($v)
{ return $v > 1; }));
var_dump($enum->count());
var_dump(Enumerable::from(new \EmptyIterator)->count());

var_dump($enum->max(function($v, $k)
{ return intval($k); }));
var_dump(Enumerable::from(array(1, 2, 3))->max(function($v)
{ return $v * $v; }));
var_dump(Enumerable::from(array(1, 2, 3))->max());
var_dump($enum->max());
//var_dump(Enumerable::from(new \EmptyIterator)->max());

var_dump($enum->min(function($v, $k)
{ return intval($k); }));
var_dump(Enumerable::from(array(1, 2, 3))->min(function($v)
{ return $v * $v; }));
var_dump(Enumerable::from(array(1, 2, 3))->min());
var_dump($enum->min());
//var_dump(Enumerable::from(new \EmptyIterator)->min());

var_dump($enum->maxBy(__NAMESPACE__ . '\compare_strlen', function($v, $k)
{ return $v . ' ' . $k; }));
