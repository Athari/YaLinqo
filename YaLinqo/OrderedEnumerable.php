<?php

/**
 * OrderedEnumerable class.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

namespace YaLinqo;

use YaLinqo;

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
    /** Whether to sort in descending or ascending order. @var bool */
    private $desc;
    /** Key selector. @var callable {(v, k) ==> key} */
    private $keySelector;
    /** Comprarer function. @var callable {(a, b) ==> diff} */
    private $comparer;

    /**
     * @internal
     * @param Enumerable $source
     * @param callable $keySelector {(v, k) ==> key} A function to extract a key from an element.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @param \YaLinqo\OrderedEnumerable $parent
     */
    public function __construct ($source, $desc, $keySelector, $comparer, $parent = null)
    {
        $this->source = $source;
        $this->desc = $desc;
        $this->keySelector = $keySelector;
        $this->comparer = $comparer;
        $this->parent = $parent;
    }

    /**
     * <p><b>Syntax</b>: thenByDir (false|true [, {{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of elements in a sequence in a particular direction (ascending, descending) according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callable $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDir ($desc, $keySelector = null, $comparer = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$value);
        $comparer = Utils::createComparer($comparer, $desc);
        return new self($this->source, $desc, $keySelector, $comparer, $this);
    }

    /**
     * <p><b>Syntax</b>: thenBy ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in ascending order according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenBy ($keySelector = null, $comparer = null)
    {
        return $this->thenByDir(false, $keySelector, $comparer);
    }

    /**
     * <p><b>Syntax</b>: thenByDescending ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in descending order according to a key.
     * <p>Three methods are defined to extend the type OrderedEnumerable, which is the return type of this method. These three methods, namely {@link thenBy}, {@link thenByDescending} and {@link thenByDir}, enable you to specify additional sort criteria to sort a sequence. These methods also return an OrderedEnumerable, which means any number of consecutive calls to thenBy, thenByDescending or thenByDir can be made.
     * <p>Because OrderedEnumerable inherits from {@link Enumerable}, you can call {@link Enumerable::orderBy orderBy}, {@link Enumerable::orderByDescending orderByDescending} or {@link Enumerable::orderByDir orderByDir} on the results of a call to orderBy, orderByDescending, orderByDir, thenBy, thenByDescending or thenByDir. Doing this introduces a new primary ordering that ignores the previously established ordering.
     * <p>This method performs an unstable sort; that is, if the keys of two elements are equal, the order of the elements is not preserved. In contrast, a stable sort preserves the order of elements that have the same key. Internally, {@link usort} is used.
     * @param callable $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: value.
     * @param callable $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDescending ($keySelector = null, $comparer = null)
    {
        return $this->thenByDir(true, $keySelector, $comparer);
    }

    /** {@inheritdoc} */
    public function getIterator ()
    {
        $orders = [ ];
        for ($order = $this; $order != null; $order = $order->parent)
            $orders[] = $order;
        $orders = array_reverse($orders);

        $enum = [ ];
        foreach ($this->source as $k => $v)
            $enum[] = [ $k, $v ];

        usort($enum, function ($a, $b) use ($orders) {
            /** @var $order OrderedEnumerable */
            foreach ($orders as $order) {
                $comparer = $order->comparer;
                $keySelector = $order->keySelector;
                $diff = $comparer($keySelector($a[1], $a[0]), $keySelector($b[1], $b[0]));
                if ($diff != 0)
                    return $order->desc ? -$diff : $diff;
            }
            return 0;
        });

        foreach ($enum as $v)
            yield $v[0] => $v[1];
    }
}
