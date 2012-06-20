<?php

namespace YaLinqo;
use YaLinqo, YaLinqo\collections as c;

// TODO: string syntax: select("new { ... }")
// TODO: linq.js must: Distinct[By], Except[By], Intersect, Union
// TODO: linq.js must: Zip, Concat, Insert, Let, Memoize, MemoizeAll, BufferWithCount
// TODO: linq.js high: CascadeBreadthFirst, CascadeDepthFirst, Flatten, Scan, PreScan, Alternate, DefaultIfEmpty, SequenceEqual, Reverse, Shuffle
// TODO: linq.js maybe: Pairwise, PartitionBy, TakeExceptLast, TakeFromLast, Share
// TODO: Interactive: Defer, Case, DoWhile, If, IsEmpty, (Skip|Take)Last, StartWith, While
// TODO: MoreLinq: Batch(Chunk?), Pad, (Skip|Take)Until, (Skip|Take)Every, Zip(Shortest|Longest)
// TODO: EvenMoreLinq: Permutations, Subsets, PermutedSubsets, Random, RandomSubset, Slice
// TODO: LinqLib: Permutations, Combinations, Statistical
// TODO: PHP Iterators: Recursive*Iterator
// TODO: PHP arrays: combine, flip, merge[_recursive], rand, replace[_recursive], walk_recursive, extract
// TODO: toTable, toCsv, toExcelCsv
// TODO: foreach fails on object keys. Bug in PHP still not fixed. Transform all statements into ForEach calls?
// Differences: preserving keys and toSequental, *Enum for keywords, no (el,i) overloads, string lambda args (v,k,a,b,e etc.), toArray/toList/toDictionary, objects as keys, docs copied and may be incorrect, elementAt uses key instead of index

class Enumerable implements \IteratorAggregate
{
    const ERROR_NO_ELEMENTS = 'Sequence contains no elements.';
    const ERROR_NO_MATCHES = 'Sequence contains no matching elements.';
    const ERROR_NO_KEY = 'Sequence does not contain the key.';
    const ERROR_MANY_ELEMENTS = 'Sequence contains more than one element.';
    const ERROR_MANY_MATCHES = 'Sequence contains more than one matching element.';
    const ERROR_COUNT_LESS_THAN_ZERO = 'count must be a non-negative value.';

    private $getIterator;

    /**
     * @param Closure $iterator
     */
    public function __construct ($iterator)
    {
        $this->getIterator = $iterator;
    }

    /** {@inheritdoc} */
    public function getIterator ()
    {
        /** @var $it \Iterator */
        $it = call_user_func($this->getIterator);
        $it->rewind();
        return $it;
    }

    #region Generation

    /**
     * TODO doc
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
                    $it->rewind();
                    if (!$it->valid())
                        throw new \InvalidArgumentException(self::ERROR_NO_ELEMENTS);
                }
                $yield($it->current(), $i++);
                $it->next();
                return true;
            });
        });
    }

    /**
     * <p><b>Syntax</b>: emptyEnum ()
     * <p>Returns an empty sequence.
     * @return \YaLinqo\Enumerable
     */
    public static function emptyEnum ()
    {
        return new Enumerable(function ()
        {
            return new \EmptyIterator;
        });
    }

    /**
     * <p><b>Syntax</b>: from (source)
     * <p>Converts source into Enumerable sequence. Result depends on the type of source:
     * <ul>
     * <li><b>array</b>: Enumerable from ArrayIterator;
     * <li><b>Enumerable</b>: Enumerable source itself;
     * <li><b>Iterator</b>: Enumerable from Iterator;
     * <li><b>IteratorAggregate</b>: Enumerable from Iterator returned from getIterator() method.
     * </ul>
     * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $source Value to convert into Enumerable sequence.
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

    /**
     * TODO doc
     */
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
            function ($v) use ($end) { return $v < $end; }
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

