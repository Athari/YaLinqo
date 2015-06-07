<?php

/**
 * Enumerable class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

use YaLinqo;

// Differences: preserving keys and toSequental, *Enum for keywords, no (el,i) overloads, string lambda args (v,k,a,b,e etc.), toArray/toList/toDictionary, objects as keys, docs copied and may be incorrect, elementAt uses key instead of index, @throws doc incomplete, aggregater default seed is null not undefined, call/each, InvalidOperationException => UnexpectedValueException

/**
 * A sequence of values indexed by keys, the primary class of YaLinqo.
 * <p>A sequence of values indexed by keys, which supports various operations: generation, projection, filtering, ordering, joining, grouping, aggregation etc.
 * <p>To create a Enumerable, call {@link Enumerable::from} (aliased as a global function {@link from}) or any of the generation functions. To convert to array, call {@link Enumerable::toArrayDeep} or any of the conversion functions.
 * <p>Internally, it is a wrapper around a lazily created iterator. The wrapped iterator is evaluated when {@link getIterator} is called.
 * @see from
 * @package YaLinqo
 */
class Enumerable implements \IteratorAggregate
{
    /** Error message: "Sequence contains no elements." */
    const ERROR_NO_ELEMENTS = 'Sequence contains no elements.';
    /** Error message: "Sequence contains no matching elements." */
    const ERROR_NO_MATCHES = 'Sequence contains no matching elements.';
    /** Error message: "Sequence does not contain the key." */
    const ERROR_NO_KEY = 'Sequence does not contain the key.';
    /** Error message: "Sequence contains more than one element." */
    const ERROR_MANY_ELEMENTS = 'Sequence contains more than one element.';
    /** Error message: "Sequence contains more than one matching element." */
    const ERROR_MANY_MATCHES = 'Sequence contains more than one matching element.';
    /** Error message: "count must be a non-negative value." */
    const ERROR_COUNT_LESS_THAN_ZERO = 'count must be a non-negative value.';
    /** Error message: "step must be a positive value." */
    const ERROR_STEP_NEGATIVE = 'step must be a positive value.';
    /** Error message: "type must by one of built-in types." */
    const ERROR_UNSUPPORTED_BUILTIN_TYPE = 'type must by one of built-in types.';

    /**
     * Wrapped iterator.
     * @var \Iterator
     */
    private $iterator;

    /**
     * @internal
     * @param \Closure|\Iterator $iterator
     * @param bool $isClosure
     */
    private function __construct ($iterator, $isClosure = true)
    {
        $this->iterator = $isClosure ? $iterator() : $iterator;
    }

    /**
     * Retrieve an external iterator.
     * {@inheritdoc}
     * @return \Iterator
     */
    public function getIterator ()
    {
        return $this->iterator;
    }

    #region Generation

    /**
     * Cycles through the source sequence.
     * <p><b>Syntax</b>: cycle (source)
     * <p>Source keys are discarded.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $source Source sequence.
     * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
     * @throws \UnexpectedValueException If source contains no elements (checked during enumeration).
     * @return Enumerable Endless list of items repeating the source sequence.
     * @package YaLinqo\Generation
     */
    public static function cycle ($source)
    {
        $source = self::from($source);

        return new self(function () use ($source) {
            $isEmpty = true;
            while (true) {
                foreach ($source as $v) {
                    yield $v;
                    $isEmpty = false;
                }
                if ($isEmpty)
                    throw new \UnexpectedValueException(self::ERROR_NO_ELEMENTS);
            }
        });
    }

    /**
     * Returns an empty sequence.
     * <p><b>Syntax</b>: emptyEnum ()
     * @return Enumerable
     * @package YaLinqo\Generation
     */
    public static function emptyEnum ()
    {
        return self::from(new \EmptyIterator);
    }

    /**
     * Converts source into Enumerable sequence.
     * <p><b>Syntax</b>: from (source)
     * <p>Result depends on the type of source:
     * <ul>
     * <li><b>array</b>: Enumerable from ArrayIterator;
     * <li><b>Enumerable</b>: Enumerable source itself;
     * <li><b>Iterator</b>: Enumerable from Iterator;
     * <li><b>IteratorAggregate</b>: Enumerable from Iterator returned from getIterator() method.
     * </ul>
     * @param array|\Iterator|\IteratorAggregate|Enumerable $source Value to convert into Enumerable sequence.
     * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
     * @return Enumerable
     * @package YaLinqo\Generation
     */
    public static function from ($source)
    {
        $it = null;
        if ($source instanceof Enumerable)
            return $source;
        else if (is_array($source))
            $it = new \ArrayIterator($source);
        elseif ($source instanceof \Iterator)
            $it = $source;
        elseif ($source instanceof \IteratorAggregate)
            $it = $source->getIterator();
        if ($it !== null) {
            return new self($it, false);
        }
        throw new \InvalidArgumentException('source must be array or Traversable or Enumerable.');
    }

    /**
     * Generates a sequence by mimicking a for loop.
     * <p><b>Syntax</b>: generate (funcValue {(v, k) ==> value} [, seedValue [, funcKey {(v, k) ==> key} [, seedKey]]])
     * <p>If seedValue is null, the first value will be the result of calling funcValue on seedValue and seedKey. The same applies for seedKey.
     * @param callable $funcValue {(v, k) ==> value} State update function to run on value after every iteration of the generator loop. Default: value.
     * @param mixed $seedValue Initial state of the generator loop for values. Default: null.
     * @param callable|null $funcKey {(v, k) ==> key} State update function to run on key after every iteration of the generator loop. Default: increment.
     * @param mixed $seedKey Initial state of the generator loop ofr keys. Default: 0.
     * @return Enumerable
     * @package YaLinqo\Generation
     */
    public static function generate ($funcValue, $seedValue = null, $funcKey = null, $seedKey = null)
    {
        $funcValue = Utils::createLambda($funcValue, 'v,k');
        $funcKey = Utils::createLambda($funcKey, 'v,k', false);

        return new self(function () use ($funcValue, $funcKey, $seedValue, $seedKey) {
            $key = $seedKey === null ? ($funcKey ? $funcKey($seedValue, $seedKey) : 0) : $seedKey;
            $value = $seedValue === null ? $funcValue($seedValue, $seedKey) : $seedValue;
            yield $key => $value;
            while (true) {
                list($value, $key) = [
                    $funcValue($value, $key),
                    $funcKey ? $funcKey($value, $key) : $key + 1,
                ];
                yield $key => $value;
            }
        });
    }

    /**
     * Generates a sequence of integral numbers to infinity.
     * <p><b>Syntax</b>: toInfinity ([start [, step]])
     * @param int $start The first integer in the sequence. Default: 0.
     * @param int $step The difference between adjacent integers. Default: 1.
     * @return Enumerable
     * @package YaLinqo\Generation
     */
    public static function toInfinity ($start = 0, $step = 1)
    {
        return new self(function () use ($start, $step) {
            $value = $start - $step;
            while (true)
                yield $value += $step;
        });
    }

    /**
     * Searches subject for all matches to the regular expression given in pattern and enumerates them in the order specified by flags. After the first match is found, the subsequent searches are continued on from end of the last match.
     * <p><b>Syntax</b>: matches (subject, pattern [, flags])
     * @param string $subject The input string.
     * @param string $pattern The pattern to search for, as a string.
     * @param int $flags Can be a combination of the following flags: PREG_PATTERN_ORDER, PREG_SET_ORDER, PREG_OFFSET_CAPTURE. Default: PREG_SET_ORDER.
     * @return Enumerable
     * @see preg_match_all
     * @package YaLinqo\Generation
     */
    public static function matches ($subject, $pattern, $flags = PREG_SET_ORDER)
    {
        return new self(function () use ($subject, $pattern, $flags) {
            preg_match_all($pattern, $subject, $matches, $flags);
            return $matches !== false ? self::from($matches)->getIterator() : self::emptyEnum();
        });
    }

    /**
     * Generates a sequence of integral numbers to negative infinity.
     * <p><b>Syntax</b>: toNegativeInfinity ([start [, step]])
     * @param int $start The first integer in the sequence. Default: 0.
     * @param int $step The difference between adjacent integers. Default: 1.
     * @return Enumerable
     * @package YaLinqo\Generation
     */
    public static function toNegativeInfinity ($start = 0, $step = 1)
    {
        return self::toInfinity($start, -$step);
    }

    /**
     * Returns a sequence that contains a single element with a specified value.
     * <p><b>Syntax</b>: returnEnum (element)
     * @param mixed $element The single element in the resulting sequence.
     * @return Enumerable Observable sequence containing the single specified element.
     * @package YaLinqo\Generation
     */
    public static function returnEnum ($element)
    {
        return self::repeat($element, 1);
    }

