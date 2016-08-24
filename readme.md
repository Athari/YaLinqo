# *YaLinqo: Yet Another LINQ to Objects for PHP*

[![Travis CI Status](https://img.shields.io/travis/Athari/YaLinqo.svg)](https://travis-ci.org/Athari/YaLinqo)
[![Coveralls Coverage](https://img.shields.io/coveralls/Athari/YaLinqo/master.svg)](https://coveralls.io/r/Athari/YaLinqo)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Athari/YaLinqo.svg)](https://scrutinizer-ci.com/g/Athari/YaLinqo)
[![SensioLabs Insight Check](https://img.shields.io/sensiolabs/i/d1273f86-85e3-4076-a037-a40062906329.svg)](https://insight.sensiolabs.com/projects/d1273f86-85e3-4076-a037-a40062906329)
[![VersionEye Dependencies](https://www.versioneye.com/php/athari:yalinqo/badge.svg)](https://www.versioneye.com/php/athari:yalinqo)<br>
[![Packagist Downloads](https://img.shields.io/packagist/dt/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![VersionEye References](https://www.versioneye.com/php/athari:yalinqo/reference_badge.svg)](https://www.versioneye.com/php/athari:yalinqo/references)
[![Packagist Version](https://img.shields.io/packagist/v/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![GitHub License](https://img.shields.io/github/license/Athari/YaLinqo.svg)](license.md)

* [**Online documentation**](http://athari.github.io/YaLinqo)
* [**GitHub repository**](https://github.com/Athari/YaLinqo)

Features
========

* The most complete port of .NET 4 LINQ to PHP, with [many additional methods](#implemented-methods).
* Lazy evaluation, error messages and other behavior of original LINQ.
* [Detailed PHPDoc and online reference](http://athari.github.io/YaLinqo) based on PHPDoc for all methods. Articles are adapted from original LINQ documentation from MSDN.
* 100% unit test coverage.
* Best performance among full-featured LINQ ports (YaLinqo, Ginq, Pinq), at least 2x faster than the closest competitor, see [performance tests](https://github.com/Athari/YaLinqoPerf).
* Callback functions can be specified as closures (like `function ($v) { return $v; }`), PHP "function pointers" (either strings like `'strnatcmp'` or arrays like `array($object, 'methodName')`), string "lambdas" using various syntaxes (`'"$k = $v"'`, `'$v ==> $v+1'`, `'($v, $k) ==> $v + $k'`, `'($v, $k) ==> { return $v + $k; }'`).
* Keys are as important as values. Most callback functions receive both values and the keys; transformations can be applied to both values and the keys; keys are never lost during transformations, if possible.
* SPL interfaces `Iterator`, `IteratorAggregate` etc. are used throughout the code and can be used interchangeably with Enumerable.
* Redundant collection classes are avoided, native PHP arrays are used everywhere.
* Composer support ([package](https://packagist.org/packages/athari/yalinqo) on Packagist).
* No external dependencies.

Implemented methods
===================

Some methods had to be renamed, because their names are reserved keywords. Original methods names are given in parenthesis.

* **Generation**: cycle, emptyEnum (empty), from, generate, toInfinity, toNegativeInfinity, matches, returnEnum (return), range, rangeDown, rangeTo, repeat, split;
* **Projection and filtering**: cast, ofType, select, selectMany, where;
* **Ordering**: orderBy, orderByDescending, orderByDir, thenBy, thenByDescending, thenByDir;
* **Joining and grouping**: groupJoin, join, groupBy;
* **Aggregation**: aggregate, aggregateOrDefault, average, count, max, maxBy, min, minBy, sum;
* **Set**: all, any, contains, distinct, except, intersect, union;
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

// Put products with non-zero quantity into matching categories;
// sort categories by name;
// sort products within categories by quantity descending, then by name.
$result = from($categories)
    ->orderBy('$cat ==> $cat["name"]')
    ->groupJoin(
        from($products)
            ->where('$prod ==> $prod["quantity"] > 0')
            ->orderByDescending('$prod ==> $prod["quantity"]')
            ->thenBy('$prod ==> $prod["name"]'),
        '$cat ==> $cat["id"]', '$prod ==> $prod["catId"]',
        '($cat, $prods) ==> array(
            "name" => $cat["name"],
            "products" => $prods
        )'
    );

// Alternative shorter syntax using default variable names
$result2 = from($categories)
    ->orderBy('$v["name"]')
    ->groupJoin(
        from($products)
            ->where('$v["quantity"] > 0')
            ->orderByDescending('$v["quantity"]')
            ->thenBy('$v["name"]'),
        '$v["id"]', '$v["catId"]',
        'array(
            "name" => $v["name"],
            "products" => $e
        )'
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
        function ($cat, $prods) {
            return array(
                "name" => $cat["name"],
                "products" => $prods
            );
        }
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

Requirements
============

* Version 1: PHP 5.3 or higher.
* Version 2: PHP 5.5 or higher.

Usage
=====

Add to `composer.json`:

```json
{
    "require": {
        "athari/yalinqo": "~2.0"
    }
}
```

Add to your PHP script:

```php
require_once 'vendor/autoloader.php';
use \YaLinqo\Enumerable;

// 'from' can be called as a static method or via a global function shortcut
Enumerable::from(array(1, 2, 3));
from(array(1, 2, 3));
```

License
=======

#### Simplified BSD License

Copyright © 2012–2016, Alexander Prokhorov
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

#### YaLinqo

* **CodeProject** articles *(English):*
  * [LINQ for PHP comparison: YaLinqo, Ginq, Pinq](http://www.codeproject.com/Articles/997238/LINQ-for-PHP-comparison-YaLinqo-Ginq-Pinq) — performance comparison of full-featured LINQ ports, with some additional information.

* **Habrahabr** articles *(Russian):*
  * [Comparison of old LINQ libraries](http://habrahabr.ru/post/147612/) — comparison of *LINQ for PHP*, *Phinq*, *PHPLinq* and *Plinq*, also *Underscore.php*.
  * [YaLinqo 1.0 with updated comparison](http://habrahabr.ru/post/147848/) — explanation of architecture and design decisions.
  * [YaLinqo 2.0](http://habrahabr.ru/post/229763/) — switch to PHP 5.5 with generators support and related changes.
  * [LINQ for PHP: speed matters](http://habrahabr.ru/post/259155/) — performance comparison of full-featured LINQ ports (YaLinqo, Ginq, Pinq).

* **Other**:
  * Tute Wall: [How to use Linq in PHP](http://tutewall.com/how-to-use-linq-in-php-part-01/) by *Mr. X* — a series of posts covering basic usage of YaLinqo. 

* Related projects:
  * [**YaLinqoPerf**](https://github.com/Athari/YaLinqoPerf) — collection of performance tests comparing raw PHP, array functions, YaLinqo, YaLinqo with string lambdas, Ginq, Ginq with property accessors, Pinq.

#### LINQ ported to other languages:

* [**linq.js**](http://linqjs.codeplex.com/) — LINQ for JavaScript. The one and only complete port of .NET 4 LINQ to JavaScript.
* [**Underscore.js**](http://documentcloud.github.com/underscore/) — library for functional programming in JavaScript. Similar to LINQ, but different method names and no lazy evaluation.
* [**Underscore.php**](http://brianhaveri.github.com/Underscore.php/) — port of Underscore.js to PHP. Identical functionality.
