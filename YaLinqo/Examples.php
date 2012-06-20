<?php

require_once __DIR__ . '/Linq.php';
use \YaLinqo\Enumerable, \YaLinqo\Functions;

$enum = from(array('a', 'bbb', 'c', 1, 'a' => 2, '10' => 3))
        ->where(function($v, $k) { return is_numeric($k); })
        ->select(function($v, $k) { return "$v($k)"; });

function compare_strlen ($a, $b)
{
    return strlen($a) - strlen($b);
}

foreach ($enum as $k => $v)
    echo "($k): ($v)\n";

var_dump($enum->aggregate(function($a, $b) { return $a . '|' . $b; }, 'ooo'));
var_dump($enum->aggregate(function($a, $b) { return $a . '|' . $b; }));

var_dump($enum->average(function($v, $k) { return $v + $k; }));
var_dump($enum->average(function($v, $k) { return $k; }));
var_dump($enum->average());
var_dump(from(new \EmptyIterator)->average());

var_dump($enum->count(function($v) { return intval($v) != 0; }));
var_dump(from(array(1, 2, 3))->count(function($v) { return $v > 1; }));
var_dump($enum->count());
var_dump(from(new \EmptyIterator)->count());

var_dump($enum->max(function($v, $k) { return intval($k); }));
var_dump(from(array(1, 2, 3))->max(function($v) { return $v * $v; }));
var_dump(from(array(1, 2, 3))->max());
var_dump($enum->max());
//var_dump(from(new \EmptyIterator)->max());

var_dump($enum->min(function($v, $k) { return intval($k); }));
var_dump(from(array(1, 2, 3))->min(function($v) { return $v * $v; }));
var_dump(from(array(1, 2, 3))->min());
var_dump($enum->min());
//var_dump(from(new \EmptyIterator)->min());

var_dump($enum->maxBy(__NAMESPACE__ . '\compare_strlen', function($v, $k) { return $v . ' ' . $k; }));

var_dump($enum->toArray());
var_dump($enum->toValues()->toArray());
var_dump($enum->toValues()->elementAt(2));
var_dump($enum->toValues()->elementAtOrDefault(-1, 666));

//var_dump(from(array(1, 2, 3))->take(2)->toArray());
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
    function($v, $k) { return $v + $k; }, 1,
    function($v) { return $v; }, 1
)->take(10)->toArray());

var_dump(Enumerable::generate(
    function($v) { return array($v[1], $v[0] + $v[1]); }, array(1, 1),
    function($v) { return $v[1]; }, 1
)->toKeys()->take(10)->toArray());

var_dump(Enumerable::generate(function ($v, $k) { return pow(-1, $k) / (2 * $k + 1); }, 0)->take(1000)->sum() * 4);

var_dump(Enumerable::toInfinity()->take(999)->sum(function ($k) { return pow(-1, $k) / (2 * $k + 1); }) * 4);

echo "Lambdas\n";
var_dump(from(array(1, 2, 3, 4, 5, 6))->where('$v ==> $v > 3')->select('$v ==> $v*$v')->toArray());
var_dump(from(array(1, 2, 3, 4, 5, 6))->where('($v) ==> $v > 3')->select('$v, $k ==> $v+$k')->toArray());
var_dump(from(array(1, 2, 3, 4, 5, 6))->where('($v) ==> { echo $v; return $v > 3; }')->select('($v, $k) ==> { return $v*2+$k*3; }')->toArray());
var_dump(from(array(1, 2, 3, 4, 5, 6))->where('$v > 2')->where('$v>3')->select('$v+$k')->toArray());

var_dump(Enumerable::split('1 2 3 4 5 6', '# #')->toArray());
var_dump(Enumerable::matches('1 2 3 4 5 6', '#\d+#')->select('$v[0]')->maxBy(Functions::$compareStrict));

var_dump(from(array(1, 2))->selectMany('$v ==> array(1, 2)', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(from(array(1, 2))->selectMany('$v ==> array(1, 2)', '"$k1=$v1 $k2=$v2"')->toArray());
var_dump(from(array(1, 2))->selectMany('$v ==> array(1, 2)', 'array($v1, $v2)')->toArray());
var_dump(from(array(1, 2))->selectMany('$v ==> array()', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(from(array())->selectMany('$v ==> array(1, 2)', '"$v1 $v2"', '"$k1 $k2"')->toArray());
var_dump(from(array('a' => array(1, 2), 'b' => array(3)))->selectMany('$v')->toArray());

var_dump(from(array(
    array(1, 3, 4),
    array(2, 1, 4),
    array(2, 1, 1),
    array(2, 3, 1),
    array(1, 3, 1),
    array(1, 1, 1),
))->orderBy('$v[0]')->thenBy('$v[1]')->thenByDescending('$v[2]')->select('implode(" ", $v)')->toList());

var_dump(from(array(1, 1, 1, 2, 2, 3))->select('$v', '$v')->toLookup());
var_dump(from(array('a' => 1, 'b' => 2))->toObject());

var_dump(from(array('a', 'b', 'c', 10 => 'z'))->join(array('d', 'e', 10 => 'y', 11 => 'x'))->toArray());
var_dump(from(
    array(
        array('id' => 10, 'name' => 'cat1'),
        array('id' => 11, 'name' => 'cat2'),
        array('id' => 12, 'name' => 'cat3'),
    )
)->join(
    array(
        array('name' => 'prod1', 'catId' => 10),
        array('name' => 'prod2', 'catId' => 10),
        array('name' => 'prod3', 'catId' => 11),
        array('name' => 'prod4', 'catId' => 13),
    ),
    '$v["id"]', '$v["catId"]',
    function ($cat, $prod) { return "prod {$prod['name']} from {$cat['name']}"; })->toLookup()->toArray());

var_dump(from(array('a', 'b', 'c', 10 => 'z'))->groupJoin(array('d', 'e', 10 => 'y', 11 => 'x'), null, null, 'array($v, $e->toArray())')->toArray());
var_dump(from(
    array(
        array('id' => 10, 'name' => 'cat1'),
        array('id' => 11, 'name' => 'cat2'),
        array('id' => 12, 'name' => 'cat3'),
    )
)->groupJoin(
    array(
        array('name' => 'prod1', 'catId' => 10),
        array('name' => 'prod2', 'catId' => 10),
        array('name' => 'prod3', 'catId' => 11),
        array('name' => 'prod4', 'catId' => 13),
    ),
    '$v["id"]', '$v["catId"]',
    function ($cat, $prods)
    {
        /** @var $prods \YaLinqo\Enumerable */
        return "Prods from {$cat['name']}: " .
                implode(', ', $prods->select('$v["name"]')->toArray()) .
                '.';
    })->toArray());

var_dump(from(
    array(
        array('name' => 'prod1', 'cat' => 'cat1'),
        array('name' => 'prod2', 'cat' => 'cat1'),
        array('name' => 'prod3', 'cat' => 'cat2'),
        array('name' => 'prod4', 'cat' => 'cat3'),
    )
)->groupBy('$v["cat"]', '$v["name"]')->toArray());
