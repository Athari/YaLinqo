<?php

namespace YaLinqo;
use YaLinqo;

spl_autoload_register(function($class)
{
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file))
        require_once($file);
});

// TODO: string syntax: select("new { ... }")
// TODO: linq.js now: SelectMany, (Order|Then)By[Descending], Join, GroupJoin, GroupBy
// TODO: linq.js now: All, Any, Contains, OfType, Do, ForEach (Run?)
// TODO: linq.js now: (First|Last|Single)[OrDefault], [Last]IndexOf, (Skip|Take)While
// TODO: linq.js now: ToLookup, ToObject, ToDictionary, ToJSON, ToString, Write, WriteLine
// TODO: linq.js must: Distinct[By], Except[By], Intersect, Union
// TODO: linq.js must: Zip, Concat, Insert, Let, Memoize, MemoizeAll, BufferWithCount
// TODO: linq.js high: CascadeBreadthFirst, CascadeDepthFirst, Flatten, Scan, PreScan, Alternate, DefaultIfEmpty, SequenceEqual, Reverse, Shuffle
// TODO: linq.js maybe: Pairwise, PartitionBy, TakeExceptLast, TakeFromLast, Share
// TODO: Interactive: Defer, Case, DoWhile, If, IsEmpty, (Skip|Take)Last, StartWith, While
// TODO: MoreLinq: Batch(Chunk?), Pad, OrDefault+=OrFallback, (Skip|Take)Until, (Skip|Take)Every, Zip(Shortest|Longest)
// TODO: EvenMoreLinq: OrderByDirection, Permutations, Subsets, PermutedSubsets, Random, RandomSubset, Slice
// TODO: PHP Iterators: Recursive*Iterator
// TODO: PHP arrays: combine, flip, merge[_recursive], rand, replace[_recursive], walk_recursive, extract
// TODO: toTable, toCsv, toExcelCsv

class Enumerable implements \IteratorAggregate
{
    const ERROR_NO_ELEMENTS = 'Sequence contains no elements.';
    const ERROR_NO_MATCHES = 'Sequence contains no matching elements.';
    const ERROR_NO_KEY = 'Sequence does not contain the key.';
    const ERROR_MANY_ELEMENTS = 'Sequence contains more than one element.';
    const ERROR_MANY_MATCHES = 'Sequence contains more than one matching element.';
    const ERROR_COUNT_LESS_THAN_ZERO = 'count must have a non-negative value.';

    private $getIterator;

    /**
     * @param Closure $iterator
     */
    public function __construct ($iterator)
    {
        $this->getIterator = $iterator;
    }

    /** {@inheritdoc}  */
    public function getIterator ()
    {
        /** @var $it \Iterator */
        $it = call_user_func($this->getIterator);
        $it->rewind();
        return $it;
    }

    #region Generation

    /**
     * Source keys are discarded.
     * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $source
     * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
     * @throws \InvalidArgumentException If source contains no elements (checked during enumeration).
     * @return \YaLinqo\Enumerable
     */
    public static function cycle ($source)
    {
        $source = Enumerable::from($source);

        return new Enumerable(function () use ($source)
        {
            $it = new \EmptyIterator;
            $i = 0;
            return new Enumerator(function ($yield) use ($source, &$it, &$i)
            {
                /** @var $source Enumerable */
                /** @var $it \Iterator */
                if (!$it->valid()) {
                    $it = $source->getIterator();
                    if (!$it->valid())
                        throw new \InvalidArgumentException(self::ERROR_NO_ELEMENTS);
                }
                $yield($it->current(), $i++);
                $it->next();
                return true;
            });
        });
    }

    public static function emptyEnum ()
    {
        return new Enumerable(function ()
        {
            return new \EmptyIterator;
        });
    }

    /**
     * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $source
     * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
     * @return \YaLinqo\Enumerable
     */
    public static function from ($source)
    {
        $it = null;
        if ($source instanceof Enumerable)
            return $source;
        if (is_array($source))
            $it = new \ArrayIterator($source);
        elseif ($source instanceof \Iterator)
            $it = $source;
        elseif ($source instanceof \IteratorAggregate)
            $it = $source->getIterator();
        if ($it !== null) {
            return new Enumerable(function () use ($it)
            {
                return $it;
            });
        }
        throw new \InvalidArgumentException('source must be array or Traversable or Enumerable.');
    }

