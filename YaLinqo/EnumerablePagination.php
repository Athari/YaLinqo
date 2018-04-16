<?php

/**
 * EnumerablePagination trait of Enumerable class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

/**
 * Trait of {@link Enumerable} containing pagination methods.
 * @package YaLinqo
 */
trait EnumerablePagination
{
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
    public function elementAt($key)
    {
        /** @var $it \Iterator|\ArrayAccess */
        $it = $this->getIterator();

        if ($it instanceof \ArrayAccess) {
            if (!$it->offsetExists($key))
                throw new \UnexpectedValueException(Errors::NO_KEY);
            return $it->offsetGet($key);
        }

        foreach ($it as $k => $v) {
            if ($k === $key)
                return $v;
        }
        throw new \UnexpectedValueException(Errors::NO_KEY);
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
    public function elementAtOrDefault($key, $default = null)
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
    public function first($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        foreach ($this as $k => $v) {
            if ($predicate($v, $k))
                return $v;
        }
        throw new \UnexpectedValueException(Errors::NO_MATCHES);
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
    public function firstOrDefault($default = null, $predicate = null)
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
    public function firstOrFallback($fallback, $predicate = null)
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
    public function last($predicate = null)
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
            throw new \UnexpectedValueException(Errors::NO_MATCHES);
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
    public function lastOrDefault($default = null, $predicate = null)
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
    public function lastOrFallback($fallback, $predicate = null)
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
    public function single($predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(Errors::MANY_MATCHES);
                $found = true;
                $value = $v;
            }
        }
        if (!$found)
            throw new \UnexpectedValueException(Errors::NO_MATCHES);
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
    public function singleOrDefault($default = null, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(Errors::MANY_MATCHES);
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
    public function singleOrFallback($fallback, $predicate = null)
    {
        $predicate = Utils::createLambda($predicate, 'v,k', Functions::$true);

        $found = false;
        $value = null;
        foreach ($this as $k => $v) {
            if ($predicate($v, $k)) {
                if ($found)
                    throw new \UnexpectedValueException(Errors::MANY_MATCHES);
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
     * @return mixed The key of the first occurrence of value, if found; otherwise, false.
     * @package YaLinqo\Pagination
     */
    public function indexOf($value)
    {
        $array = $this->tryGetArrayCopy();
        if ($array !== null)
            return array_search($value, $array, true);
        else {
            foreach ($this as $k => $v) {
                if ($v === $value)
                    return $k;
            }
            return false;
        }
    }

    /**
     * Searches for the specified value and returns the key of the last occurrence.
     * <p><b>Syntax</b>: lastIndexOf (value)
     * <p>To search for the zero-based index of the last occurence, call {@link toValues} method first.
     * @param mixed $value The value to locate in the sequence.
     * @return mixed The key of the last occurrence of value, if found; otherwise, null.
     * @package YaLinqo\Pagination
     */
    public function lastIndexOf($value)
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
    public function findIndex($predicate)
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
    public function findLastIndex($predicate)
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
    public function skip(int $count)
    {
        return new self(function() use ($count) {
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
    public function skipWhile($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new self(function() use ($predicate) {
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
    public function take(int $count)
    {
        if ($count <= 0)
            return new self(new \EmptyIterator, false);

        return new self(function() use ($count) {
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
    public function takeWhile($predicate)
    {
        $predicate = Utils::createLambda($predicate, 'v,k');

        return new self(function() use ($predicate) {
            foreach ($this as $k => $v) {
                if (!$predicate($v, $k))
                    break;
                yield $k => $v;
            }
        });
    }

    /**
     * Retrieve an external iterator.
     * @return \Iterator
     */
    public abstract function getIterator();
}