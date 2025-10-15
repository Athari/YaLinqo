# *YaLinqo: Yet Another LINQ to Objects for PHP*

[![Coveralls Coverage](https://img.shields.io/coveralls/Athari/YaLinqo/master.svg)](https://coveralls.io/r/Athari/YaLinqo)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Athari/YaLinqo.svg)](https://scrutinizer-ci.com/g/Athari/YaLinqo)
[![Packagist Downloads](https://img.shields.io/packagist/dt/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![Packagist Version](https://img.shields.io/packagist/v/athari/yalinqo.svg)](https://packagist.org/packages/athari/yalinqo)
[![GitHub License](https://img.shields.io/github/license/Athari/YaLinqo.svg)](license.md)

* [**Reference documentation**](http://athari.github.io/YaLinqo)
* [**GitHub repository**](https://github.com/Athari/YaLinqo)

Features
========

* The most complete port of .NET LINQ to PHP, with [many additional methods](#implemented-methods).
* Lazy evaluation, error messages and other behavior of original LINQ.
* [Detailed PHPDoc and online reference](http://athari.github.io/YaLinqo) based on PHPDoc for all methods. Articles are adapted from original LINQ documentation from MSDN.
* 100% unit test coverage.
* Best performance among full-featured LINQ ports (YaLinqo, Ginq, Pinq), at least 2x faster than the closest competitor, see [performance tests](https://github.com/Athari/YaLinqoPerf).
* Callback functions can be specified as arrow functions (`fn($v) => $v`), first-class callables (`strnatcmp(...)`) or any other [PHP callables](https://www.php.net/manual/language.types.callable.php).
* Keys are as important as values. Most callback functions receive both values and keys; transformations can be applied to both values and keys; keys are never lost during transformations, if possible.
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

Usage
=====

Add to `composer.json`:

```json
{
    "require": {
        "athari/yalinqo": "^3.0"
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

Example
=======

*Process sample data:*

```php
// Data
$products = [
    [ 'name' => 'Keyboard',    'catId' => 'hw', 'quantity' =>  10, 'id' => 1 ],
    [ 'name' => 'Mouse',       'catId' => 'hw', 'quantity' =>  20, 'id' => 2 ],
    [ 'name' => 'Monitor',     'catId' => 'hw', 'quantity' =>   0, 'id' => 3 ],
    [ 'name' => 'Joystick',    'catId' => 'hw', 'quantity' =>  15, 'id' => 4 ],
    [ 'name' => 'CPU',         'catId' => 'hw', 'quantity' =>  15, 'id' => 5 ],
    [ 'name' => 'Motherboard', 'catId' => 'hw', 'quantity' =>  11, 'id' => 6 ],
    [ 'name' => 'Windows',     'catId' => 'os', 'quantity' => 666, 'id' => 7 ],
    [ 'name' => 'Linux',       'catId' => 'os', 'quantity' => 666, 'id' => 8 ],
    [ 'name' => 'Mac',         'catId' => 'os', 'quantity' => 666, 'id' => 9 ],
];
$categories = [
    [ 'name' => 'Hardware',          'id' => 'hw' ],
    [ 'name' => 'Operating systems', 'id' => 'os' ],
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

// More verbose syntax with parameter names (PHP 8.0+)
// and first-class callables (PHP 8.1+):
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

```pwsh
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

<table>

<tr>
<th>Version</th>
<th>Status</th>
<th>PHP</th>
<th>Notes</th>

<tr>
<td colspan=4><h3><b>1.x</b> (2012)

<tr>
<td valign=top>1.0−1.1
<td valign=top>legacy
<td valign=top>5.3−7.4
<td><ul>
    <li>Manually implemented iterators

<tr>
<td colspan=4><h3><b>2.x</b> (2014)

<tr>
<td valign=top>2.0−2.4
<td valign=top>legacy
<td valign=top>5.5−7.4
<td><ul>
    <li>Rewrite using PHP 5.5 generators
    <li>Causes deprecation warnings in PHP 7.2+ due to use of <code>create_function</code>

<tr>
<td valign=top>2.5
<td valign=top>maintenance
<td valign=top>5.5+
<td><ul>
    <li>Switched from <code>create_function</code> to <code>eval</code> for string lambdas
    <li>May cause security analysis warnings due to use of <code>eval</code>

<tr>
<td colspan=4><h3><b>3.x</b> (2018)

<tr>
<td valign=top>3.0
<td valign=top>maintenance
<td valign=top>7.0+
<td><ul>
    <li>Abandoned rewrite with perfomance improvements
    <li>Released 7 years later with most of the performance-related changes dropped
    <li>May cause security analysis warnings due to use of <code>eval</code>

<tr>
<td colspan=4><h3><b>4.x</b> (2025)

<tr>
<td valign=top>4.0
<td valign=top>planned
<td valign=top>8.0+(?)
<td><ul>
    <li>Strong types everywhere, string lambdas nuked from existence

</table>

Breaking changes
================

Version 1.x → 2.x
------------------

* Minimum supported PHP version is 5.5.
* Collections `Dictionary` and `Lookup` were replaced with standard arrays.

Version 2.x → 3.x
-----------------

* Minimum supported PHP version is 7.0.
* Type hints were added to parameters of some functions (`ofType`, `range`, `rangeDown`, `rangeTo`, `toInfinity`, `toNegativeInfinity`, `matches`, `split`). There may be edge cases if you rely on passing incorrect types of arguments.

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
> **When using legacy versions of YaLinqo and PHP:**
>
> 1. You **MUST NOT**[^1] use user-provided strings to construct string lambdas. This directly opens you to passing to user-provided strings to `eval`, which is literally the worst thing you can do security-wise.
> 2. You **SHOULD NOT**[^1] dynamically construct string lambdas in general, even if it seems convenient. Passing incorrect code to `eval` throws a `ParseError`. An exception to this rule may be constructing a *trivial* lambda from an array of *predefined* values.
> 3. You **SHOULD**[^1] use full closure syntax instead of string lambdas when you need access to variables in scope.
>
> When all your string lambdas are *single-quoted string constants*, there's no security risk in using them. If you're still paranoid about `eval`, just never use string lambdas.

Links
=====

### Documentation

* [Reference documentation](http://athari.github.io/YaLinqo) — generated from source. Includes MSDN-tier explanation of what every method does.
* [How to use Linq in PHP](https://web.archive.org/web/2019/http://tutewall.com/how-to-use-linq-in-php-part-01/) (acrhived) by *Mr. X* — a series of posts covering basic usage of YaLinqo.

> [!TIP]
> If you're new to LINQ, you should read the series of articles by Mr. X, as they're very beginner-friednly.

### Articles

* **CodeProject** *(English):*
  * [LINQ for PHP comparison: YaLinqo, Ginq, Pinq](http://www.codeproject.com/Articles/997238/LINQ-for-PHP-comparison-YaLinqo-Ginq-Pinq) — performance comparison of full-featured LINQ ports, with some additional information.

* **Habrahabr** *(Russian):*
  * [Comparison of old LINQ libraries](http://habrahabr.ru/post/147612/) — comparison of *LINQ for PHP*, *Phinq*, *PHPLinq* and *Plinq*, also *Underscore.php*.
  * [YaLinqo 1.0 with updated comparison](http://habrahabr.ru/post/147848/) — explanation of architecture and design decisions.
  * [YaLinqo 2.0](http://habrahabr.ru/post/229763/) — switch to PHP 5.5 with generators support and related changes.
  * [LINQ for PHP: speed matters](http://habrahabr.ru/post/259155/) — performance comparison of full-featured LINQ ports (YaLinqo, Ginq, Pinq).

### Alternatives

Realistically, there're none. This is the only PHP library in existence which implements lazy evaluation, deals with keys in iterators properly, has documentation and actually works (until yet another breaking change in PHP), with everything else failing in 2+ ways. However, some alternatives are worth mentioning.

* [**Laravel LazyCollection**](https://laravel.com/docs/collections#lazy-collections) (Laravel 6.0+) — The closest you can get to LINQ-to-objects in PHP without YaLinqo. Includes SQL-isms like `where('balance', '>', '100')`, Ruby-isms like `pluck('my.hair')`, random non-pure methods like `forget('name')` and other footguns, but largely functional. Note that lazy evaluation is opt-in: you need to call either `LazyCollection::make($iterable)` or `collect($array)->lazy()`.
* [**RxPHP**](https://github.com/ReactiveX/RxPHP) — reactive (push) counterpart of the active (pull) LINQ, port of Rx.NET. A faithful implementation of Rx in PHP by people who actually use it. Highly recommended if you need complex transformations over asynchronous operations.

### Related projects

* [**linq.js**](https://github.com/mihaifm/linq) — LINQ for JavaScript. The one and only complete port of .NET LINQ to JavaScript. Supports TypeScript, ESM, CJS, browsers.
* [**Underscore.js**](https://underscorejs.org/) — library for functional programming in JavaScript. Similar to LINQ, but different method names and no lazy evaluation.
* [**YaLinqoPerf**](https://github.com/Athari/YaLinqoPerf) — collection of performance tests comparing raw PHP, array functions, YaLinqo, YaLinqo with string lambdas, Ginq, Ginq with property accessors, Pinq.

### PHP

If you want to contribute to the project without writing any code, consider annoying the developers of PHP on GitHub and their mailing list whenever they decline yet another useful feature.

If you're successful and actually get them to implement PFA + Pipe v4 (?), then non-lazy LINQ ports will lose 80% of their users, as PHP array functions will become usable by themselves without turning the code into unreadable spaghetti.

And if devs of PHP implement [pipes for iterables](https://wiki.php.net/rfc/pipe-operator-v3#iterable_api), then YaLinqo itself will need a complete rewrite for 20% of cases and become obsolete for 80% of them. I wouldn't hold my breath though, as that thing has been in discussion for like 10 years already.

* Graveyard of PHP RFCs:
  * [Partial function application](https://wiki.php.net/rfc/partial_function_application) — imagine pipe operator being actually useful... nah, not happening.
  * [Short Closures 2.0](https://wiki.php.net/rfc/auto-capture-closure) — support for multi-statement arrow functions declined, 2 votes short.
  * [Add `array_group` function](https://wiki.php.net/rfc/array_column_results_grouping) — grouping won't be optimized by using a built-in function. Not a huge loss, but still a pity.

* Too little too late:
  * [Pipe operator v3](https://wiki.php.net/rfc/pipe-operator-v3) ([v2](https://wiki.php.net/rfc/pipe-operator-v2), [v1](https://wiki.php.net/rfc/pipe-operator)) — took 3 RFCs and 10 years, but we've finally arrived at... the least useful and the most verbose pipe syntax on the planet... yay?
  * [Arrow functions v2](https://wiki.php.net/rfc/arrow_functions_v2) ([v1](https://wiki.php.net/rfc/arrow_functions), [v0](https://wiki.php.net/rfc/short_closures)) — took 3 RFCs and just 5 years. A notable exception of actually being in a good state. However, *zero* plans from the "future scope" were implemented in the following years.

License
=======

[**Simplified BSD License**](license.md)<br>
Copyright © 2012–2025, Alexander Prokhorov

History
=======

<a href="https://www.star-history.com/#Athari/YaLinqo&type=date&legend=bottom-right">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=Athari/YaLinqo&type=date&theme=dark&legend=bottom-right" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=Athari/YaLinqo&type=date&legend=bottom-right" />
   <img alt="Star history chart" title="Star history chart" src="https://api.star-history.com/svg?repos=Athari/YaLinqo&type=date&legend=bottom-right" />
 </picture>
</a>

[![Contributors](https://contrib.rocks/image?repo=Athari/YaLinqo&columns=12&max=24&anon=0 "Contributors")](https://github.com/Athari/YaLinqo/graphs/contributors)

[^1]: The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to be interpreted as described in [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119).