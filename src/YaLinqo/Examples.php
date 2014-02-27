<?php

// @codeCoverageIgnoreStart

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
//var_dump(from(new \EmptyIterator)->average());

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
        return $prods->any()
                ? "Prods from {$cat['name']}: " . $prods->toString(', ', '$v["name"]') . '.'
                : "Cat {$cat['name']} is empty.";
    })->toArray());

var_dump(from(
    array(
        array('name' => 'prod1', 'cat' => 'cat1'),
        array('name' => 'prod2', 'cat' => 'cat1'),
        array('name' => 'prod3', 'cat' => 'cat2'),
        array('name' => 'prod4', 'cat' => 'cat3'),
    )
)->groupBy('$v["cat"]', '$v["name"]')->toArray());

var_dump(from(array(1, 2, 3))->all('$v > 0'));
var_dump(from(array(1, 2, 3))->all('$v > 1'));
var_dump(from(array(1, 2, 3))->any('$v > 2'));
var_dump(from(array(1, 2, 3))->any('$v > 3'));
var_dump(from(array(1, 2, 3))->any());

var_dump(from(array(1, 2, 3))->contains('1'));
var_dump(from(array(1, 2, 3))->contains(1));
var_dump(from(array(1, 2, 3))->contains(4));

var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('string')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('int')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('numeric')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('scalar')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('object')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('array')->toArray());
var_dump(from(array(1, array(), 2, '3', null, '45', new stdClass, 'a'))->ofType('stdClass')->toArray());

//var_dump(from(array())->first());
var_dump(from(array())->firstOrDefault(1));
//var_dump(from(array())->last());
var_dump(from(array())->lastOrDefault());
var_dump(from(array(1, 2))->first());
var_dump(from(array(1, 2))->last());
var_dump(from(array(1, 2))->firstOrDefault(3, '$v > 1'));
var_dump(from(array(1, 2))->firstOrDefault(3, '$v > 2'));
var_dump(from(array(1, 2))->lastOrDefault(3, '$v < 2'));
var_dump(from(array())->firstOrFallback(function () { return 4; }));
//var_dump(from(array(1, 2))->single());
//var_dump(from(array(1, 2))->single('$v > 0'));
var_dump(from(array(1, 2))->single('$v > 1'));
var_dump(from(array(1, 2))->singleOrDefault(3, '$v > 2'));

var_dump(from(array(1, 2, 3))->toString());
var_dump(from(array(1, 2, 3))->toString(', '));
var_dump(from(array(1, 2, 3))->toString(', ', '$v*2'));

from(array(1, 2, 3))->write();
echo "\n";
from(array(1, 2, 3))->write(', ');
echo "\n";
from(array(1, 2, 3))->write(', ', '"$k = $v"');
echo "\n";
from(array(1, 2, 3))->writeLine('"$k = $v"');

var_dump(from(array(1, 2, 3))->select('array($v, $v)')->toJSON());

var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(0)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(4)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(8)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(9)->toString());
//var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(-1)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skipWhile('$v < 7')->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skipWhile('$v == 0')->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skipWhile('$v != 8')->toString());

var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->take(0)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->take(4)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->take(8)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->take(9)->toString());
//var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->skip(-1)->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->takeWhile('$v < 7')->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->takeWhile('$v == 0')->toString());
var_dump(from(array(1, 2, 3, 4, 5, 6, 7, 8))->takeWhile('$v != 9')->toString());

$products = array(
    array('name' => 'Keyboard',    'catId' => 'hw', 'quantity' =>  10, 'id' => 1),
    array('name' => 'Mouse',       'catId' => 'hw', 'quantity' =>  20, 'id' => 2),
    array('name' => 'Monitor',     'catId' => 'hw', 'quantity' =>   0, 'id' => 3),
    array('name' => 'Joystick',    'catId' => 'hw', 'quantity' =>  15, 'id' => 4),
    array('name' => 'CPU',         'catId' => 'hw', 'quantity' =>  15, 'id' => 5),
    array('name' => 'Motherboard', 'catId' => 'hw', 'quantity' =>  11, 'id' => 6),
    array('name' => 'Windows',     'catId' => 'os', 'quantity' => 666, 'id' => 7),
    array('name' => 'Linux',       'catId' => 'os', 'quantity' => 666, 'id' => 8),
    array('name' => 'Mac',         'catId' => 'os', 'quantity' => 666, 'id' => 9),
);
$categories = array(
    array('name' => 'Hardware',          'id' => 'hw'),
    array('name' => 'Operating systems', 'id' => 'os'),
);

// Full lambda syntax
$result = from($categories)
    ->orderBy('$cat ==> $cat["name"]')
    ->groupJoin(
        from($products)
            ->where('$prod ==> $prod["quantity"] > 0')
            ->orderByDescending('$prod ==> $prod["quantity"]')
            ->thenBy('$prod ==> $prod["name"]'),
        '$cat ==> $cat["id"]',
        '$prod ==> $prod["catId"]',
        '($cat, $prods) ==> array("name" => $cat["name"], "products" => $prods)'
    );

// Short lambda syntax
$result2 = from($categories)
    ->orderBy('$v["name"]')
    ->groupJoin(
        from($products)
            ->where('$v["quantity"] > 0')
            ->orderByDescending('$v["quantity"]')
            ->thenBy('$v["name"]'),
        '$v["id"]', '$v["catId"]', 'array("name" => $v["name"], "products" => $e)'
    );

// Closure syntax
$result3 = from($categories)
    ->orderBy(function ($cat) { return $cat['name']; })
    ->groupJoin(
        from($products)
            ->where(function ($prod) { return $prod["quantity"] > 0; })
            ->orderByDescending(function ($prod) { return $prod["quantity"]; })
            ->thenBy(function ($prod) { return $prod["name"]; }),
        function ($cat) { return $cat["id"]; },
        function ($prod) { return $prod["catId"]; },
        function ($cat, $prods) { return array("name" => $cat["name"], "products" => $prods); }
    );

print_r($result->toArrayDeep());
print_r($result2->toArrayDeep());
print_r($result3->toArrayDeep());

$result->writeLine(function ($cat) {
    return
        "<p><b>{$cat['name']}</b>:\n" .
        $cat['products']->toString(', ', function ($prod) {
            return "<a href='/products/{$prod["id"]}'>{$prod['name']}</a> ({$prod['quantity']})";
        }) .
        "</p>";
});