    /**
     * Generates a sequence of integral numbers, beginning with start and containing count elements.
     * <p><b>Syntax</b>: range (start, count [, step])
     * <p>Keys in the generated sequence are sequental: 0, 1, 2 etc.
     * <p>Example: range(3, 4, 2) = 3, 5, 7, 9.
     * @param int $start The value of the first integer in the sequence.
     * @param int $count The number of integers to generate.
     * @param int $step The difference between adjacent integers. Default: 1.
     * @return Enumerable A sequence that contains a range of integral numbers.
     * @package YaLinqo\Generation
     */
    public static function range ($start, $count, $step = 1)
    {
        if ($count <= 0)
            return self::emptyEnum();
        return new self(function () use ($start, $count, $step) {
            $value = $start - $step;
            while ($count-- > 0)
                yield $value += $step;
        });
    }

    /**
     * Generates a reversed sequence of integral numbers, beginning with start and containing count elements.
     * <p><b>Syntax</b>: rangeDown (start, count [, step])
     * <p>Keys in the generated sequence are sequental: 0, 1, 2 etc.
     * <p>Example: rangeDown(9, 4, 2) = 9, 7, 5, 3.
     * @param int $start The value of the first integer in the sequence.
     * @param int $count The number of integers to generate.
     * @param int $step The difference between adjacent integers. Default: 1.
     * @return Enumerable A sequence that contains a range of integral numbers.
     * @package YaLinqo\Generation
     */
    public static function rangeDown ($start, $count, $step = 1)
    {
        return self::range($start, $count, -$step);
    }

    /**
     * Generates a sequence of integral numbers within a specified range from start to end.
     * <p><b>Syntax</b>: rangeTo (start, end [, step])
     * <p>Keys in the generated sequence are sequental: 0, 1, 2 etc.
     * <p>Example: rangeTo(3, 9, 2) = 3, 5, 7, 9.
     * @param int $start The value of the first integer in the sequence.
     * @param int $end The value of the last integer in the sequence (not included).
     * @param int $step The difference between adjacent integers. Default: 1.
     * @throws \InvalidArgumentException If step is not a positive number.
     * @return Enumerable A sequence that contains a range of integral numbers.
     * @package YaLinqo\Generation
     */
    public static function rangeTo ($start, $end, $step = 1)
    {
        if ($step <= 0)
            throw new \InvalidArgumentException(self::ERROR_STEP_NEGATIVE);
        return new self(function () use ($start, $end, $step) {
            if ($start <= $end) {
                for ($i = $start; $i < $end; $i += $step)
                    yield $i;
            }
            else {
                for ($i = $start; $i > $end; $i -= $step)
                    yield $i;
            }
        });
    }

    /**
     * Generates an sequence that contains one repeated value.
     * <p><b>Syntax</b>: repeat (element)
     * <p>Generates an endless sequence that contains one repeated value.
     * <p><b>Syntax</b>: repeat (element, count)
     * <p>Generates a sequence of specified length that contains one repeated value.
     * <p>Keys in the generated sequence are sequental: 0, 1, 2 etc.
     * @param int $element The value to be repeated.
     * @param int $count The number of times to repeat the value in the generated sequence. Default: null.
     * @throws \InvalidArgumentException If count is less than 0.
     * @return Enumerable A sequence that contains a repeated value.
     * @package YaLinqo\Generation
     */
    public static function repeat ($element, $count = null)
    {
        if ($count < 0)
            throw new \InvalidArgumentException(self::ERROR_COUNT_LESS_THAN_ZERO);
        return new self(function () use ($element, $count) {
            for ($i = 0; $i < $count || $count === null; $i++)
                yield $element;
        });
    }

    /**
     * Split the given string by a regular expression.
     * <p><b>Syntax</b>: split (subject, pattern [, flags])
     * @param string $subject The input string.
     * @param string $pattern The pattern to search for, as a string.
     * @param int $flags flags can be any combination of the following flags: PREG_SPLIT_NO_EMPTY, PREG_SPLIT_DELIM_CAPTURE, PREG_SPLIT_OFFSET_CAPTURE. Default: 0.
     * @return Enumerable
     * @see preg_split
     * @package YaLinqo\Generation
     */
    public static function split ($subject, $pattern, $flags = 0)
    {
        return new self(
            new \ArrayIterator(preg_split($pattern, $subject, -1, $flags)),
            false
        );
    }

    #endregion

    #region Projection and filtering

    /**
     * Casts the elements of a sequence to the specified type.
     * <p><b>Syntax</b>: cast (type)
     * <p>The cast method causes an error if an element cannot be cast (exact error depends on the implementation of PHP casting), to get only elements of the specified type, use {@link ofType}.
     * @param string $type The type to cast the elements to. Can be one of the built-in types: array, int (integer, long), float (real, double), null (unset), object, string.
     * @return Enumerable An sequence that contains each element of the source sequence cast to the specified type.
     * @link http://php.net/manual/language.types.type-juggling.php Type Juggling
     * @package YaLinqo\Projection and filtering
     */
    public function cast ($type)
    {
        switch ($type) {
            case 'array':
                return $this->select(function ($v) { return (array)$v; });
            case 'int':
            case 'integer':
            case 'long':
                return $this->select(function ($v) { return (int)$v; });
            case 'float':
            case 'real':
            case 'double':
                return $this->select(function ($v) { return (float)$v; });
            case 'null':
            case 'unset':
                return $this->select(function ($v) { return (unset)$v; });
            case 'object':
                return $this->select(function ($v) { return (object)$v; });
            case 'string':
                return $this->select(function ($v) { return (string)$v; });
            default:
                throw new \InvalidArgumentException(self::ERROR_UNSUPPORTED_BUILTIN_TYPE);
        }
    }

    /**
     * Filters the elements of a sequence based on a specified type.
     * <p><b>Syntax</b>: ofType (type)
     * <p>The ofType method returns only elements of the specified type. To instead receive an error if an element cannot be cast, use {@link cast}.
     * @param string $type The type to filter the elements of the sequence on. Can be either class name or one of the predefined types: array, int (integer, long), callable (callable), float (real, double), null, string, object, numeric, scalar.
     * @return Enumerable A sequence that contains elements from the input sequence of the specified type.
     * @package YaLinqo\Projection and filtering
     */
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
            case 'callback':
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
     * Projects each element of a sequence into a new form.
     * <p><b>Syntax</b>: select (selectorValue {(v, k) ==> result} [, selectorKey {(v, k) ==> result}])
     * <p>This projection method requires the transform functions, selectorValue and selectorKey, to produce one key-value pair for each value in the source sequence. If selectorValue returns a value that is itself a collection, it is up to the consumer to traverse the subsequences manually. In such a situation, it might be better for your query to return a single coalesced sequence of values. To achieve this, use the {@link selectMany()} method instead of select. Although selectMany works similarly to select, it differs in that the transform function returns a collection that is then expanded by selectMany before it is returned.
     * @param callable $selectorValue {(v, k) ==> value} A transform function to apply to each value.
     * @param callable|null $selectorKey {(v, k) ==> key} A transform function to apply to each key. Default: key.
     * @return Enumerable A sequence whose elements are the result of invoking the transform functions on each element of source.
     * @package YaLinqo\Projection and filtering
     */
    public function select ($selectorValue, $selectorKey = null)
    {
        $selectorValue = Utils::createLambda($selectorValue, 'v,k');
        $selectorKey = Utils::createLambda($selectorKey, 'v,k', Functions::$key);

        return new self(function () use ($selectorValue, $selectorKey) {
            foreach ($this as $k => $v)
                yield $selectorKey($v, $k) => $selectorValue($v, $k);
        });
    }

