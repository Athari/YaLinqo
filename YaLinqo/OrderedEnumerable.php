<?php

/**
 * OrderedEnumerable class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

/**
 * Subclass of Enumerable supporting ordering by multiple conditions.
 * @package YaLinqo
 */
class OrderedEnumerable extends Enumerable
{
    /** Source sequence. @var Enumerable */
    private $source;
    /** Parent ordered sequence. @var \YaLinqo\OrderedEnumerable */
    private $parent;
    /** Sort order for array_multisort: SORT_DESC or SORT_ASC. @var int|bool */
    private $sortOrder;
    /** Sort flags for array_multisort. @var int */
    private $sortFlags;
    /** Whether comparer result needs to be negated (used in usort). @var bool */
    private $isReversed;
    /** Key selector. @var callable {(v, k) ==> key} */
    private $keySelector;
    /** Comprarer function. @var callable {(a, b) ==> diff} */
    private $comparer;

    /**
     * @internal
     * @param Enumerable $source
     * @param int|bool $sortOrder A direction in which to order the elements: false or SORT_DESC for ascending (by increasing value), true or SORT_ASC for descending (by decreasing value).
     * @param int $sortFlags Sort flags for array_multisort.
     * @param bool $isReversed Whether comparer result needs to be negated (used in usort).
     * @param callable $keySelector {(v, k) ==> key} A function to extract a key from an element.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param \YaLinqo\OrderedEnumerable $parent
     */
    public function __construct($source, $sortOrder, $sortFlags, $isReversed, $keySelector, $comparer, $parent = null)
    {
        $this->source = $source;
        $this->sortOrder = $sortOrder;
        $this->sortFlags = $sortFlags;
        $this->isReversed = $isReversed;
        $this->keySelector = $keySelector;
        $this->comparer = $comparer;
        $this->parent = $parent;
    }

    private function getSingleComparer()
    {
        $comparer = $this->comparer;
        if ($this->isReversed)
            $comparer = function($a, $b) use ($comparer) { return -$comparer($a, $b); };
        return $comparer;
    }

    /**
     * <p><b>Syntax</b>: thenByDir (false|true [, {{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of elements in a sequence in a particular direction (ascending, descending) according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param int|bool $sortOrder A direction in which to order the elements: false or SORT_DESC for ascending (by increasing value), true or SORT_ASC for descending (by decreasing value).
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|int|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b. Can also be a combination of SORT_ flags.
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDir($sortOrder, $keySelector = null, $comparer = null): OrderedEnumerable
    {
        $sortFlags = Utils::lambdaToSortFlagsAndOrder($comparer, $sortOrder);
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);
        $isReversed = $sortOrder == SORT_DESC;
        $comparer = Utils::createComparer($comparer, $sortOrder, $isReversed);
        return new self($this->source, $sortOrder, $sortFlags, $isReversed, $keySelector, $comparer, $this);
    }

    /**
     * <p><b>Syntax</b>: thenBy ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in ascending order according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|int|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b. Can also be a combination of SORT_ flags.
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenBy($keySelector = null, $comparer = null): OrderedEnumerable
    {
        return $this->thenByDir(false, $keySelector, $comparer);
    }

    /**
     * <p><b>Syntax</b>: thenByDescending ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in descending order according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable|null $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable|int|null $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b. Can also be a combination of SORT_ flags.
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDescending($keySelector = null, $comparer = null): OrderedEnumerable
    {
        return $this->thenByDir(true, $keySelector, $comparer);
    }

    /** {@inheritdoc} */
    public function getIterator(): \Traversable
    {
        $canMultisort = $this->sortFlags !== null;
        $array = $this->source->tryGetArrayCopy();

        $it = $this->trySortBySingleField($array, $canMultisort);
        if ($it !== null)
            return $it;

        return $this->sortByMultipleFields($array, $canMultisort);
    }

    private function trySortBySingleField($array, bool $canMultisort)
    {
        if ($this->parent !== null || $array === null) {
            return null;
        }
        elseif ($this->keySelector === Functions::$value) {
            if (!$canMultisort)
                uasort($array, $this->getSingleComparer());
            elseif ($this->sortOrder == SORT_ASC)
                asort($array, $this->sortFlags);
            else
                arsort($array, $this->sortFlags);
        }
        elseif ($this->keySelector === Functions::$key) {
            if ($canMultisort)
                uksort($array, $this->getSingleComparer());
            elseif ($this->sortOrder == SORT_ASC)
                ksort($array, $this->sortFlags);
            else
                krsort($array, $this->sortFlags);
        }
        else {
            return null;
        }
        return new \ArrayIterator($array);
    }

    private function sortByMultipleFields($array, bool $canMultisort)
    {
        $orders = [];
        for ($order = $this; $order !== null; $order = $order->parent) {
            $orders[] = $order;
            if ($order->sortFlags === null)
                $canMultisort = false;
        }
        $orders = array_reverse($orders);

        $it = $this->trySortArrayWithMultisort($array, $orders, $canMultisort);
        if ($it !== null)
            return $it;

        return $this->sortIterator($orders, $canMultisort);
    }

    private function sortIterator(array $orders, bool $canMultisort)
    {
        $enum = [];
        if ($canMultisort)
            $this->sortIteratorWithMultisort($enum, $orders);
        else
            $this->sortIteratorWithUsort($enum, $orders);

        foreach ($enum as $pair)
            yield $pair[0] => $pair[1];
    }

    private function trySortArrayWithMultisort($array, array $orders, bool $canMultisort)
    {
        /** @var $order OrderedEnumerable */
        if ($array === null || !$canMultisort)
            return null;

        $args = [];
        foreach ($orders as $order) {
            $column = [];
            foreach ($array as $k => $v) {
                $keySelector = $order->keySelector;
                $column[$k] = $keySelector($v, $k);
            }
            $args[] = $column;
            $args[] = $order->sortOrder;
            $args[] = $order->sortFlags;
        }
        $args[] = &$array;

        call_user_func_array('array_multisort', $args);

        return new \ArrayIterator($array);
    }

    private function sortIteratorWithMultisort(&$enum, array $orders)
    {
        /** @var $order OrderedEnumerable */
        foreach ($this->source as $k => $v)
            $enum[] = [ $k, $v ];

        $args = [];
        foreach ($orders as $order) {
            $column = [];
            foreach ($enum as $k => $pair) {
                $keySelector = $order->keySelector;
                $column[$k] = $keySelector($pair[1], $pair[0]);
            }
            $args[] = $column;
            $args[] = $order->sortOrder;
            $args[] = $order->sortFlags;
        }
        $args[] = &$enum;

        call_user_func_array('array_multisort', $args);
    }

    private function sortIteratorWithUsort(&$enum, array $orders)
    {
        /** @var $order OrderedEnumerable */
        foreach ($this->source as $k => $v) {
            $element = [ $k, $v ];
            foreach ($orders as $order) {
                $keySelector = $order->keySelector;
                $element[] = $keySelector($v, $k);
            }
            $enum[] = $element;
        }

        usort($enum, function($a, $b) use ($orders) {
            /** @var $order OrderedEnumerable */
            $count = count($orders);
            for ($i = 0; $i < $count; $i++) {
                $order = $orders[$i];
                $comparer = $order->comparer;
                $diff = $comparer($a[$i + 2], $b[$i + 2]);
                if ($diff != 0)
                    return $order->isReversed ? -$diff : $diff;
            }
            return 0;
        });
    }
}
