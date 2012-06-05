<?php

namespace YaLinqo;
use YaLinqo;

class OrderedEnumerable extends Enumerable
{
    /** @var \YaLinqo\Enumerable */
    private $source;
    /** @var \YaLinqo\OrderedEnumerable */
    private $parent;
    /** @var bool */
    private $desc;
    /** @var callback {(v, k) ==> key} */
    private $keySelector;
    /** @var callback {(a, b) ==> diff} */
    private $comparer;

    /**
     * @param \YaLinqo\Enumerable $source
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
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
     * <p>thenByDir (false|true [, {{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of elements in a sequence in a particular direction (ascending, descending) according to a key.
     * @param bool $desc A direction in which to order the elements: false for ascending (by increasing value), true for descending (by decreasing value).
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDir ($desc, $keySelector = null, $comparer = null)
    {
        $keySelector = Utils::createLambda($keySelector, 'v,k', Functions::$identity);
        $comparer = Utils::createLambda($comparer, 'a,b', Functions::$compareStrict);
        return new OrderedEnumerable($this->source, $desc, $keySelector, $comparer, $this);
    }

    /**
     * <p>thenBy ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in ascending order according to a key.
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenBy ($keySelector = null, $comparer = null)
    {
        return $this->thenByDir(false, $keySelector, $comparer);
    }

    /**
     * <p>thenByDescending ([{{(v, k) ==> key} [, {{(a, b) ==> diff}]])
     * <p>Performs a subsequent ordering of the elements in a sequence in descending order according to a key.
     * @param callback $keySelector {(v, k) ==> key} A function to extract a key from an element. Default: identity function.
     * @param callback $comparer {(a, b) ==> diff} Difference between a and b: &lt;0 if a&lt;b; 0 if a==b; &gt;0 if a&gt;b
     * @return \YaLinqo\OrderedEnumerable
     */
    public function thenByDescending ($keySelector = null, $comparer = null)
    {
        return $this->thenByDir(true, $keySelector, $comparer);
    }

    /** {@inheritdoc} */
    public function getIterator ()
    {
        $orders = array();

        for ($order = $this; $order != null; $order = $order->parent)
            $orders[] = $order;
        $orders = array_reverse($orders);

        $map = $this->source->select(function ($v, $k) { return array('v' => $v, 'k' => $k); })->toList();
        $comparers = array();

        for ($i = 0; $i < count($orders); ++$i) {
            $order = $orders[$i];
            $comparer = $order->comparer;
            if ($order->desc)
                $comparer = function ($a, $b) use ($comparer) { return -call_user_func($comparer, $a, $b); };
            $comparers[] = $comparer;
            for ($j = 0; $j < count($map); ++$j)
                $map[$j][] = call_user_func($order->keySelector, $map[$j]['v'], $map[$j]['k']);
        }

        usort($map, function ($a, $b) use ($comparers)
        {
            for ($i = 0; $i < count($comparers); ++$i) {
                $diff = call_user_func($comparers[$i], $a[$i], $b[$i]);
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
