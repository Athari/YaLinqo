# *YaLinqo: Yet Another LINQ to Objects for PHP*

[![Travis CI Status](https://img.shields.io/travis/Athari/YaLinqo.svg)](https://travis-ci.org/Athari/YaLinqo)
[![Coveralls Coverage](https://img.shields.io/coveralls/Athari/YaLinqo/master.svg)](https://coveralls.io/r/Athari/YaLinqo)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Athari/YaLinqo.svg)](https://scrutinizer-ci.com/g/Athari/YaLinqo)
[![Packagist Downloads](https://img.shields.io/packagist/dt/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![Packagist Version](https://img.shields.io/packagist/v/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![GitHub License](https://img.shields.io/github/license/Athari/YaLinqo.svg)](license.md)

* [**Online documentation**](http://athari.github.io/YaLinqo)
* [**GitHub repository**](https://github.com/Athari/YaLinqo)

Features
========

* The most complete port of .NET LINQ to PHP, with [many additional methods](#implemented-methods).
* Lazy evaluation, error messages and other behavior of original LINQ.
* [Detailed PHPDoc and online reference](http://athari.github.io/YaLinqo) based on PHPDoc for all methods. Articles are adapted from original LINQ documentation from MSDN.
* 100% unit test coverage.
* Best performance among full-featured LINQ ports (YaLinqo, Ginq, Pinq), at least 2x faster than the closest competitor, see [performance tests](https://github.com/Athari/YaLinqoPerf).
* Callback functions can be specified as arrow functions (`fn($v) => $v`), first-class callables (`strnatcmp(...)`) or any other [PHP callables](https://www.php.net/manual/language.types.callable.php).
* Keys are as important as values. Most callback functions receive both values and the keys; transformations can be applied to both values and the keys; keys are never lost during transformations, if possible.
* SPL interfaces `Iterator`, `IteratorAggregate` etc. are used throughout the code and can be used interchangeably with `Enumerable`.
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
* **Set**: all, any, append, concat, contains, distinct, except, intersect, prepend, union;
* **Pagination**: elementAt, elementAtOrDefault, first, firstOrDefault, firstOrFallback, last, lastOrDefault, lastOrFallback, single, singleOrDefault, singleOrFallback, indexOf, lastIndexOf, findIndex, findLastIndex, skip, skipWhile, take, takeWhile;
* **Conversion**: toArray, toArrayDeep, toList, toListDeep, toDictionary, toJSON, toLookup, toKeys, toValues, toObject, toString;
* **Actions**: call (do), each (forEach), write, writeLine.

In total, more than 80 methods.

Example
=======

*Process sample data:*

```php
// Data
$products = [
    ['name' => 'Keyboard', 'catId' => 'hw', 'quantity' => 10, 'id' => 1],
    ['name' => 'Mouse', 'catId' => 'hw', 'quantity' => 20, 'id' => 2],
    ['name' => 'Monitor', 'catId' => 'hw', 'quantity' => 0, 'id' => 3],
    ['name' => 'Joystick', 'catId' => 'hw', 'quantity' => 15, 'id' => 4],
    ['name' => 'CPU', 'catId' => 'hw', 'quantity' => 15, 'id' => 5],
    ['name' => 'Motherboard', 'catId' => 'hw', 'quantity' => 11, 'id' => 6],
    ['name' => 'Windows', 'catId' => 'os', 'quantity' => 666, 'id' => 7],
    ['name' => 'Linux', 'catId' => 'os', 'quantity' => 666, 'id' => 8],
    ['name' => 'Mac', 'catId' => 'os', 'quantity' => 666, 'id' => 9],
];
$categories = [
    ['name' => 'Hardware', 'id' => 'hw'],
    ['name' => 'Operating systems', 'id' => 'os'],
];

// Put products with non-zero quantity into matching categories;
// sort categories by name;
// sort products within categories by quantity descending, then by name.
$result = from($categories)
    ->orderBy(fn($cat) => $cat['name'])
    ->groupJoin(
        from($products)
            ->where(fn($prod) => $prod['quantity'] > 0)
            ->orderByDescending(fn($prod) => $prod['quantity'])
            ->thenBy(fn($prod) => $prod['name'], 'strnatcasecmp'),
        fn($cat) => $cat['id'],
        fn($prod) => $prod['catId'],
        fn($cat, $prods) => [
            'name' => $cat['name'],
            'products' => $prods
        ]
    );

// More verbose syntax with argument names:
$result = Enumerable::from($categories)
    ->orderBy(keySelector: fn($cat) => $cat['name'])
    ->groupJoin(
        inner: from($products)
            ->where(predicate: fn($prod) => $prod['quantity'] > 0)
            ->orderByDescending(keySelector: fn($prod) => $prod['quantity'])
            ->thenBy(keySelector: fn($prod) => $prod['name'], comparer: strnatcasecmp(...)),
        outerKeySelector: fn($cat) => $cat['id'],
        innerKeySelector: fn($prod) => $prod['catId'],
        resultSelectorValue: fn($cat, $prods) => [
            'name' => $cat['name'],
            'products' => $prods
        ]
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

Versions
========

| Version        | Status      | PHP     | Notes |
|----------------|-------------|---------|-------|
| **1.x** (2012) |
| ​    1.0−1.1    | legacy      | 5.3−7.4 | • Manually implemented iterators |
| **2.x** (2014) |
| ​    2.0−2.4    | legacy      | 5.5−7.4 | • Rewrite using PHP 5.5 generators<br>• Causes deprecation warnings in PHP 7.2+ due to use of `create_function` |
| ​    2.5+       | maintenance | 5.5+    | • Switched from `create_function` to `eval` for string lambdas<br>• May cause security analysis warnings due to use of `eval` |
| **3.x** (2025) |
| ​    3.0        | abandoned   | 7.0−7.4 | • Abandoned rewrite with perfomance improvements<br>• Released with performance-related changes dropped. |
| **4.x**        |
| ​    4.0        | planned     | 8.0+ (?)| • PHP 8.0 support, strong types everywhere, string lambdas nuked from existence. |

Usage
=====

Add to `composer.json`:

```json
{
    "require": {
        "athari/yalinqo": "^2.0"
    }
}
```

Add to your PHP script:

```php
require_once 'vendor/autoloader.php';
use \YaLinqo\Enumerable;

// 'from' can be called as a static method or via a global function shortcut
Enumerable::from([1, 2, 3]);
from([1, 2, 3]);
```

Legacy information
==================

Legacy features
---------------

* (**Versions 1.0−2.5**) Callback functions can be specified as "string lambdas" using various syntaxes:
    * `'"$k = $v"'` (implicit `$v` and `$k` arguments, implicit return)
    * `'$v ==> $v + 1'` (like a modern arrow function, but without `fn` and with a longer arrow)
    * `'($v, $k) ==> $v + $k'` (explicit arguments, implicit return)
    * `'($v, $k) ==> { return $v + $k; }'` (explicit arguments, explicit return within a block)

> [!NOTE]
>
> Before arrow functions were added in PHP 7.4, the choice was between the ridiculously verbose anonymous function syntax (`function ($value) { return $value['key']; }`) and rolling your own lambda syntax (like `$v ==> $v["key"]`). This is why "string lambdas" were a necessity at the time.

> [!CAUTION]
>
> When using legacy versions of YaLinqo and PHP, you:
>
> 1. **MUST NOT** use user-provided strings to construct string lambdas;
> 2. **SHOULD NOT** dynamically construct string lambdas in general.
>
> When all your string lambdas are *single-quoted string constants*, there's no security risk in using them. If you're still paranoid about `eval`, just never use string lambdas.

License
=======

[**Simplified BSD License**](license.md)<br>
Copyright © 2012–2025, Alexander Prokhorov

Links
=====

### YaLinqo Articles

* **CodeProject** *(English):*
  * [LINQ for PHP comparison: YaLinqo, Ginq, Pinq](http://www.codeproject.com/Articles/997238/LINQ-for-PHP-comparison-YaLinqo-Ginq-Pinq) — performance comparison of full-featured LINQ ports, with some additional information.

* **Habrahabr** *(Russian):*
  * [Comparison of old LINQ libraries](http://habrahabr.ru/post/147612/) — comparison of *LINQ for PHP*, *Phinq*, *PHPLinq* and *Plinq*, also *Underscore.php*.
  * [YaLinqo 1.0 with updated comparison](http://habrahabr.ru/post/147848/) — explanation of architecture and design decisions.
  * [YaLinqo 2.0](http://habrahabr.ru/post/229763/) — switch to PHP 5.5 with generators support and related changes.
  * [LINQ for PHP: speed matters](http://habrahabr.ru/post/259155/) — performance comparison of full-featured LINQ ports (YaLinqo, Ginq, Pinq).

* **Other** *(English):*
  * Tute Wall: [How to use Linq in PHP](http://tutewall.com/how-to-use-linq-in-php-part-01/) by *Mr. X* — a series of posts covering basic usage of YaLinqo.

### Related projects

* [**linq.js**](http://linqjs.codeplex.com/) — LINQ for JavaScript. The one and only complete port of .NET LINQ to JavaScript.
* [**Underscore.js**](http://documentcloud.github.com/underscore/) — library for functional programming in JavaScript. Similar to LINQ, but different method names and no lazy evaluation.
* [**Underscore.php**](http://brianhaveri.github.com/Underscore.php/) — port of Underscore.js to PHP.
* [**RxPHP**](https://github.com/ReactiveX/RxPHP) — reactive (push) counterpart of the active (pull) LINQ, port of Rx.NET.
* [**YaLinqoPerf**](https://github.com/Athari/YaLinqoPerf) — collection of performance tests comparing raw PHP, array functions, YaLinqo, YaLinqo with string lambdas, Ginq, Ginq with property accessors, Pinq.

### PHP

* Dead PHP RFCs:
  * [Short Closures 2.0](https://wiki.php.net/rfc/auto-capture-closure) — support for multi-statement arrow functions declined, 2 votes short.
  * [Add `array_group` function](https://wiki.php.net/rfc/array_column_results_grouping) — grouping won't be optimized by using a built-in function.
  * [Partial function application](https://wiki.php.net/rfc/partial_function_application) — imagine pipe operator being actually useful... nah, not happening.
  * [Pipe operator v3](https://wiki.php.net/rfc/pipe-operator-v3) ([v2](https://wiki.php.net/rfc/pipe-operator-v2), [v1](https://wiki.php.net/rfc/pipe-operator)) — took 3 RFCs and 10 years, but we've finally arrived at... the least useful and the most verbose pipe syntax on the planet... yay?