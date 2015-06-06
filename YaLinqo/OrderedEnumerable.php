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
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
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

        $map = $this->source->select(function ($v, $k) { return [ 'v' => $v, 'k' => $k ]; })->toList();
        $comparers = [ ];

        for ($i = 0; $i < count($orders); ++$i) {
            $order = $orders[$i];
            $comparer = $order->comparer;
            if ($order->desc)
                $comparer = function ($a, $b) use ($comparer) { return -$comparer($a, $b); };
            $comparers[] = $comparer;
            for ($j = 0; $j < count($map); ++$j) {
                $keySelector = $order->keySelector;
                $map[$j][] = $keySelector($map[$j]['v'], $map[$j]['k']);
            }
        }

        usort($map, function ($a, $b) use ($comparers) {
            for ($i = 0; $i < count($comparers); ++$i) {
                $diff = $comparers[$i]($a[$i], $b[$i]);
                if ($diff != 0)
                    return $diff;
            }
            return 0;
        });

        return Enumerable::from($map)->select(
            function ($v) { return $v['v']; },
            function ($v) { return $v['k']; }
        )->getIterator();
    }
}
