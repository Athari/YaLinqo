# *YaLinqo: Yet Another LINQ to Objects for PHP*

https://github.com/Athari/YaLinqo

Features
========

* The most complete port of .NET 4 LINQ to PHP, with many additional methods. Some methods are still missing, but it is a work in progress.
* Lazy evaluation, exceptions' messages and other behavior of original LINQ.
* Detailed PHPDoc for all methods. Articles are adapted from original LINQ documentation from MSDN.
* 100% unit test coverage.
* Callback functions can be specified as closures (like `function ($v) { return $v; }`), PHP "function pointers" (either strings like `'strnatcmp'` or arrays like `array($object, 'methodName')`), string "lambdas" using various syntaxes (`'"$k = $v"'`, `'$v ==> $v+1'`, `'($v, $k) ==> $v + $k'`, `'($v, $k) ==> { return $v + $k; }'`).
* Keys are as important as values. Most callback functions receive both values and the keys; transformations can be applied to both values and the keys; keys are never lost during transformation, if possible.
* SPL interfaces `Iterator`, `IteratorAggregate` etc. are used throughout the code and can be used interchangeably with Enumerable.
* Composer support with autoloading ([package](https://packagist.org/packages/athari/yalinqo) on Packagist).

Implemented methods
===================

Some methods had to be renamed, because their names are reserved keywords. Original methods names are given in parenthesis.

* **Generation**: cycle, emptyEnum (empty), from, generate, toInfinity, toNegativeInfinity, matches, returnEnum (return), range, rangeDown, rangeTo, repeat, split;
* **Projection and filtering**: ofType, select, selectMany, where;
* **Ordering**: orderBy, orderByDescending, orderByDir, thenBy, thenByDescending, thenByDir;
* **Joining and grouping**: groupJoin, join, groupBy;
* **Aggregation**: aggregate, aggregateOrDefault, average, count, max, maxBy, min, minBy, sum;
* **Set**: all, any, contains;
* **Pagination**: elementAt, elementAtOrDefault, first, firstOrDefault, firstOrFallback, last, lastOrDefault, lastOrFallback, single, singleOrDefault, singleOrFallback, indexOf, lastIndexOf, findIndex, findLastIndex, skip, skipWhile, take, takeWhile;
* **Conversion**: toArray, toArrayDeep, toList, toListDeep, toDictionary, toJSON, toLookup, toKeys, toValues, toObject, toString;
* **Actions**: call (do), each (forEach), write, writeLine.

In total, more than 70 methods.

Example
=======

*Process sample data:*
```php
// Data
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

// Put products with non-zero quantity into matching categories; sort products by quantity descending, then by name
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

// Alternative shorter syntax using default variable names
$result2 = from($categories)
    ->orderBy('$v["name"]')
    ->groupJoin(
        from($products)
            ->where('$v["quantity"] > 0')
            ->orderByDescending('$v["quantity"]')
            ->thenBy('$v["name"]'),
        '$v["id"]', '$v["catId"]', 'array("name" => $v["name"], "products" => $e)'
    );

// Closure syntax, maximum support in IDEs, but verbose and hard to read
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
```
*Output (compacted):*
```
Array (
    [hw] => Array (
        [name] => Hardware
        [products] => Array (
            [0] => Array ( [name] => Mouse       [catId] => hw [quantity] =>  20 [id] => 2 )
            [1] => Array ( [name] => CPU         [catId] => hw [quantity] =>  15 [id] => 5 )
            [2] => Array ( [name] => Joystick    [catId] => hw [quantity] =>  15 [id] => 4 )
            [3] => Array ( [name] => Motherboard [catId] => hw [quantity] =>  11 [id] => 6 )
            [4] => Array ( [name] => Keyboard    [catId] => hw [quantity] =>  10 [id] => 1 )
        )
    )
    [os] => Array (
        [name] => Operating systems
        [products] => Array (
            [0] => Array ( [name] => Linux       [catId] => os [quantity] => 666 [id] => 8 )
            [1] => Array ( [name] => Mac         [catId] => os [quantity] => 666 [id] => 9 )
            [2] => Array ( [name] => Windows     [catId] => os [quantity] => 666 [id] => 7 )
        )
    )
)
```
*Convert to HTML:*
```php
$result->writeLine(function ($cat) {
    return
        "<p><b>{$cat['name']}</b>:\n" .
        $cat['products']->toString(', ', function ($prod) {
            return "<a href='/products/{$prod["id"]}'>{$prod['name']}</a> ({$prod['quantity']})";
        }) .
        "</p>";
});
```
*Output (reformatted):*
```
<p><b>Hardware</b>:
<a href='/products/1'>Keyboard</a> (10), <a href='/products/6'>Motherboard</a> (11),
<a href='/products/4'>Joystick</a> (15), <a href='/products/5'>CPU</a> (15),
<a href='/products/2'>Mouse</a> (20)</p>

<p><b>Operating systems</b>:
<a href='/products/7'>Windows</a> (666), <a href='/products/9'>Mac</a> (666),
<a href='/products/8'>Linux</a> (666)</p>
```

Requirements
============

* PHP 5.3 or higher.

Usage
=====

```php
require_once __DIR__ . '/lib/Linq.php'; // replace with your path
use \YaLinqo\Enumerable; // optional, to shorten class name

// 'from' can be called as a static method or via a global function shortcut
Enumerable::from(array(1, 2, 3));
from(array(1, 2, 3));
```

IMPORTANT! Please vote for these bugs!
======================================

If you want to make using the library more pleasurable, you are welcome to vote for the following bugs and features to get them noticed and fixed.

PHP
---

1. Iterator::key() is not allowed to return anything but int or string

   [45684](https://bugs.php.net/bug.php?id=45684) (A request for foreach to be key-type agnostic)

2. Unfortunately, a feature request for simpler Closure syntax was rejected, so you can't vote for it. :-(

PhpStorm IDE
------------

You need to register in order to vote and comment.

1. PHP code inside strings

   [WI-3477](http://youtrack.jetbrains.com/issue/WI-3477) (Inject PHP language inside assert('literal'), eval and similar)

   [WI-2377](http://youtrack.jetbrains.com/issue/WI-2377) (No autocompletion for php variables inside string with injected language)

2. PHP inspections

   [WI-11110](http://youtrack.jetbrains.com/issue/WI-11110) (Undefined method: Undefined method wrongly reported when using closures)

3. PhpDoc bugs

   [WI-8270](http://youtrack.jetbrains.com/issue/WI-8270) (Error in PhpDoc quick documentation if {@link} used twice in a line)

License
=======
**TL;DR: Simplified BSD License**

Copyright (c) 2012, Alexander Prokhorov
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright
  notice, this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL ALEXANDER PROKHOROV BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Links
=====

* [**linq.js**](http://linqjs.codeplex.com/) - LINQ for JavaScript. The one and only complete port of .NET 4 LINQ to JavaScript.
* [**Underscore.js**](http://documentcloud.github.com/underscore/) - library for functional programming in JavaScript. Similar to LINQ, but different method names and no lazy evaluation.
* [**Underscore.php**](http://brianhaveri.github.com/Underscore.php/) - port of Underscore.js to PHP. Identical functionality.