    public function ofType ($type)
    {
        switch ($type) {
            case 'array':
                return $this->where(function ($v) { return is_array($v); });
            case 'int':
            case 'integer':
            case 'long':
                return $this->where(function ($v) { return is_int($v); });
            case 'callable':
                return $this->where(function ($v) { return is_callable($v); });
            case 'float':
            case 'real':
            case 'double':
                return $this->where(function ($v) { return is_float($v); });
            case 'null':
                return $this->where(function ($v) { return is_null($v); });
            case 'numeric':
                return $this->where(function ($v) { return is_numeric($v); });
            case 'object':
                return $this->where(function ($v) { return is_object($v); });
            case 'scalar':
                return $this->where(function ($v) { return is_scalar($v); });
            case 'string':
                return $this->where(function ($v) { return is_string($v); });
            default:
                return $this->where(function ($v) use ($type) { return is_object($v) && get_class($v) === $type; });
        }
    }

    /**
     * <p><b>Syntax</b>: select (selectorValue {{(v, k) ==> result} [, selectorKey {{(v, k) ==> result}])
     * <p>Projects each element of a sequence into a new form.
     * <p>This projection method requires the transform functions, selectorValue and selectorKey, to produce one key-value pair for each value in the source sequence. If selectorValue returns a value that is itself a collection, it is up to the consumer to traverse the subsequences manually. In such a situation, it might be better for your query to return a single coalesced sequence of values. To achieve this, use the {@link selectMany()} method instead of select. Although selectMany works similarly to select, it differs in that the transform function returns a collection that is then expanded by selectMany before it is returned.
     * @param callback $selectorValue {(v, k) ==> value} A transform function to apply to each value.
     * @param callback $selectorKey {(v, k) ==> key} A transform function to apply to each key. Default: key.
     * @return \YaLinqo\Enumerable A sequence whose elements are the result of invoking the transform functions on each element of source.
     */
    public function select ($selectorValue, $selectorKey = null)
    {
        $self = $this;
        $selectorValue = Utils::createLambda($selectorValue, 'v,k');
        $selectorKey = Utils::createLambda($selectorKey, 'v,k', Functions::$key);

        return new Enumerable(function () use ($self, $selectorValue, $selectorKey)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();

            return new Enumerator(function ($yield) use ($it, $selectorValue, $selectorKey)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                $yield(
                    call_user_func($selectorValue, $it->current(), $it->key()),
                    call_user_func($selectorKey, $it->current(), $it->key())
                );
                $it->next();
                return true;
            });
        });
    }

    /**
     * <p><b>Syntax</b>: selectMany (collectionSelector {{(v, k) ==> enum})
     * <p>Projects each element of a sequence to a sequence and flattens the resulting sequences into one sequence.
     * <p>The selectMany method enumerates the input sequence, uses transform functions to map each element to a sequence, and then enumerates and yields the elements of each such sequence. That is, for each element of source, selectorValue and selectorKey are invoked and a sequence of key-value pairs is returned. selectMany then flattens this two-dimensional collection of collections into a one-dimensional sequence and returns it. For example, if a query uses selectMany to obtain the orders for each customer in a database, the result is a sequence of orders. If instead the query uses {@link select} to obtain the orders, the collection of collections of orders is not combined and the result is a sequence of sequences of orders.
     * <p><b>Syntax</b>: selectMany (collectionSelector {{(v, k) ==> enum} [, resultSelectorValue {{(v1, v2, k1, k2) ==> value} [, resultSelectorKey {{(v1, v2, k1, k2) ==> key}]])
     * <p>Projects each element of a sequence to a sequence, flattens the resulting sequences into one sequence, and invokes a result selector functions on each element therein.
     * <p>The selectMany method is useful when you have to keep the elements of source in scope for query logic that occurs after the call to selectMany. If there is a bidirectional relationship between objects in the source sequence and objects returned from collectionSelector, that is, if a sequence returned from collectionSelector provides a property to retrieve the object that produced it, you do not need this overload of selectMany. Instead, you can use simpler selectMany overload and navigate back to the source object through the returned sequence.
     * @param callback $collectionSelector {(v, k) ==> enum} A transform function to apply to each element.
     * @param callback $resultSelectorValue {(v1, v2, k1, k2) ==> value} A transform function to apply to each value of the intermediate sequence. Default: {(v1, v2, k1, k2) ==> v2}.
     * @param callback $resultSelectorKey {(v1, v2, k1, k2) ==> key} A transform function to apply to each key of the intermediate sequence. Default: increment.
     * @return \YaLinqo\Enumerable A sequence whose elements are the result of invoking the one-to-many transform function on each element of the input sequence.
     */
    public function selectMany ($collectionSelector, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $self = $this;
        $collectionSelector = Utils::createLambda($collectionSelector, 'v,k');
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v1,v2,k1,k2',
            function ($v1, $v2, $k1, $k2) { return $v2; });
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v1,v2,k1,k2', false);
        if ($resultSelectorKey === false) {
            $i = 0;
            $resultSelectorKey = function ($v1, $v2, $k1, $k2) use (&$i) { return $i++; };
        }

        return new Enumerable(function () use ($self, $collectionSelector, $resultSelectorValue, $resultSelectorKey)
        {
            /** @var $self Enumerable */
            $itOut = $self->getIterator();
            $itOut->rewind();
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
                    $itIn->rewind();
                }
                $args = array($itOut->current(), $itOut->key(), $itIn->current(), $itIn->key());
                $yield(call_user_func_array($resultSelectorValue, $args), call_user_func_array($resultSelectorKey, $args));
                $itIn->next();
                return true;
            });
        });
    }

    /**
     * <p><b>Syntax</b>: where (predicate {{(v, k) ==> result})
     * <p>Filters a sequence of values based on a predicate.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return \YaLinqo\Enumerable A sequence that contains elements from the input sequence that satisfy the condition.
     */
    public function where ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();

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

    #region Ordering

    /**
     * <p>orderByDir (false|true [, {{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Sorts the elements of a sequence in a particular direction (ascending, descending) according to a key.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function orderByDir ($desc, $keySelector = null, $comparer = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$identity);
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        return new OrderedEnumerable($this, $desc, $keySelector, $comparer);
    }

    /**
     * <p>orderBy ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Sorts the elements of a sequence in ascending order according to a key.
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function orderBy ($keySelector = null, $comparer = null)
    {
        return $this->orderByDir(false, $keySelector, $comparer);
    }

    /**
     * <p>orderByDescending ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Sorts the elements of a sequence in descending order according to a key.
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function orderByDescending ($keySelector = null, $comparer = null)
    {
        return $this->orderByDir(true, $keySelector, $comparer);
    }

    #endregion

    #region Joining

    public function groupJoin ($inner, $outerKeySelector = null, $innerKeySelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $self = $this;
        $inner = self::from($inner);
        $outerKeySelector = Utils::createLambda($outerKeySelector, 'v,k', Functions::$key);
        $innerKeySelector = Utils::createLambda($innerKeySelector, 'v,k', Functions::$key);
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v,e,k', function ($v, $e, $k) { return array($v, $e); });
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v,e,k', function ($v, $e, $k) { return $k; });

        return new Enumerable(function () use ($self, $inner, $outerKeySelector, $innerKeySelector, $resultSelectorValue, $resultSelectorKey)
        {
            /** @var $self Enumerable */
            /** @var $inner Enumerable */
            $it = $self->getIterator();
            $it->rewind();
            $lookup = $inner->toLookup($innerKeySelector);

            return new Enumerator(function ($yield) use ($it, $lookup, $outerKeySelector, $resultSelectorValue, $resultSelectorKey)
            {
                /** @var $it \Iterator */
                /** @var $lookup \YaLinqo\collections\Lookup */
                if (!$it->valid())
                    return false;
                $key = call_user_func($outerKeySelector, $it->current(), $it->key());
                $args = array($it->current(), Enumerable::from($lookup[$key]), $key);
                $yield(call_user_func_array($resultSelectorValue, $args), call_user_func_array($resultSelectorKey, $args));
                $it->next();
                return true;
            });
        });
    }

    /**
     * <p><b>Syntax</b>: join (inner [, outerKeySelector {{(v, k) ==> key} [, innerKeySelector {{(v, k) ==> key} [, resultSelectorValue {{(v1, v2, k) ==> value} [, resultSelectorKey {{(v1, v2, k) ==> key}]]]])
     * <p>Correlates the elements of two sequences based on matching keys.
     * <p>A join refers to the operation of correlating the elements of two sources of information based on a common key. Join brings the two information sources and the keys by which they are matched together in one method call. This differs from the use of SelectMany, which requires more than one method call to perform the same operation.
     * <p>Join preserves the order of the elements of the source, and for each of these elements, the order of the matching elements of inner.
     * <p>In relational database terms, the Join method implements an inner equijoin. 'Inner' means that only elements that have a match in the other sequence are included in the results. An 'equijoin' is a join in which the keys are compared for equality.
     * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $inner The sequence to join to the source sequence.
     * @param callback $outerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the source sequence. Default: key.
     * @param callback $innerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the second sequence. Default: key.
     * @param callback $resultSelectorValue {(v1, v2, k) ==> result} A function to create a result value from two matching elements. Default: {(v1, v2, k) ==> array(v1, v2)}.
     * @param callback $resultSelectorKey {(v1, v2, k) ==> result} A function to create a result key from two matching elements. Default: {(v1, v2, k) ==> k}.
     * @return \YaLinqo\Enumerable
     */
    public function join ($inner, $outerKeySelector = null, $innerKeySelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $self = $this;
        $inner = self::from($inner);
        $outerKeySelector = Utils::createLambda($outerKeySelector, 'v,k', Functions::$key);
        $innerKeySelector = Utils::createLambda($innerKeySelector, 'v,k', Functions::$key);
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v1,v2,k', function ($v1, $v2, $k) { return array($v1, $v2); });
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v1,v2,k', function ($v1, $v2, $k) { return $k; });

        return new Enumerable(function () use ($self, $inner, $outerKeySelector, $innerKeySelector, $resultSelectorValue, $resultSelectorKey)
        {
            /** @var $self Enumerable */
            /** @var $inner Enumerable */
            $itOut = $self->getIterator();
            $itOut->rewind();
            $lookup = $inner->toLookup($innerKeySelector);
            $arrIn = null;
            $posIn = 0;
            $key = null;

            return new Enumerator(function ($yield) use ($itOut, $lookup, &$arrIn, &$posIn, &$key, $outerKeySelector, $resultSelectorValue, $resultSelectorKey)
            {
                /** @var $itOut \Iterator */
                /** @var $lookup \YaLinqo\collections\Lookup */
                while ($arrIn === null || $posIn >= count($arrIn)) {
                    if ($arrIn !== null)
                        $itOut->next();
                    if (!$itOut->valid())
                        return false;
                    $key = call_user_func($outerKeySelector, $itOut->current(), $itOut->key());
                    $arrIn = $lookup[$key];
                    $posIn = 0;
                }
                $args = array($itOut->current(), $arrIn[$posIn], $key);
                $yield(call_user_func_array($resultSelectorValue, $args), call_user_func_array($resultSelectorKey, $args));
                $posIn++;
                return true;
            });
        });
    }

    #endregion

    #region Grouping

    public function groupBy ($keySelector = null, $valueSelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$value);
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'e,k', Functions::$value);
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'e,k', Functions::$key);

        return self::from($this->toLookup($keySelector, $valueSelector))
                ->select($resultSelectorValue, $resultSelectorKey);
    }

    #endregion

    #region Aggregation

    /**
     * <p><b>Syntax</b>: aggregate (func {{(a, v, k) ==> accum} [, seed])
     * <p>Applies an accumulator function over a sequence. If seed is not null, its value is used as the initial accumulator value.
     * <p>Aggregate method makes it simple to perform a calculation over a sequence of values. This method works by calling func one time for each element in source. Each time func is called, aggregate passes both the element from the sequence and an aggregated value (as the first argument to func). If seed is null, the first element of source is used as the initial aggregate value. The result of func replaces the previous aggregated value. Aggregate returns the final result of func.
     * <p>To simplify common aggregation operations, the standard query operators also include a general purpose count method, {@link count}, and four numeric aggregation methods, namely {@link min}, {@link max}, {@link sum}, and {@link average}.
     * @param callback $func {(a, v, k) ==> accum} An accumulator function to be invoked on each element.
     * @param mixed $seed If seed is not null, the first element is used as seed. Default: null.
     * @throws \InvalidOperationException If seed is null and sequence contains no elements.
     * @return mixed The final accumulator value.
     */
    public function aggregate ($func, $seed = null)
    {
        $func = Utils::createLambda($func, 'a,v,k');

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
     * <p>aggregateOrDefault (func {{(a, v, k) ==> accum} [, default])
     * @param callback $func {(a, v, k) ==> accum}
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
     * <p><b>Syntax</b>: average ()
     * <p>Computes the average of a sequence of numeric values.
     * <p><b>Syntax</b>: average (selector {{(v, k) ==> result})
     * <p>Computes the average of a sequence of numeric values that are obtained by invoking a transform function on each element of the input sequence.
     * @param callback $selector {(v, k) ==> result} A transform function to apply to each element. Default: identity.
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number The average of the sequence of values.
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
     * <p><b>Syntax</b>: count ()
     * <p>Returns the number of elements in a sequence.
     * <p>If source iterator implements {@link Countable}, that implementation is used to obtain the count of elements. Otherwise, this method determines the count.
     * <p><b>Syntax</b>: count (predicate {{(v, k) ==> result})
     * <p>Returns a number that represents how many elements in the specified sequence satisfy a condition.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: null.
     * @return int The number of elements in the input sequence.
     */
    public function count ($predicate = null)
    {
        $it = $this->getIterator();

        if ($it instanceof \Countable && $predicate === null)
            return count($it);

        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$identity);
        $count = 0;

        foreach ($this as $k => $v)
            if (call_user_func($predicate, $v, $k))
                $count++;
        return $count;
    }

    /**
     * <p>max ([selector {{(v, k) ==> result}])
     * @param callback $selector {(v, k) ==> result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function max ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) { return max($a, $b); });
    }

    /**
     * <p>maxBy (comparer {{(a, b) ==> diff} [, selector {{(v, k) ==> result}])
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param callback $selector {(v, k) ==> result}
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
     * <p>min ([selector {{(v, k) ==> result}])
     * @param callback $selector {(v, k) ==> result}
     * @throws \InvalidOperationException If sequence contains no elements.
     * @return number
     */
    public function min ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function($a, $b) { return min($a, $b); });
    }

    /**
     * <p>minBy (comparer {{(a, b) ==> diff} [, selector {{(v, k) ==> result}])
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param callback $selector {(v, k) ==> result}
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
     * <p>sum ([selector {{(v, k) ==> result}])
     * @param callback $selector {(v, k) ==> result}
     * @return number
     */
    public function sum ($selector = null)
    {
        $enum = $this;
        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregateOrDefault(function ($a, $b) { return $a + $b; }, 0);
    }

    #endregion

    #region Set

    /**
     * <p><b>Syntax</b>: all (predicate {{(v, k) ==> result})
     * <p>Determines whether all elements of a sequence satisfy a condition. The enumeration of source is stopped as soon as the result can be determined.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return bool true if every element of the source sequence passes the test in the specified predicate, or if the sequence is empty; otherwise, false.
     */
    public function all ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        foreach ($this as $k => $v) {
            if (!call_user_func($predicate, $v, $k))
                return false;
        }
        return true;
    }

    /**
     * <p><b>Syntax</b>: any ()
     * <p>Determines whether a sequence contains any elements. The enumeration of source is stopped as soon as the result can be determined.
     * <p><b>Syntax</b>: any (predicate {{(v, k) ==> result})
     * <p>Determines whether any element of a sequence exists or satisfies a condition. The enumeration of source is stopped as soon as the result can be determined.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: null.
     * @return bool If predicate is null: true if the source sequence contains any elements; otherwise, false. If predicate is not null: true if any elements in the source sequence pass the test in the specified predicate; otherwise, false.
     */
    public function any ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', false);

        if ($predicate) {
            foreach ($this as $k => $v) {
                if (call_user_func($predicate, $v, $k))
                    return true;
            }
            return false;
        }
        else {
            $it = $this->getIterator();
            if ($it instanceof \Countable)
                return count($it) > 0;
            $it->rewind();
            return $it->valid();
        }
    }

    /**
     * <p><b>Syntax</b>: contains (value)
     * <p>Determines whether a sequence contains a specified element. Enumeration is terminated as soon as a matching element is found.
     * @param $value mixed The value to locate in the sequence.
     * @return bool true if the source sequence contains an element that has the specified value; otherwise, false.
     */
    public function contains ($value)
    {
        foreach ($this as $v) {
            if ($v === $value)
                return true;
        }
        return false;
    }

    #endregion

    #region Pagination

    /**
     * <p><b>Syntax</b>: elementAt (key)
     * <p>Returns the value at a specified key in a sequence.
     * <p>If the type of source iterator implements {@link ArrayAccess}, that implementation is used to obtain the value at the specified key. Otherwise, this method obtains the specified value.
     * <p>This method throws an exception if key is not found. To instead return a default value when the specified key is not found, use the {@link elementAtOrDefault} method.
     * @param mixed $key The key of the value to retrieve.
     * @throws \InvalidArgumentException If sequence does not contain value with specified key.
     * @return mixed The value at the key in the source sequence.
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
     * <p><b>Syntax</b>: elementAtOrDefault (key [, default])
     * <p>Returns the value at a specified key in a sequence or a default value if the key is not found.
     * <p>If the type of source iterator implements {@link ArrayAccess}, that implementation is used to obtain the value at the specified key. Otherwise, this method obtains the specified value.
     * @param mixed $key The key of the value to retrieve.
     * @param mixed $default Value to return if sequence does not contain value with specified key. Default: null.
     * @return mixed default value if the key is not found in the source sequence; otherwise, the value at the specified key in the source sequence.
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

    /**
     * <p><b>Syntax</b>: first ()
     * <p>Returns the first element of a sequence.
     * <p>The first method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link firstOrDefault} method.
     * <p><b>Syntax</b>: first (predicate {{(v, k) ==> result})
     * <p>Returns the first element in a sequence that satisfies a specified condition.
     * <p>The first method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link firstOrDefault} method.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \InvalidArgumentException If source contains no matching elements.
     * @return mixed If predicate is null: the first element in the specified sequence. If predicate is not null: The first element in the sequence that passes the test in the specified predicate function.
     */
    public function first ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k))
                return $v;
        }
        throw new \InvalidArgumentException(self::ERROR_NO_MATCHES);
    }

    /**
     * <p><b>Syntax</b>: firstOrDefault ([default])
     * <p>Returns the first element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrDefault ([default [, predicate {{(v, k) ==> result}]])
     * <p>Returns the first element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link firstOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the first element in source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the first element in source that passes the test specified by predicate.
     */
    public function firstOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k))
                return $v;
        }
        return $default;
    }

    /**
     * <p><b>Syntax</b>: firstOrFallback ([fallback])
     * <p>Returns the first element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrFallback ([fallback [, predicate {{(v, k) ==> result}]])
     * <p>Returns the first element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the firstOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link firstOrDefault}.
     * @param mixed $fallback A fallback function to return the default element.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the first element in source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the first element in source that passes the test specified by predicate.
     */
    public function firstOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k))
                return $v;
        }
        return call_user_func($fallback);
    }

    /**
     * <p><b>Syntax</b>: last ()
     * <p>Returns the last element of a sequence.
     * <p>The last method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link lastOrDefault} method.
     * <p><b>Syntax</b>: last (predicate {{(v, k) ==> result})
     * <p>Returns the last element in a sequence that satisfies a specified condition.
     * <p>The last method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link lastOrDefault} method.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \InvalidArgumentException If source contains no matching elements.
     * @return mixed If predicate is null: the last element in the specified sequence. If predicate is not null: The last element in the sequence that passes the test in the specified predicate function.
     */
    public function last ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        if (!$found)
            throw new \InvalidArgumentException(self::ERROR_NO_MATCHES);
        return $value;
    }

    /**
     * <p><b>Syntax</b>: lastOrDefault ([default])
     * <p>Returns the last element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrDefault ([default [, predicate {{(v, k) ==> result}]])
     * <p>Returns the last element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link lastOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the last element in source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the last element in source that passes the test specified by predicate.
     */
    public function lastOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : $default;
    }

    /**
     * <p><b>Syntax</b>: lastOrFallback ([fallback])
     * <p>Returns the last element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrFallback ([fallback [, predicate {{(v, k) ==> result}]])
     * <p>Returns the last element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the lastOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link lastOrDefault}.
     * @param mixed $fallback A fallback function to return the default element.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the last element in source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the last element in source that passes the test specified by predicate.
     */
    public function lastOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : call_user_func($fallback);
    }

    /**
     * <p><b>Syntax</b>: single ()
     * <p>Returns the only element of a sequence, and throws an exception if there is not exactly one element in the sequence.
     * <p>The single method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link singleOrDefault} method.
     * <p><b>Syntax</b>: single (predicate {{(v, k) ==> result})
     * <p>Returns the only element of a sequence that satisfies a specified condition.
     * <p>The single method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link singleOrDefault} method.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \InvalidArgumentException If source contains no matching elements or more than one matching element.
     * @return mixed If predicate is null: the single element of the input sequence. If predicate is not null: The single element of the sequence that passes the test in the specified predicate function.
     */
    public function single ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $v = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                if ($found)
                    throw new \InvalidArgumentException(self::ERROR_MANY_MATCHES);
                $found = true;
            }
        }
        if (!$found)
            throw new \InvalidArgumentException(self::ERROR_NO_MATCHES);
        return $v;
    }

    /**
     * <p><b>Syntax</b>: singleOrDefault ([default])
     * <p>Returns the only element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrDefault ([default [, predicate {{(v, k) ==> result}]])
     * <p>Returns the only element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link singleOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \InvalidArgumentException If source contains more than one matching element.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the single element of the source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the single element of the source that passes the test specified by predicate.
     */
    public function singleOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $v = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                if ($found)
                    throw new \InvalidArgumentException(self::ERROR_MANY_MATCHES);
                $found = true;
            }
        }
        return $found ? $v : $default;
    }

    /**
     * <p><b>Syntax</b>: singleOrFallback ([fallback])
     * <p>Returns the only element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrFallback ([fallback [, predicate {{(v, k) ==> result}]])
     * <p>Returns the only element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the singleOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link singleOrDefault}.
     * @param mixed $fallback A fallback function to return the default element.
     * @param callback $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \InvalidArgumentException If source contains more than one matching element.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the single element of the source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the single element of the source that passes the test specified by predicate.
     */
    public function singleOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $v = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k)) {
                if ($found)
                    throw new \InvalidArgumentException(self::ERROR_MANY_MATCHES);
                $found = true;
            }
        }
        return $found ? $v : call_user_func($fallback);
    }

    public function indexOf ($value)
    {
        foreach ($this as $k => $v) {
            if ($v === $value)
                return $k;
        }
        return null; // not -1
    }

    public function indexWhere ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k))
                return $k;
        }
        return null; // not -1
    }

    public function lastIndexOf ($value)
    {
        $key = null;
        foreach ($this as $k => $v) {
            if ($v === $value)
                $key = $k;
        }
        return $key; // not -1
    }

    public function lastIndexWhere ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $key = null;
        foreach ($this as $k => $v) {
            if (call_user_func($predicate, $v, $k))
                $key = $k;
        }
        return $key; // not -1
    }

    public function skip ($count)
    {
        if ($count < 0)
            throw new \InvalidArgumentException(self::ERROR_COUNT_LESS_THAN_ZERO);

        $self = $this;

        return new Enumerable(function () use ($self, $count)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();
            for ($i = 0; $i < $count && $it->valid(); ++$i)
                $it->next();

            return new Enumerator(function ($yield) use ($it)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                $yield($it->current(), $it->key());
                $it->next();
                return true;
            });
        });
    }

    public function skipWhile ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();
            while ($it->valid() && call_user_func($predicate, $it->current(), $it->key()))
                $it->next();

            return new Enumerator(function ($yield) use ($it)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                $yield($it->current(), $it->key());
                $it->next();
                return true;
            });
        });
    }

    public function take ($count)
    {
        if ($count < 0)
            throw new \InvalidArgumentException(self::ERROR_COUNT_LESS_THAN_ZERO);

        $self = $this;

        return new Enumerable(function () use ($self, $count)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();
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

    public function takeWhile ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();

            return new Enumerator(function ($yield) use ($it, &$i, $predicate)
            {
                /** @var $it \Iterator */
                if (!$it->valid() || !call_user_func($predicate, $it->current(), $it->key()))
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

    public function toDictionary ()
    {
        return $this->toArray();
    }

    public function toJSON ($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toList ()
    {
        $array = array();
        foreach ($this as $v)
            $array[] = $v;
        return $array;
    }

    public function toLookup ($keySelector = null, $valueSelector = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$identity);

        $lookup = new c\Lookup();
        foreach ($this as $k => $v)
            $lookup->append(call_user_func($keySelector, $v, $k), call_user_func($valueSelector, $v, $k));
        return $lookup;
    }

    public function toKeys ()
    {
        $self = $this;

        return new Enumerable(function () use ($self)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();
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

    public function toObject ($keySelector = null, $valueSelector = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$identity);

        $obj = new \stdClass();
        foreach ($this as $k => $v)
            $obj->{call_user_func($keySelector, $v, $k)} = call_user_func($valueSelector, $v, $k);
        return $obj;
    }

    public function toString ($separator = '', $selector = null)
    {
        if ($selector === null) {
            /** @var $it \Iterator|\ArrayIterator */
            $it = $this->getIterator();
            if ($it instanceof \ArrayIterator)
                return implode($separator, $it->getArrayCopy());
        }

        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        return implode($separator, $this->select($selector)->toArray());
    }

    public function toValues ()
    {
        $self = $this;

        return new Enumerable(function () use ($self)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();
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

    #endregion

    #region Actions

    public function doEnum ($action)
    {
        $self = $this;
        $action = Utils::createLambda($action, 'v,k');

        return new Enumerable(function () use ($self, $action)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            $it->rewind();

            return new Enumerator(function ($yield) use ($it, $action)
            {
                /** @var $it \Iterator */
                if (!$it->valid())
                    return false;
                call_user_func($action, $it->current(), $it->key());
                $yield($it->current(), $it->key());
                $it->next();
                return true;
            });
        });
    }

    public function forEachEnum ($action = null)
    {
        $action = Utils::createLambda($action, 'v,k', Functions::$blank);

        foreach ($this as $k => $v)
            call_user_func($action, $v, $k);
    }

    public function write ($separator = '', $selector = null)
    {
        echo $this->toString($separator, $selector);
    }

    public function writeLine ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        foreach ($this as $k => $v) {
            echo call_user_func($selector, $v, $k);
            echo PHP_EOL;
        }
    }

    #endregion
}