    /**
     * Projects each element of a sequence to a sequence and flattens the resulting sequences into one sequence.
     * <p><b>Syntax</b>: selectMany ()
     * <p>The selectMany method enumerates the input sequence, where each element is a sequence, and then enumerates and yields the elements of each such sequence. That is, for each element of source, selectorValue and selectorKey are invoked and a sequence of key-value pairs is returned. selectMany then flattens this two-dimensional collection of collections into a one-dimensional sequence and returns it. For example, if a query uses selectMany to obtain the orders for each customer in a database, the result is a sequence of orders. If instead the query uses {@link select} to obtain the orders, the collection of collections of orders is not combined and the result is a sequence of sequences of orders.
     * <p><b>Syntax</b>: selectMany (collectionSelector {(v, k) ==> enum})
     * <p>The selectMany method enumerates the input sequence, uses transform functions to map each element to a sequence, and then enumerates and yields the elements of each such sequence.
     * <p><b>Syntax</b>: selectMany (collectionSelector {(v, k) ==> enum} [, resultSelectorValue {(v, k1, k2) ==> value} [, resultSelectorKey {(v, k1, k2) ==> key}]])
     * <p>Projects each element of a sequence to a sequence, flattens the resulting sequences into one sequence, and invokes a result selector functions on each element therein.
     * <p>The selectMany method is useful when you have to keep the elements of source in scope for query logic that occurs after the call to selectMany. If there is a bidirectional relationship between objects in the source sequence and objects returned from collectionSelector, that is, if a sequence returned from collectionSelector provides a property to retrieve the object that produced it, you do not need this overload of selectMany. Instead, you can use simpler selectMany overload and navigate back to the source object through the returned sequence.
     * @param callable $collectionSelector {(v, k) ==> enum} A transform function to apply to each element.
     * @param callable|null $resultSelectorValue {(v, k1, k2) ==> value} A transform function to apply to each value of the intermediate sequence. Default: {(v, k1, k2) ==> v}.
     * @param callable|null $resultSelectorKey {(v, k1, k2) ==> key} A transform function to apply to each key of the intermediate sequence. Default: increment.
     * @return Enumerable A sequence whose elements are the result of invoking the one-to-many transform function on each element of the input sequence.
     * @package YaLinqo\Projection and filtering
     */
    public function selectMany ($collectionSelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $collectionSelector = Utils::createLambda($collectionSelector, 'v,k', Functions::$value);
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v,k1,k2', Functions::$value);
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v,k1,k2', false);
        if ($resultSelectorKey === false)
            $resultSelectorKey = Functions::increment();

        return new self(function () use ($collectionSelector, $resultSelectorValue, $resultSelectorKey) {
            foreach ($this as $ok => $ov)
                foreach ($collectionSelector($ov, $ok) as $ik => $iv)
                    yield $resultSelectorKey($iv, $ok, $ik) => $resultSelectorValue($iv, $ok, $ik);
        });
    }