    public static function generate ($funcValue, $seedValue = null, $funcKey = null, $seedKey = null)
    {
        $funcValue = Utils::createLambda($funcValue, 'v,k');
        $funcKey = Utils::createLambda($funcKey, 'v,k', false);

        return new Enumerable(function () use ($funcValue, $funcKey, $seedValue, $seedKey)
        {
            $isFirst = true;
            return new Enumerator(function ($yield) use ($funcValue, $funcKey, $seedValue, $seedKey, &$value, &$key, &$isFirst)
            {
                if ($isFirst) {
                    $key = $seedKey === null ? ($funcKey ? call_user_func($funcKey, $seedValue, $seedKey) : 0) : $seedKey;
                    $value = $seedValue === null ? call_user_func($funcValue, $seedValue, $seedKey) : $seedValue;
                    $isFirst = false;
                    return $yield($value, $key);
                }
                list($value, $key) = array(
                    call_user_func($funcValue, $value, $key),
                    $funcKey ? call_user_func($funcKey, $value, $key) : $key + 1,
                );
                return $yield($value, $key);
            });
        });
    }

    public static function toInfinity ($start = 0, $step = 1)
    {
        return new Enumerable(function () use ($start, $step)
        {
            $i = -1;
            $value = $start - $step;

            return new Enumerator(function ($yield) use ($step, &$value, &$i)
            {
                return $yield($value += $step, ++$i);
            });
        });
    }

    /**
     * Searches subject for all matches to the regular expression given in pattern and enumerates them in the order specified by flags.
     * After the first match is found, the subsequent searches are continued on from end of the last match.
     * @param string $subject The input string.
     * @param string $pattern The pattern to search for, as a string.
     * @param int $flags Can be a combination of the following flags: PREG_PATTERN_ORDER, PREG_SET_ORDER, PREG_OFFSET_CAPTURE. Default: PREG_SET_ORDER.
     * @return \YaLinqo\Enumerable
     * @see preg_match_all
     */
    public static function matches ($subject, $pattern, $flags = PREG_SET_ORDER)
    {
        return new Enumerable(function () use ($subject, $pattern, $flags)
        {
            preg_match_all($pattern, $subject, $matches, $flags);
            return Enumerable::from($matches)->getIterator();
        });
    }

    public static function toNegativeInfinity ($start = 0, $step = 1)
    {
        return self::toInfinity($start, -$step);
    }

    public static function returnEnum ($element)
    {
        return self::repeat($element, 1);
    }

    public static function range ($start, $count, $step = 1)
    {
        return self::toInfinity($start, $step)->take($count);
    }

    public static function rangeDown ($start, $count, $step = 1)
    {
        return self::toInfinity($start, $count, -$step);
    }

    public static function rangeTo ($start, $end, $step = 1)
    {
        if ($start > $end)
            $step = -$step;
        return self::toInfinity($start, $step)->takeWhile(
            function ($v) use ($end)
            { return $v < $end; }
        );
    }

    public static function repeat ($element, $count)
    {
        if ($count < 0)
            throw new \InvalidArgumentException(self::ERROR_COUNT_LESS_THAN_ZERO);
        return new Enumerable(function () use ($element, $count)
        {
            $i = 0;
            return new Enumerator(function ($yield) use ($element, $count, &$i)
            {
                if ($i++ >= $count)
                    return false;
                return $yield($element, $i);
            });
        });
    }

    /**
     * Split the given string by a regular expression.
     * @param string $subject The input string.
     * @param string $pattern The pattern to search for, as a string.
     * @param int $flags flags can be any combination of the following flags: PREG_SPLIT_NO_EMPTY, PREG_SPLIT_DELIM_CAPTURE, PREG_SPLIT_OFFSET_CAPTURE. Default: 0.
     * @return \YaLinqo\Enumerable
     * @see preg_split
     */
    public static function split ($subject, $pattern, $flags = 0)
    {
        return new Enumerable(function () use ($subject, $pattern, $flags)
        {
            return Enumerable::from(preg_split($pattern, $subject, -1, $flags))->getIterator();
        });
    }

    #endregion

    #region Projection and filtering