    /**
     * Filters a sequence of values based on a predicate.
     * <p><b>Syntax</b>: where (predicate {(v, k) ==> result})
     * @param callable $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return Enumerable A sequence that contains elements from the input sequence that satisfy the condition.
     * @package YaLinqo\Projection and filtering
     */
    public function where ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new self(function () use ($predicate) {
            foreach ($this as $k => $v)
                if ($predicate($v, $k))
                    yield $k => $v;
        });
    }

    #endregion

    #region Ordering

    /**
     * Sorts the elements of a sequence in a particular direction (ascending, descending) according to a key.
     * <p><b>Syntax</b>: orderByDir (false|true [, {(v, k) ==> key} [, {(a, b) ==> diff}]])
     * <p>Three methods are defined to extend the type {@link OrderedEnumerable}, which is the return type of this method. These three methods, namely {@link OrderedEnumerable::thenBy thenBy}, {@link OrderedEnumerable::thenByDescending thenByDescending} and {@link OrderedEnumerable::thenByDir thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from Enumerable, you can call {@link orderBy}, {@link orderByDescending} or {@link orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return OrderedEnumerable
     * @package YaLinqo\Ordering
     */
    public function orderByDir ($desc, $keySelector = null, $comparer = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        return new OrderedEnumerable($this, $desc, $keySelector, $comparer);
    }

    /**
     * Sorts the elements of a sequence in ascending order according to a key.
     * <p><b>Syntax</b>: orderBy ([{(v, k) ==> key} [, {(a, b) ==> diff}]])
     * <p>Three methods are defined to extend the type {@link OrderedEnumerable}, which is the return type of this method. These three methods, namely {@link OrderedEnumerable::thenBy thenBy}, {@link OrderedEnumerable::thenByDescending thenByDescending} and {@link OrderedEnumerable::thenByDir thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from Enumerable, you can call {@link orderBy}, {@link orderByDescending} or {@link orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return OrderedEnumerable
     * @package YaLinqo\Ordering
     */
    public function orderBy ($keySelector = null, $comparer = null)
    {
        return $this->orderByDir(false, $keySelector, $comparer);
    }

    /**
     * Sorts the elements of a sequence in descending order according to a key.
     * <p><b>Syntax</b>: orderByDescending ([{(v, k) ==> key} [, {(a, b) ==> diff}]])
     * <p>Three methods are defined to extend the type {@link OrderedEnumerable}, which is the return type of this method. These three methods, namely {@link OrderedEnumerable::thenBy thenBy}, {@link OrderedEnumerable::thenByDescending thenByDescending} and {@link OrderedEnumerable::thenByDir thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from Enumerable, you can call {@link orderBy}, {@link orderByDescending} or {@link orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return OrderedEnumerable
     * @package YaLinqo\Ordering
     */
    public function orderByDescending ($keySelector = null, $comparer = null)
    {
        return $this->orderByDir(true, $keySelector, $comparer);
    }

    #endregion

    #region Joining and grouping

    /**
     * Correlates the elements of two sequences based on equality of keys and groups the results.
     * <p><b>Syntax</b>: groupJoin (inner [, outerKeySelector {(v, k) ==> key} [, innerKeySelector {(v, k) ==> key} [, resultSelectorValue {(v, e, k) ==> value} [, resultSelectorKey {(v, e, k) ==> key}]]]])
     * <p>GroupJoin produces hierarchical results, which means that elements from outer are paired with collections of matching elements from inner. GroupJoin enables you to base your results on a whole set of matches for each element of outer. If there are no correlated elements in inner for a given element of outer, the sequence of matches for that element will be empty but will still appear in the results.
     * <p>The resultSelectorValue and resultSelectorKey functions are called only one time for each outer element together with a collection of all the inner elements that match the outer element. This differs from the {@link join} method, in which the result selector function is invoked on pairs that contain one element from outer and one element from inner. GroupJoin preserves the order of the elements of outer, and for each element of outer, the order of the matching elements from inner.
     * <p>GroupJoin has no direct equivalent in traditional relational database terms. However, this method does implement a superset of inner joins and left outer joins. Both of these operations can be written in terms of a grouped join.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $inner The second (inner) sequence to join to the first (source, outer) sequence.
     * @param callable|null $outerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the first sequence. Default: key.
     * @param callable|null $innerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the second sequence. Default: key.
     * @param callable|null $resultSelectorValue {(v, e, k) ==> value} A function to create a result value from an element from the first sequence and a collection of matching elements from the second sequence. Default: {(v, e, k) ==> array(v, e)}.
     * @param callable|null $resultSelectorKey {(v, e, k) ==> key} A function to create a result key from an element from the first sequence and a collection of matching elements from the second sequence. Default: {(v, e, k) ==> k} (keys returned by outerKeySelector and innerKeySelector functions).
     * @return Enumerable A sequence that contains elements that are obtained by performing a grouped join on two sequences.
     * @package YaLinqo\Joining and grouping
     */
    public function groupJoin ($inner, $outerKeySelector = null, $innerKeySelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $inner = self::from($inner);
        $outerKeySelector = Utils::createLambda($outerKeySelector, 'v,k', Functions::$key);
        $innerKeySelector = Utils::createLambda($innerKeySelector, 'v,k', Functions::$key);
        /** @noinspection PhpUnusedParameterInspection */
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v,e,k', function ($v, $e, $k) { return [ $v, $e ]; });
        /** @noinspection PhpUnusedParameterInspection */
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v,e,k', function ($v, $e, $k) { return $k; });

        return new self(function () use ($inner, $outerKeySelector, $innerKeySelector, $resultSelectorValue, $resultSelectorKey) {
            $lookup = $inner->toLookup($innerKeySelector);
            foreach ($this as $k => $v) {
                $key = $outerKeySelector($v, $k);
                $e = isset($lookup[$key]) ? self::from($lookup[$key]) : self::emptyEnum();
                yield $resultSelectorKey($v, $e, $key) => $resultSelectorValue($v, $e, $key);
            }
        });
    }

    /**
     * Correlates the elements of two sequences based on matching keys.
     * <p><b>Syntax</b>: join (inner [, outerKeySelector {(v, k) ==> key} [, innerKeySelector {(v, k) ==> key} [, resultSelectorValue {(v1, v2, k) ==> value} [, resultSelectorKey {(v1, v2, k) ==> key}]]]])
     * <p>A join refers to the operation of correlating the elements of two sources of information based on a common key. Join brings the two information sources and the keys by which they are matched together in one method call. This differs from the use of {@link selectMany}, which requires more than one method call to perform the same operation.
     * <p>Join preserves the order of the elements of the source, and for each of these elements, the order of the matching elements of inner.
     * <p>In relational database terms, the Join method implements an inner equijoin. 'Inner' means that only elements that have a match in the other sequence are included in the results. An 'equijoin' is a join in which the keys are compared for equality. A left outer join operation has no dedicated standard query operator, but can be performed by using the {@link groupJoin} method.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $inner The sequence to join to the source sequence.
     * @param callable|null $outerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the source sequence. Default: key.
     * @param callable|null $innerKeySelector {(v, k) ==> key} A function to extract the join key from each element of the second sequence. Default: key.
     * @param callable|null $resultSelectorValue {(v1, v2, k) ==> result} A function to create a result value from two matching elements. Default: {(v1, v2, k) ==> array(v1, v2)}.
     * @param callable|null $resultSelectorKey {(v1, v2, k) ==> result} A function to create a result key from two matching elements. Default: {(v1, v2, k) ==> k} (keys returned by outerKeySelector and innerKeySelector functions).
     * @return Enumerable
     * @package YaLinqo\Joining and grouping
     */
    public function join ($inner, $outerKeySelector = null, $innerKeySelector = null, $resultSelectorValue = null, $resultSelectorKey = null)
    {
        $inner = self::from($inner);
        $outerKeySelector = Utils::createLambda($outerKeySelector, 'v,k', Functions::$key);
        $innerKeySelector = Utils::createLambda($innerKeySelector, 'v,k', Functions::$key);
        /** @noinspection PhpUnusedParameterInspection */
        $resultSelectorValue = Utils::createLambda($resultSelectorValue, 'v1,v2,k', function ($v1, $v2, $k) { return [ $v1, $v2 ]; });
        /** @noinspection PhpUnusedParameterInspection */
        $resultSelectorKey = Utils::createLambda($resultSelectorKey, 'v1,v2,k', function ($v1, $v2, $k) { return $k; });

        return new self(function () use ($inner, $outerKeySelector, $innerKeySelector, $resultSelectorValue, $resultSelectorKey) {
            $lookup = $inner->toLookup($innerKeySelector);
            foreach ($this as $ok => $ov) {
                $key = $outerKeySelector($ov, $ok);
                if (isset($lookup[$key]))
                    foreach ($lookup[$key] as $iv)
                        yield $resultSelectorKey($ov, $iv, $key) => $resultSelectorValue($ov, $iv, $key);
            }
        });
    }

    /**
     * Groups the elements of a sequence by its keys or a specified key selector function.
     * <p><b>Syntax</b>: groupBy ()
     * <p>Groups the elements of a sequence by its keys.
     * <p><b>Syntax</b>: groupBy (keySelector {(v, k) ==> key})
     * <p>Groups the elements of a sequence according to a specified key selector function.
     * <p><b>Syntax</b>: groupBy (keySelector {(v, k) ==> key}, valueSelector {(v, k) ==> value})
     * <p>Groups the elements of a sequence according to a specified key selector function and projects the elements for each group by using a specified function.
     * <p><b>Syntax</b>: groupBy (keySelector {(v, k) ==> key}, valueSelector {(v, k) ==> value}, resultSelectorValue {(e, k) ==> value} [, resultSelectorKey {(e, k) ==> key}])
     * <p>Groups the elements of a sequence according to a specified key selector function and creates a result value from each group and its key.
     * <p>For all overloads except the last: the groupBy method returns a sequence of sequences, one inner sequence for each distinct key that was encountered. The outer sequence is yielded in an order based on the order of the elements in source that produced the first key of each inner sequence. Elements in a inner sequence are yielded in the order they appear in source.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract the key for each element. Default: key.
     * @param callable|null $valueSelector {(v, k) ==> value} A function to map each source element to a value in the inner sequence.
     * @param callable|null $resultSelectorValue {(e, k) ==> value} A function to create a result value from each group.
     * @param callable|null $resultSelectorKey {(e, k) ==> key} A function to create a result key from each group.
     * @return Enumerable A sequence of sequences indexed by a key.
     * @package YaLinqo\Joining and grouping
     */
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
     * Applies an accumulator function over a sequence. If seed is not null, its value is used as the initial accumulator value.
     * <p><b>Syntax</b>: aggregate (func {(a, v, k) ==> accum} [, seed])
     * <p>Aggregate method makes it simple to perform a calculation over a sequence of values. This method works by calling func one time for each element in source. Each time func is called, aggregate passes both the element from the sequence and an aggregated value (as the first argument to func). If seed is null, the first element of source is used as the initial aggregate value. The result of func replaces the previous aggregated value. Aggregate returns the final result of func.
     * <p>To simplify common aggregation operations, the standard query operators also include a general purpose count method, {@link count}, and four numeric aggregation methods, namely {@link min}, {@link max}, {@link sum}, and {@link average}.
     * @param callable $func {(a, v, k) ==> accum} An accumulator function to be invoked on each element.
     * @param mixed $seed If seed is not null, the first element is used as seed. Default: null.
     * @throws \UnexpectedValueException If seed is null and sequence contains no elements.
     * @return mixed The final accumulator value.
     * @package YaLinqo\Aggregation
     */
    public function aggregate ($func, $seed = null)
    {
        $func = Utils::createLambda($func, 'a,v,k');

        $result = $seed;
        if ($seed !== null) {
            foreach ($this as $k => $v) {
                $result = $func($result, $v, $k);
            }
        }
        else {
            $assigned = false;
            foreach ($this as $k => $v) {
                if ($assigned) {
                    $result = $func($result, $v, $k);
                }
                else {
                    $result = $v;
                    $assigned = true;
                }
            }
            if (!$assigned)
                throw new \UnexpectedValueException(self::ERROR_NO_ELEMENTS);
        }
        return $result;
    }

    /**
     * Applies an accumulator function over a sequence. If seed is not null, its value is used as the initial accumulator value.
     * <p>aggregateOrDefault (func {(a, v, k) ==> accum} [, seed [, default]])
     * <p>Aggregate method makes it simple to perform a calculation over a sequence of values. This method works by calling func one time for each element in source. Each time func is called, aggregate passes both the element from the sequence and an aggregated value (as the first argument to func). If seed is null, the first element of source is used as the initial aggregate value. The result of func replaces the previous aggregated value. Aggregate returns the final result of func. If source sequence is empty, default is returned.
     * <p>To simplify common aggregation operations, the standard query operators also include a general purpose count method, {@link count}, and four numeric aggregation methods, namely {@link min}, {@link max}, {@link sum}, and {@link average}.
     * @param callable $func {(a, v, k) ==> accum} An accumulator function to be invoked on each element.
     * @param mixed $seed If seed is not null, the first element is used as seed. Default: null.
     * @param mixed $default Value to return if sequence is empty. Default: null.
     * @return mixed The final accumulator value, or default if sequence is empty.
     * @package YaLinqo\Aggregation
     */
    public function aggregateOrDefault ($func, $seed = null, $default = null)
    {
        $func = Utils::createLambda($func, 'a,v,k');
        $result = $seed;
        $assigned = false;

        if ($seed !== null) {
            foreach ($this as $k => $v) {
                $result = $func($result, $v, $k);
                $assigned = true;
            }
        }
        else {
            foreach ($this as $k => $v) {
                if ($assigned) {
                    $result = $func($result, $v, $k);
                }
                else {
                    $result = $v;
                    $assigned = true;
                }
            }
        }
        return $assigned ? $result : $default;
    }

    /**
     * Computes the average of a sequence of numeric values.
     * <p><b>Syntax</b>: average ()
     * <p>Computes the average of a sequence of numeric values.
     * <p><b>Syntax</b>: average (selector {(v, k) ==> result})
     * <p>Computes the average of a sequence of numeric values that are obtained by invoking a transform function on each element of the input sequence.
     * @param callable|null $selector {(v, k) ==> result} A transform function to apply to each element. Default: value.
     * @throws \UnexpectedValueException If sequence contains no elements.
     * @return number The average of the sequence of values.
     * @package YaLinqo\Aggregation
     */
    public function average ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);
        $sum = $count = 0;

        foreach ($this as $k => $v) {
            $sum += $selector($v, $k);
            $count++;
        }
        if ($count === 0)
            throw new \UnexpectedValueException(self::ERROR_NO_ELEMENTS);
        return $sum / $count;
    }

    /**
     * Returns the number of elements in a sequence.
     * <p><b>Syntax</b>: count ()
     * <p>If source iterator implements {@link Countable}, that implementation is used to obtain the count of elements. Otherwise, this method determines the count.
     * <p><b>Syntax</b>: count (predicate {(v, k) ==> result})
     * <p>Returns a number that represents how many elements in the specified sequence satisfy a condition.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: null.
     * @return int The number of elements in the input sequence.
     * @package YaLinqo\Aggregation
     */
    public function count ($predicate = null)
    {
        $it = $this->getIterator();

        if ($it instanceof \Countable && $predicate === null)
            return count($it);

        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$value);
        $count = 0;

        foreach ($it as $k => $v)
            if ($predicate($v, $k))
                $count++;
        return $count;
    }

    /**
     * Returns the maximum value in a sequence of values.
     * <p><b>Syntax</b>: max ()
     * <p>Returns the maximum value in a sequence of values.
     * <p><b>Syntax</b>: max (selector {(v, k) ==> value})
     * <p>Invokes a transform function on each element of a sequence and returns the maximum value.
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @throws \UnexpectedValueException If sequence contains no elements.
     * @return number The maximum value in the sequence.
     * @package YaLinqo\Aggregation
     */
    public function max ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        $max = -PHP_INT_MAX;
        $assigned = false;
        foreach ($this as $k => $v) {
            $max = max($max, $selector($v, $k));
            $assigned = true;
        }
        if (!$assigned)
            throw new \UnexpectedValueException(self::ERROR_NO_ELEMENTS);
        return $max;
    }

    /**
     * Returns the maximum value in a sequence of values, using specified comparer.
     * <p><b>Syntax</b>: maxBy (comparer {(a, b) ==> diff})
     * <p>Returns the maximum value in a sequence of values, using specified comparer.
     * <p><b>Syntax</b>: maxBy (comparer {(a, b) ==> diff}, selector {(v, k) ==> value})
     * <p>Invokes a transform function on each element of a sequence and returns the maximum value, using specified comparer.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @throws \UnexpectedValueException If sequence contains no elements.
     * @return number The maximum value in the sequence.
     * @package YaLinqo\Aggregation
     */
    public function maxBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer) { return $comparer($a, $b) > 0 ? $a : $b; });
    }

    /**
     * Returns the minimum value in a sequence of values.
     * <p><b>Syntax</b>: min ()
     * <p>Returns the minimum value in a sequence of values.
     * <p><b>Syntax</b>: min (selector {(v, k) ==> value})
     * <p>Invokes a transform function on each element of a sequence and returns the minimum value.
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @throws \UnexpectedValueException If sequence contains no elements.
     * @return number The minimum value in the sequence.
     * @package YaLinqo\Aggregation
     */
    public function min ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        $min = PHP_INT_MAX;
        $assigned = false;
        foreach ($this as $k => $v) {
            $min = min($min, $selector($v, $k));
            $assigned = true;
        }
        if (!$assigned)
            throw new \UnexpectedValueException(self::ERROR_NO_ELEMENTS);
        return $min;
    }

    /**
     * Returns the minimum value in a sequence of values, using specified comparer.
     * <p><b>Syntax</b>: minBy (comparer {(a, b) ==> diff})
     * <p>Returns the minimum value in a sequence of values, using specified comparer.
     * <p><b>Syntax</b>: minBy (comparer {(a, b) ==> diff}, selector {(v, k) ==> value})
     * <p>Invokes a transform function on each element of a sequence and returns the minimum value, using specified comparer.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @throws \UnexpectedValueException If sequence contains no elements.
     * @return number The minimum value in the sequence.
     * @package YaLinqo\Aggregation
     */
    public function minBy ($comparer, $selector = null)
    {
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        $enum = $this;

        if ($selector !== null)
            $enum = $enum->select($selector);
        return $enum->aggregate(function ($a, $b) use ($comparer) { return $comparer($a, $b) < 0 ? $a : $b; });
    }

    /**
     * Computes the sum of a sequence of values.
     * <p><b>Syntax</b>: sum ()
     * <p>Computes the sum of a sequence of values.
     * <p><b>Syntax</b>: sum (selector {(v, k) ==> result})
     * <p>Computes the sum of the sequence of values that are obtained by invoking a transform function on each element of the input sequence.
     * <p>This method returns zero if source contains no elements.
     * @param callable|null $selector {(v, k) ==> result} A transform function to apply to each element.
     * @return number The sum of the values in the sequence.
     * @package YaLinqo\Aggregation
     */
    public function sum ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        $sum = 0;
        foreach ($this as $k => $v)
            $sum += $selector($v, $k);
        return $sum;
    }

    #endregion

    #region Sets

    /**
     * Determines whether all elements of a sequence satisfy a condition.
     * <p><b>Syntax</b>: all (predicate {(v, k) ==> result})
     * <p>Determines whether all elements of a sequence satisfy a condition. The enumeration of source is stopped as soon as the result can be determined.
     * @param callable $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return bool true if every element of the source sequence passes the test in the specified predicate, or if the sequence is empty; otherwise, false.
     * @package YaLinqo\Sets
     */
    public function all ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        foreach ($this as $k => $v) {
            if (!$predicate($v, $k))
                return false;
        }
        return true;
    }

    /**
     * Determines whether a sequence contains any elements.
     * <p><b>Syntax</b>: any ()
     * <p>Determines whether a sequence contains any elements. The enumeration of source is stopped as soon as the result can be determined.
     * <p><b>Syntax</b>: any (predicate {(v, k) ==> result})
     * <p>Determines whether any element of a sequence exists or satisfies a condition. The enumeration of source is stopped as soon as the result can be determined.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: null.
     * @return bool If predicate is null: true if the source sequence contains any elements; otherwise, false. If predicate is not null: true if any elements in the source sequence pass the test in the specified predicate; otherwise, false.
     * @package YaLinqo\Sets
     */
    public function any ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', false);

        if ($predicate) {
            foreach ($this as $k => $v) {
                if ($predicate($v, $k))
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
     * Determines whether a sequence contains a specified element.
     * <p><b>Syntax</b>: contains (value)
     * <p>Determines whether a sequence contains a specified element. Enumeration is terminated as soon as a matching element is found.
     * @param $value mixed The value to locate in the sequence.
     * @return bool true if the source sequence contains an element that has the specified value; otherwise, false.
     * @package YaLinqo\Sets
     */
    public function contains ($value)
    {
        foreach ($this as $v) {
            if ($v === $value)
                return true;
        }
        return false;
    }

    /**
     * Returns distinct elements from a sequence.
     * <p>Element keys are values identifying elements. They are used as array keys and are subject to the same rules as array keys, for example, integer 100 and string "100" are considered equal.
     * <p><b>Syntax</b>: distinct ()
     * <p>Returns distinct elements from a sequence using values as element keys.
     * <p><b>Syntax</b>: distinct (keySelector {(v, k) ==> value})
     * <p>Returns distinct elements from a sequence using values produced by keySelector as element keys.
     * @param callable|null $keySelector {(v, k) ==> value} A function to extract the element key from each element. Default: value.
     * @return Enumerable A sequence that contains distinct elements of the input sequence.
     * @package YaLinqo\Sets
     */
    public function distinct ($keySelector = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);

        return new self(function () use ($keySelector) {
            $set = [ ];
            foreach ($this as $k => $v) {
                $key = $keySelector($v, $k);
                if (isset($set[$key]))
                    continue;
                $set[$key] = true;
                yield $k => $v;
            }
        });
    }

    /**
     * Produces the set difference of two sequences. The set difference of two sets is defined as the members of the first set that do not appear in the second set.
     * <p>Element keys are values identifying elements. They are used as array keys and are subject to the same rules as array keys, for example, integer 100 and string "100" are considered equal.
     * <p><b>Syntax</b>: distinct (other)
     * <p>Produces the set difference of two sequences using values as element keys.
     * <p><b>Syntax</b>: distinct (other, keySelector {(v, k) ==> value})
     * <p>Produces the set difference of two sequences using values produced by keySelector as element keys.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $other A sequence whose elements that also occur in the source sequence will cause those elements to be removed from the returned sequence.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract the element key from each element. Default: value.
     * @return Enumerable A sequence that contains the set difference of the elements of two sequences.
     * @package YaLinqo\Sets
     */
    public function except ($other, $keySelector = null)
    {
        $other = self::from($other);
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);

        return new self(function () use ($other, $keySelector) {
            $set = [ ];
            foreach ($other as $k => $v) {
                $key = $keySelector($v, $k);
                $set[$key] = true;
            }
            foreach ($this as $k => $v) {
                $key = $keySelector($v, $k);
                if (isset($set[$key]))
                    continue;
                $set[$key] = true;
                yield $k => $v;
            }
        });
    }

    /**
     * Produces the set intersection of two sequences. The intersection of two sets is defined as the set that contains all the elements of the first set that also appear in the second set, but no other elements.
     * <p>Element keys are values identifying elements. They are used as array keys and are subject to the same rules as array keys, for example, integer 100 and string "100" are considered equal.
     * <p><b>Syntax</b>: intersect (other)
     * <p>Produces the set intersection of two sequences using values as element keys.
     * <p><b>Syntax</b>: intersect (other, keySelector {(v, k) ==> value})
     * <p>Produces the set intersection of two sequences using values produced by keySelector as element keys.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $other A sequence whose distinct elements that also appear in the first sequence will be returned.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract the element key from each element. Default: value.
     * @return Enumerable A sequence that contains the elements that form the set intersection of two sequences.
     * @package YaLinqo\Sets
     */
    public function intersect ($other, $keySelector = null)
    {
        $other = self::from($other);
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);

        return new self(function () use ($other, $keySelector) {
            $set = [ ];
            foreach ($other as $k => $v) {
                $key = $keySelector($v, $k);
                $set[$key] = true;
            }
            foreach ($this as $k => $v) {
                $key = $keySelector($v, $k);
                if (!isset($set[$key]))
                    continue;
                unset($set[$key]);
                yield $k => $v;
            }
        });
    }

    /**
     * Produces the set union of two sequences.
     * <p>Element keys are values identifying elements. They are used as array keys and are subject to the same rules as array keys, for example, integer 100 and string "100" are considered equal.
     * <p>This method excludes duplicates from the return set. This is different behavior to the {@link concat} method, which returns all the elements in the input sequences including duplicates.
     * <p><b>Syntax</b>: union (other)
     * <p>Produces the set union of two sequences using values as element keys.
     * <p><b>Syntax</b>: union (other, keySelector {(v, k) ==> value})
     * <p>Produces the set union of two sequences using values produced by keySelector as element keys.
     * @param array|\Iterator|\IteratorAggregate|Enumerable $other A sequence whose distinct elements form the second set for the union.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract the element key from each element. Default: value.
     * @return Enumerable A sequence that contains the elements from both input sequences, excluding duplicates.
     * @package YaLinqo\Sets
     */
    public function union ($other, $keySelector = null)
    {
        $other = self::from($other);
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);

        return new self(function () use ($other, $keySelector) {
            $set = [ ];
            foreach ($this as $k => $v) {
                $key = $keySelector($v, $k);
                if (isset($set[$key]))
                    continue;
                $set[$key] = true;
                yield $k => $v;
            }
            foreach ($other as $k => $v) {
                $key = $keySelector($v, $k);
                if (isset($set[$key]))
                    continue;
                $set[$key] = true;
                yield $k => $v;
            }
        });
    }

    #endregion

    #region Pagination

    /**
     * Returns the value at a specified key in a sequence.
     * <p><b>Syntax</b>: elementAt (key)
     * <p>Returns the value at a specified key in a sequence.
     * <p>If the type of source iterator implements {@link ArrayAccess}, that implementation is used to obtain the value at the specified key. Otherwise, this method obtains the specified value.
     * <p>This method throws an exception if key is not found. To instead return a default value when the specified key is not found, use the {@link elementAtOrDefault} method.
     * @param mixed $key The key of the value to retrieve.
     * @throws \UnexpectedValueException If sequence does not contain value with specified key.
     * @return mixed The value at the key in the source sequence.
     * @package YaLinqo\Pagination
     */
    public function elementAt ($key)
    {
        /** @var $it \Iterator|\ArrayAccess */
        $it = $this->getIterator();

        if ($it instanceof \ArrayAccess) {
            if (!$it->offsetExists($key))
                throw new \UnexpectedValueException(self::ERROR_NO_KEY);
            return $it->offsetGet($key);
        }

        foreach ($it as $k => $v) {
            if ($k === $key)
                return $v;
        }
        throw new \UnexpectedValueException(self::ERROR_NO_KEY);
    }

    /**
     * Returns the value at a specified key in a sequence or a default value if the key is not found.
     * <p><b>Syntax</b>: elementAtOrDefault (key [, default])
     * <p>If the type of source iterator implements {@link ArrayAccess}, that implementation is used to obtain the value at the specified key. Otherwise, this method obtains the specified value.
     * @param mixed $key The key of the value to retrieve.
     * @param mixed $default Value to return if sequence does not contain value with specified key. Default: null.
     * @return mixed default value if the key is not found in the source sequence; otherwise, the value at the specified key in the source sequence.
     * @package YaLinqo\Pagination
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
     * Returns the first element of a sequence.
     * <p><b>Syntax</b>: first ()
     * <p>Returns the first element of a sequence.
     * <p>The first method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link firstOrDefault} method.
     * <p><b>Syntax</b>: first (predicate {(v, k) ==> result})
     * <p>Returns the first element in a sequence that satisfies a specified condition.
     * <p>The first method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link firstOrDefault} method.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \UnexpectedValueException If source contains no matching elements.
     * @return mixed If predicate is null: the first element in the specified sequence. If predicate is not null: The first element in the sequence that passes the test in the specified predicate function.
     * @package YaLinqo\Pagination
     */
    public function first ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                return $v;
        }
        throw new \UnexpectedValueException(self::ERROR_NO_MATCHES);
    }

    /**
     * Returns the first element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrDefault ([default])
     * <p>Returns the first element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrDefault ([default [, predicate {(v, k) ==> result}]])
     * <p>Returns the first element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link firstOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the first element in source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the first element in source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function firstOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                return $v;
        }
        return $default;
    }

    /**
     * Returns the first element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrFallback ([fallback {() ==> value}])
     * <p>Returns the first element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: firstOrFallback ([fallback {() ==> value} [, predicate {(v, k) ==> result}]])
     * <p>Returns the first element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the firstOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link firstOrDefault}.
     * @param callable $fallback {() ==> value} A fallback function to return the default element.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the first element in source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the first element in source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function firstOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                return $v;
        }
        return $fallback();
    }

    /**
     * Returns the last element of a sequence.
     * <p><b>Syntax</b>: last ()
     * <p>Returns the last element of a sequence.
     * <p>The last method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link lastOrDefault} method.
     * <p><b>Syntax</b>: last (predicate {(v, k) ==> result})
     * <p>Returns the last element in a sequence that satisfies a specified condition.
     * <p>The last method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link lastOrDefault} method.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \UnexpectedValueException If source contains no matching elements.
     * @return mixed If predicate is null: the last element in the specified sequence. If predicate is not null: The last element in the sequence that passes the test in the specified predicate function.
     * @package YaLinqo\Pagination
     */
    public function last ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        if (!$found)
            throw new \UnexpectedValueException(self::ERROR_NO_MATCHES);
        return $value;
    }

    /**
     * Returns the last element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrDefault ([default])
     * <p>Returns the last element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrDefault ([default [, predicate {(v, k) ==> result}]])
     * <p>Returns the last element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link lastOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the last element in source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the last element in source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function lastOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : $default;
    }

    /**
     * Returns the last element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrFallback ([fallback {() ==> value}])
     * <p>Returns the last element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: lastOrFallback ([fallback {() ==> value} [, predicate {(v, k) ==> result}]])
     * <p>Returns the last element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the lastOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link lastOrDefault}.
     * @param callable $fallback {() ==> value} A fallback function to return the default element.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the last element in source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the last element in source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function lastOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : $fallback();
    }

    /**
     * Returns the only element of a sequence, and throws an exception if there is not exactly one element in the sequence.
     * <p><b>Syntax</b>: single ()
     * <p>Returns the only element of a sequence, and throws an exception if there is not exactly one element in the sequence.
     * <p>The single method throws an exception if source contains no elements. To instead return a default value when the source sequence is empty, use the {@link singleOrDefault} method.
     * <p><b>Syntax</b>: single (predicate {(v, k) ==> result})
     * <p>Returns the only element of a sequence that satisfies a specified condition.
     * <p>The single method throws an exception if no matching element is found in source. To instead return a default value when no matching element is found, use the {@link singleOrDefault} method.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \UnexpectedValueException If source contains no matching elements or more than one matching element.
     * @return mixed If predicate is null: the single element of the input sequence. If predicate is not null: The single element of the sequence that passes the test in the specified predicate function.
     * @package YaLinqo\Pagination
     */
    public function single ($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(self::ERROR_MANY_MATCHES);
                $found = true;
                $value = $v;
            }
        }
        if (!$found)
            throw new \UnexpectedValueException(self::ERROR_NO_MATCHES);
        return $value;
    }

    /**
     * Returns the only element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrDefault ([default])
     * <p>Returns the only element of a sequence, or a default value if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrDefault ([default [, predicate {(v, k) ==> result}]])
     * <p>Returns the only element of the sequence that satisfies a condition or a default value if no such element is found.
     * <p>If obtaining the default value is a costly operation, use {@link singleOrFallback} method to avoid overhead.
     * @param mixed $default A default value.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \UnexpectedValueException If source contains more than one matching element.
     * @return mixed If predicate is null: default value if source is empty; otherwise, the single element of the source. If predicate is not null: default value if source is empty or if no element passes the test specified by predicate; otherwise, the single element of the source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function singleOrDefault ($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(self::ERROR_MANY_MATCHES);
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : $default;
    }

    /**
     * Returns the only element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrFallback ([fallback {() ==> value}])
     * <p>Returns the only element of a sequence, or the result of calling a fallback function if the sequence contains no elements.
     * <p><b>Syntax</b>: singleOrFallback ([fallback {() ==> value} [, predicate {(v, k) ==> result}]])
     * <p>Returns the only element of the sequence that satisfies a condition or the result of calling a fallback function if no such element is found.
     * <p>The fallback function is not executed if a matching element is found. Use the singleOrFallback method if obtaining the default value is a costly operation to avoid overhead. Otherwise, use {@link singleOrDefault}.
     * @param callable $fallback {() ==> value} A fallback function to return the default element.
     * @param callable|null $predicate {(v, k) ==> result} A function to test each element for a condition. Default: true.
     * @throws \UnexpectedValueException If source contains more than one matching element.
     * @return mixed If predicate is null: the result of calling a fallback function if source is empty; otherwise, the single element of the source. If predicate is not null: the result of calling a fallback function if source is empty or if no element passes the test specified by predicate; otherwise, the single element of the source that passes the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function singleOrFallback ($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(self::ERROR_MANY_MATCHES);
                $found = true;
                $value = $v;
            }
        }
        return $found ? $value : $fallback();
    }

    /**
     * Searches for the specified value and returns the key of the first occurrence.
     * <p><b>Syntax</b>: indexOf (value)
     * <p>To search for the zero-based index of the first occurence, call {@link toValues} method first.
     * @param mixed $value The value to locate in the sequence.
     * @return mixed The key of the first occurrence of value, if found; otherwise, null.
     * @package YaLinqo\Pagination
     */
    public function indexOf ($value)
    {
        foreach ($this as $k => $v) {
            if ($v === $value)
                return $k;
        }
        return null; // not -1
    }

    /**
     * Searches for the specified value and returns the key of the last occurrence.
     * <p><b>Syntax</b>: lastIndexOf (value)
     * <p>To search for the zero-based index of the last occurence, call {@link toValues} method first.
     * @param mixed $value The value to locate in the sequence.
     * @return mixed The key of the last occurrence of value, if found; otherwise, null.
     * @package YaLinqo\Pagination
     */
    public function lastIndexOf ($value)
    {
        $key = null;
        foreach ($this as $k => $v) {
            if ($v === $value)
                $key = $k;
        }
        return $key; // not -1
    }

    /**
     * Searches for an element that matches the conditions defined by the specified predicate, and returns the key of the first occurrence.
     * <p><b>Syntax</b>: findIndex (predicate {(v, k) ==> result})
     * <p>To search for the zero-based index of the first occurence, call {@link toValues} method first.
     * @param callable $predicate {(v, k) ==> result} A function that defines the conditions of the element to search for.
     * @return mixed The key of the first occurrence of an element that matches the conditions defined by predicate, if found; otherwise, null.
     * @package YaLinqo\Pagination
     */
    public function findIndex ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                return $k;
        }
        return null; // not -1
    }

    /**
     * Searches for an element that matches the conditions defined by the specified predicate, and returns the key of the last occurrence.
     * <p><b>Syntax</b>: findLastIndex (predicate {(v, k) ==> result})
     * <p>To search for the zero-based index of the last occurence, call {@link toValues} method first.
     * @param callable $predicate {(v, k) ==> result} A function that defines the conditions of the element to search for.
     * @return mixed The key of the last occurrence of an element that matches the conditions defined by predicate, if found; otherwise, null.
     * @package YaLinqo\Pagination
     */
    public function findLastIndex ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        $key = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                $key = $k;
        }
        return $key; // not -1
    }

    /**
     * Bypasses a specified number of elements in a sequence and then returns the remaining elements.
     * <p><b>Syntax</b>: skip (count)
     * <p>If source contains fewer than count elements, an empty sequence is returned. If count is less than or equal to zero, all elements of source are yielded.
     * <p>The {@link take} and skip methods are functional complements. Given a sequence coll and an integer n, concatenating the results of coll->take(n) and coll->skip(n) yields the same sequence as coll.
     * @param int $count The number of elements to skip before returning the remaining elements.
     * @return Enumerable A sequence that contains the elements that occur after the specified index in the input sequence.
     * @package YaLinqo\Pagination
     */
    public function skip ($count)
    {
        return new self(function () use ($count) {
            $it = $this->getIterator();
            $it->rewind();
            for ($i = 0; $i < $count && $it->valid(); ++$i)
                $it->next();
            while ($it->valid()) {
                yield $it->key() => $it->current();
                $it->next();
            }
        });
    }

    /**
     * Bypasses elements in a sequence as long as a specified condition is true and then returns the remaining elements.
     * <p><b>Syntax</b>: skipWhile (predicate {(v, k) ==> result})
     * <p>This method tests each element of source by using predicate and skips the element if the result is true. After the predicate function returns false for an element, that element and the remaining elements in source are yielded and there are no more invocations of predicate. If predicate returns true for all elements in the sequence, an empty sequence is returned.
     * <p>The {@link takeWhile} and skipWhile methods are functional complements. Given a sequence coll and a pure function p, concatenating the results of coll->takeWhile(p) and coll->skipWhile(p) yields the same sequence as coll.
     * @param callable $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return Enumerable A sequence that contains the elements from the input sequence starting at the first element in the linear series that does not pass the test specified by predicate.
     * @package YaLinqo\Pagination
     */
    public function skipWhile ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new self(function () use ($predicate) {
            $yielding = false;
            foreach ($this as $k => $v) {
                if (!$yielding && !$predicate($v, $k))
                    $yielding = true;
                if ($yielding)
                    yield $k => $v;
            }
        });
    }

    /**
     * Returns a specified number of contiguous elements from the start of a sequence.
     * <p><b>Syntax</b>: take (count)
     * <p>Take enumerates source and yields elements until count elements have been yielded or source contains no more elements. If count is less than or equal to zero, source is not enumerated and an empty sequence is returned.
     * <p>The take and {@link skip} methods are functional complements. Given a sequence coll and an integer n, concatenating the results of coll->take(n) and coll->skip(n) yields the same sequence as coll.
     * @param int $count The number of elements to return.
     * @return Enumerable A sequence that contains the specified number of elements from the start of the input sequence.
     * @package YaLinqo\Pagination
     */
    public function take ($count)
    {
        if ($count <= 0)
            return self::emptyEnum();

        return new self(function () use ($count) {
            if ($count <= 0)
                return;
            foreach ($this as $k => $v) {
                yield $k => $v;
                if (--$count == 0)
                    break;
            }
        });
    }

    /**
     * Returns elements from a sequence as long as a specified condition is true.
     * <p><b>Syntax</b>: takeWhile (predicate {(v, k) ==> result})
     * <p>The takeWhile method tests each element of source by using predicate and yields the element if the result is true. Enumeration stops when the predicate function returns false for an element or when source contains no more elements.
     * <p>The takeWhile and {@link skipWhile} methods are functional complements. Given a sequence coll and a pure function p, concatenating the results of coll->takeWhile(p) and coll->skipWhile(p) yields the same sequence as coll.
     * @param callable $predicate {(v, k) ==> result} A function to test each element for a condition.
     * @return Enumerable A sequence that contains the elements from the input sequence that occur before the element at which the test no longer passes.
     * @package YaLinqo\Pagination
     */
    public function takeWhile ($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new self(function () use ($predicate) {
            foreach ($this as $k => $v) {
                if (!$predicate($v, $k))
                    break;
                yield $k => $v;
            }
        });
    }

    #endregion

    #region Conversion

    /**
     * Creates an array from a sequence.
     * <p><b>Syntax</b>: toArray ()
     * <p>The toArray method forces immediate query evaluation and returns an array that contains the query results.
     * <p>The toArray method does not traverse into elements of the sequence, only the sequence itself is converted. That is, if elements of the sequence are {@link Traversable} or arrays containing Traversable values, they will remain as is. To traverse deeply, you can use {@link toArrayDeep} method.
     * <p>Keys from the sequence are preserved. If the source sequence contains multiple values with the same key, the result array will only contain the latter value. To discard keys, you can use {@link toList} method. To preserve all values and keys, you can use {@link toLookup} method.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toArray ()
    {
        /** @var $it \Iterator|\ArrayIterator */
        $it = $this->getIterator();
        if ($it instanceof \ArrayIterator)
            return $it->getArrayCopy();

        $array = [ ];
        foreach ($it as $k => $v)
            $array[$k] = $v;
        return $array;
    }

    /**
     * Creates an array from a sequence, traversing deeply.
     * <p><b>Syntax</b>: toArrayDeep ()
     * <p>The toArrayDeep method forces immediate query evaluation and returns an array that contains the query results.
     * <p>The toArrayDeep method traverses into elements of the sequence. That is, if elements of the sequence are {@link Traversable} or arrays containing Traversable values, they will be converted to arrays too. To convert only the sequence itself, you can use {@link toArray} method.
     * <p>Keys from the sequence are preserved. If the source sequence contains multiple values with the same key, the result array will only contain the latter value. To discard keys, you can use {@link toListDeep} method. To preserve all values and keys, you can use {@link toLookup} method.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toArrayDeep ()
    {
        return $this->toArrayDeepProc($this);
    }

    /**
     * Proc for {@link toArrayDeep}.
     * @param $enum \Traversable Source sequence.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    protected function toArrayDeepProc ($enum)
    {
        $array = [ ];
        foreach ($enum as $k => $v)
            $array[$k] = $v instanceof \Traversable || is_array($v) ? $this->toArrayDeepProc($v) : $v;
        return $array;
    }

    /**
     * Creates an array from a sequence, with sequental integer keys.
     * <p><b>Syntax</b>: toList ()
     * <p>The toList method forces immediate query evaluation and returns an array that contains the query results.
     * <p>The toList method does not traverse into elements of the sequence, only the sequence itself is converted. That is, if elements of the sequence are {@link Traversable} or arrays containing Traversable values, they will remain as is. To traverse deeply, you can use {@link toListDeep} method.
     * <p>Keys from the sequence are discarded. To preserve keys and lose values with the same keys, you can use {@link toArray} method. To preserve all values and keys, you can use {@link toLookup} method.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toList ()
    {
        /** @var $it \Iterator|\ArrayIterator */
        $it = $this->getIterator();
        if ($it instanceof \ArrayIterator)
            return array_values($it->getArrayCopy());

        $array = [ ];
        foreach ($it as $v)
            $array[] = $v;
        return $array;
    }

    /**
     * Creates an array from a sequence, with sequental integer keys.
     * <p><b>Syntax</b>: toListDeep ()
     * <p>The toListDeep method forces immediate query evaluation and returns an array that contains the query results.
     * <p>The toListDeep method traverses into elements of the sequence. That is, if elements of the sequence are {@link Traversable} or arrays containing Traversable values, they will be converted to arrays too. To convert only the sequence itself, you can use {@link toList} method.
     * <p>Keys from the sequence are discarded. To preserve keys and lose values with the same keys, you can use {@link toArrayDeep} method. To preserve all values and keys, you can use {@link toLookup} method.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toListDeep ()
    {
        return $this->toListDeepProc($this);
    }

    /**
     * Proc for {@link toListDeep}.
     * @param $enum \Traversable Source sequence.
     * @return array An array that contains the elements from the input sequence.
     * @package YaLinqo\Conversion
     */
    protected function toListDeepProc ($enum)
    {
        $array = [ ];
        foreach ($enum as $v)
            $array[] = $v instanceof \Traversable || is_array($v) ? $this->toListDeepProc($v) : $v;
        return $array;
    }

    /**
     * Creates an array from a sequence according to specified key selector and value selector functions.
     * <p><b>Syntax</b>: toDictionary ([keySelector {(v, k) ==> key} [, valueSelector {(v, k) ==> value}]])
     * <p>The toDictionary method returns an array, a one-to-one dictionary that maps keys to values. If the source sequence contains multiple values with the same key, the result array will only contain the latter value.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from each element. Default: key.
     * @param callable|null $valueSelector {(v, k) ==> value} A transform function to produce a result value from each element. Default: value.
     * @return array An array that contains keys and values selected from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toDictionary ($keySelector = null, $valueSelector = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$value);

        $dic = [ ];
        foreach ($this as $k => $v)
            $dic[$keySelector($v, $k)] = $valueSelector($v, $k);
        return $dic;
    }

    /**
     * Returns a string containing the JSON representation of sequence (converted to array).
     * <p><b>Syntax</b>: toJSON ([options])
     * <p>This function only works with UTF-8 encoded data.
     * @param int $options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_UNESCAPED_UNICODE. Default: 0.
     * @return string A JSON encoded string on success or false on failure.
     * @see json_encode
     * @package YaLinqo\Conversion
     */
    public function toJSON ($options = 0)
    {
        return json_encode($this->toArrayDeep(), $options);
    }

    /**
     * Creates an array from a sequence according to specified key selector and value selector functions.
     * <p><b>Syntax</b>: toLookup ([keySelector {(v, k) ==> key} [, valueSelector {(v, k) ==> value}]])
     * <p>The toLookup method returns an array, a one-to-many dictionary that maps keys to arrays of values.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from each element. Default: key.
     * @param callable|null $valueSelector {(v, k) ==> value} A transform function to produce a result value from each element. Default: value.
     * @return array An array that contains keys and value arrays selected from the input sequence.
     * @package YaLinqo\Conversion
     */
    public function toLookup ($keySelector = null, $valueSelector = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$value);

        $lookup = [ ];
        foreach ($this as $k => $v)
            $lookup[$keySelector($v, $k)][] = $valueSelector($v, $k);
        return $lookup;
    }

    /**
     * Returns a sequence of keys from the source sequence.
     * <p><b>Syntax</b>: toKeys ()
     * @return Enumerable A sequence with keys from the source sequence as values and sequental integers as keys.
     * @see array_keys
     * @package YaLinqo\Conversion
     */
    public function toKeys ()
    {
        return $this->select(Functions::$key, Functions::increment());
    }

    /**
     * Returns a sequence of values from the source sequence; keys are discarded.
     * <p><b>Syntax</b>: toValues ()
     * @return Enumerable A sequence with the same values and sequental integers as keys.
     * @see array_values
     * @package YaLinqo\Conversion
     */
    public function toValues ()
    {
        return $this->select(Functions::$value, Functions::increment());
    }

    /**
     * Transform the sequence to an object.
     * <p><b>Syntax</b>: toObject ([propertySelector {(v, k) ==> name} [, valueSelector {(v, k) ==> value}]])
     * @param callable|null $propertySelector {(v, k) ==> name} A function to extract a property name from an element. Must return a valid PHP identifier. Default: key.
     * @param callable|null $valueSelector {(v, k) ==> value} A function to extract a property value from an element. Default: value.
     * @return \stdClass
     * @package YaLinqo\Conversion
     */
    public function toObject ($propertySelector = null, $valueSelector = null)
    {
        $propertySelector = Utils::createLambda($propertySelector, 'v,k', Functions::$key);
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', Functions::$value);

        $obj = new \stdClass();
        foreach ($this as $k => $v)
            $obj->{$propertySelector($v, $k)} = $valueSelector($v, $k);
        return $obj;
    }

    /**
     * Returns a string containing a string representation of all the sequence values, with the separator string between each element.
     * <p><b>Syntax</b>: toString ([separator [, selector]])
     * @param string $separator A string separating values in the result string. Default: ''.
     * @param callable|null $valueSelector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @return string
     * @see implode
     * @package YaLinqo\Conversion
     */
    public function toString ($separator = '', $valueSelector = null)
    {
        $valueSelector = Utils::createLambda($valueSelector, 'v,k', false);
        $array = $valueSelector ? $this->select($valueSelector)->toArray() : $this->toArray();
        return implode($separator, $array);
    }

    #endregion

    #region Actions

    /**
     * Invokes an action for each element in the sequence.
     * <p><b>Syntax</b>: process (action {(v, k) ==> void})
     * <p>Process method does not start enumeration itself. To force enumeration, you can use {@link each} method.
     * <p>Original LINQ method name: do.
     * @param callable $action {(v, k) ==> void} The action to invoke for each element in the sequence.
     * @return Enumerable The source sequence with the side-effecting behavior applied.
     * @package YaLinqo\Actions
     */
    public function call ($action)
    {
        $action = Utils::createLambda($action, 'v,k');

        return new self(function () use ($action) {
            foreach ($this as $k => $v) {
                $action($v, $k);
                yield $k => $v;
            }
        });
    }

    /**
     * Invokes an action for each element in the sequence.
     * <p><b>Syntax</b>: each (action {(v, k) ==> void})
     * <p>Each method forces enumeration. To just add side-effect without enumerating, you can use {@link process} method.
     * <p>Original LINQ method name: foreach.
     * @param callable $action {(v, k) ==> void} The action to invoke for each element in the sequence.
     * @package YaLinqo\Actions
     */
    public function each ($action = null)
    {
        $action = Utils::createLambda($action, 'v,k', Functions::$blank);

        foreach ($this as $k => $v)
            $action($v, $k);
    }

    /**
     * Output the result of calling {@link toString} method.
     * <p><b>Syntax</b>: write ([separator [, selector]])
     * @param string $separator A string separating values in the result string. Default: ''.
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @see implode, echo
     * @package YaLinqo\Actions
     */
    public function write ($separator = '', $selector = null)
    {
        echo $this->toString($separator, $selector);
    }

    /**
     * Output all the sequence values, with a new line after each element.
     * <p><b>Syntax</b>: writeLine ([selector])
     * @param callable|null $selector {(v, k) ==> value} A transform function to apply to each element. Default: value.
     * @return string
     * @see echo, PHP_EOL
     * @package YaLinqo\Actions
     */
    public function writeLine ($selector = null)
    {
        $selector = Utils::createLambda($selector, 'v,k', Functions::$value);

        foreach ($this as $k => $v) {
            echo $selector($v, $k), PHP_EOL;
        }
    }

    #endregion
}