    /**
     * <p>select (selector {{(v, k) ==> result})
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @return \YaLinqo\Enumerable
     */
    public function select ($selector)
    {
        $self = $this;
        $selector = Utils::createLambda($selector, 'v,k');

        return new Enumerable(function () use ($self, $selector)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(function ($yield) use ($it, $selector)
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
     * <p>select (selector {{(v, k) ==> result})
     * @param Closure|array|string $collectionSelector {(v, k) ==> enum}
     * @param Closure|array|string $resultSelectorValue {(v1, k1, v2, k2) ==> value}
     * @param Closure|array|string $resultSelectorKey {(v1, k1, v2, k2) ==> key}
     * @return \YaLinqo\Enumerable
     */
    public function selectMany ($collectionSelector, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $self = $this;
        $collectionSelector = Utils::createLambda($collectionSelector, 'v,k');
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v1,k1,v2,k2',
            function ($v1, $k1, $v2, $k2) { return $v2; });
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v1,k1,v2,k2', false);
        if ($resultSelectorKey === false) {
            $i = 0;
            $resultSelectorKey = function ($v1, $k1, $v2, $k2) use (&$i) { return $i++; };
        }

        return new Enumerable(function () use ($self, $collectionSelector, $resultSelectorValue, $resultSelectorKey)
        {
            /** @var $self Enumerable */
            $itOut = $self->getIterator();
            $itIn = null;
            return new Enumerator(function ($yield) use ($itOut, &$itIn, $collectionSelector, $resultSelectorValue, $resultSelectorKey)
            {
                /** @var $itOut \Iterator */
                /** @var $itIn \Iterator */
                while ($itIn === null || !$itIn->valid()) {
                    if ($itIn !== null)
                        $itOut->next();
                    if (!$itOut->valid())
                        return false;
                    $itIn = Enumerable::from(call_user_func($collectionSelector, $itOut->current(), $itOut->key()))->getIterator();
                }
                $args = array($itOut->current(), $itOut->key(), $itIn->current(), $itIn->key());
                $yield(call_user_func_array($resultSelectorValue, $args), call_user_func_array($resultSelectorKey, $args));
                $itIn->next();
                return true;
            });
        });
    }

    /**
     * <p>where (predicate {{(v, k) ==> result})
     * @param Closure|array|string $predicate {(v, k) ==> result}
     * @return \YaLinqo\Enumerable
     */
    public function where ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(function ($yield) use ($it, $predicate)
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

    #endregion

    #region Aggregation

    /**
     * <p>aggregate (func {{accum, (v, k) ==> result} [, seed]) => result
     * @param Closure|array|string $func {accum, (v, k) ==> result}
     * @param mixed $seed If seed is not null, the first element is used as seed. Default: null.
     * @throws \InvalidOperationException If seed is null and sequence contains no elements.
     * @return mixed
     */
    public function aggregate ($func, $seed = null)
    {
        $func = Utils::createLambda($func, 'a,v,k');

        $result = $seed;
        if ($seed !== null) {
            foreach ($this as $k => $v)
                $result = call_user_func($func, $result, $v, $k);
        } else {
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
     * <p>aggregateOrDefault (func {{accum, (v, k) ==> result}, default) => result
     * @param Closure|array|string $func {accum, (v, k) ==> result}
     * @param mixed $default Value to return if sequence is empty.
     * @return mixed
     */
    public function aggregateOrDefault ($func, $default = null)
    {
        $func = Utils::createLambda($func, 'a,v,k');
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
     * <p>average ([selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function average ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$identity);
        $sum = $count = 0;

        foreach ($this as $k => $v) {
            $sum += call_user_func($selector, $v, $k);
            $count++;
        }
        return $count === 0 ? NAN : $sum / $count;
    }

    /**
     * <p>count ([selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @return int
     */
    public function count ($selector = null)
    {
        $it = $this->getIterator();

        if ($it instanceof \Countable && $selector === null)
            return count($it);

        $selector = Utils::createLambda($selector, 'v,k', Functions::$identity);
        $count = 0;

        foreach ($this as $k => $v)
            if (call_user_func($selector, $v, $k))
                $count++;
        return $count;
    }

    /**
     * <p>max ([selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $selector {(v, k) ==> result}
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
     * <p>maxBy (comparer {{a, b => diff} [, selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $comparer {a, b => diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function maxBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer)
        { return call_user_func($comparer, $a, $b) > 0 ? $a : $b; });
    }

    /**
     * <p>min ([selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $selector {(v, k) ==> result}
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
     * <p>minBy (comparer {{a, b => diff} [, selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $comparer {a, b => diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function minBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer)
        { return call_user_func($comparer, $a, $b) > 0 ? $a : $b; });
    }

    /**
     * <p>sum ([selector {{(v, k) ==> result}]) => result
     * @param Closure|array|string $selector {(v, k) ==> result}
     * @return number
     */
    public function sum ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregateOrDefault(function ($a, $b)
        { return $a + $b; }, 0);
    }

    #endregion

    #region Pagination

    /**
     * @param mixed $key
     * @throws \InvalidArgumentException If sequence does not contain element with specified key.
     * @return mixed
     */
    public function elementAt ($key)
    {
        /** @var $it \Iterator|\ArrayAccess */
        $it = $this->getIterator();

        if ($it instanceof \ArrayAccess) {
            if (!$it->offsetExists($key))
                throw new \InvalidArgumentException(self::ERROR_NO_KEY);
            return $it->offsetGet($key);
        }

        foreach ($it as $k => $v) {
            if ($k === $key)
                return $v;
        }
        throw new \InvalidArgumentException(self::ERROR_NO_KEY);
    }

    /**
     * @param mixed $key
     * @param mixed $default Value to return if sequence does not contain element with specified key.
     * @return mixed
     */
    public function elementAtOrDefault ($key, $default = null)
    {
        /** @var $it \Iterator|\ArrayAccess */
        $it = $this->getIterator();

        if ($it instanceof \ArrayAccess)
            return $it->offsetExists($key) ? $it->offsetGet($key) : $default;

        foreach ($it as $k => $v) {
            if ($k === $key)
                return $v;
        }
        return $default;
    }

    // TODO Pagination

    public function take ($count)
    {
        if ($count < 0)
            throw new \InvalidArgumentException(self::ERROR_COUNT_LESS_THAN_ZERO);

        $self = $this;

        return new Enumerable(function () use ($self, $count)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $i = 0;
            return new Enumerator(function ($yield) use ($it, &$i, $count)
            {
                /** @var $it \Iterator */
                if ($i++ >= $count || !$it->valid())
                    return false;
                $yield($it->current(), $it->key());
                $it->next();
                return true;
            });
        });
    }

    #endregion

    #region Conversion

    public function toArray ()
    {
        $array = array();
        foreach ($this as $k => $v)
            $array[$k] = $v;
        return $array;
    }

    public function toKeys ()
    {
        $self = $this;

        return new Enumerable(function () use ($self)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $i = 0;
            return new Enumerator(function ($yield) use ($it, &$i)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                $yield($it->key(), $i++);
                $it->next();
                return true;
            });
        });
    }

    public function toValues ()
    {
        $self = $this;

        return new Enumerable(function () use ($self)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $i = 0;
            return new Enumerator(function ($yield) use ($it, &$i)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                $yield($it->current(), $i++);
                $it->next();
                return true;
            });
        });
    }

    // TODO Conversion

    #endregion
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

var_dump($enum->toArray());
var_dump($enum->toValues()->toArray());
var_dump($enum->toValues()->elementAt(2));
var_dump($enum->toValues()->elementAtOrDefault(-1, 666));

//var_dump(Enumerable::from(array(1, 2, 3))->take(2)->toArray());
var_dump(Enumerable::cycle(array(1, 2, 3))->take(10)->toArray());

var_dump(Enumerable::emptyEnum()->toArray());
var_dump(Enumerable::returnEnum('a')->toArray());
//var_dump(Enumerable::repeat('b', -1)->toArray());
var_dump(Enumerable::repeat('c', 0)->toArray());
var_dump(Enumerable::repeat('d', 2)->toArray());
var_dump(Enumerable::repeat('e', INF)->take(2)->toArray());

var_dump(Enumerable::generate(
    function($v, $k)
    { return $k * $k - $v * 2; }
)->take(20)->toArray());

var_dump(Enumerable::generate(
    function($v, $k)
    { return $v + $k; },
    1,
    function($v)
    { return $v; },
    1
)->take(10)->toArray());

var_dump(Enumerable::generate(
    function($v)
    { return array($v[1], $v[0] + $v[1]); },
    array(1, 1),
    function($v)
    { return $v[1]; },
    1
)->toKeys()->take(10)->toArray());

var_dump(Enumerable::generate(
    function ($v, $k)
    { return pow(-1, $k) / (2 * $k + 1); },
    0
)->take(1000)->sum() * 4);

var_dump(Enumerable::toInfinity()->take(999)->sum(
    function ($k)
    { return pow(-1, $k) / (2 * $k + 1); }
) * 4);

echo "Lambdas\n";
var_dump(Enumerable::from(array(1, 2, 3, 4, 5, 6))->where('$v ==> $v > 3')->select('$v ==> $v*$v')->toArray());
var_dump(Enumerable::from(array(1, 2, 3, 4, 5, 6))->where('($v) ==> $v > 3')->select('$v, $k ==> $v+$k')->toArray());
var_dump(Enumerable::from(array(1, 2, 3, 4, 5, 6))->where('($v) ==> { echo $v; return $v > 3; }')->select('($v, $k) ==> { return $v*2+$k*3; }')->toArray());
var_dump(Enumerable::from(array(1, 2, 3, 4, 5, 6))->where('$v > 2')->where('$v>3')->select('$v+$k')->toArray());

var_dump(Enumerable::split('1 2 3 4 5 6', '# #')->toArray());
var_dump(Enumerable::matches('1 2 3 4 5 6', '#\d+#')->select('$v[0]')->maxBy(Functions::$compareStrict));

var_dump(Enumerable::from(array(1, 2))->selectMany('$v ==> array(1, 2)', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(Enumerable::from(array(1, 2))->selectMany('$v ==> array(1, 2)', '"$k1=$v1 $k2=$v2"')->toArray());
var_dump(Enumerable::from(array(1, 2))->selectMany('$v ==> array(1, 2)', 'array($v1, $v2)')->toArray());
var_dump(Enumerable::from(array(1, 2))->selectMany('$v ==> array()', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(Enumerable::from(array())->selectMany('$v ==> array(1, 2)', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(Enumerable::from(array('a' => array(1, 2), 'b' => array(3)))->selectMany('$v')->toArray());
