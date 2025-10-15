<?php

/**
 * Global functions and initialization.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

use YaLinqo\Enumerable, YaLinqo\Functions, YaLinqo\Utils;

Functions::init();
Utils::init();

if (!function_exists('from')) {
    /**
     * Create Enumerable from an array or any other traversable source.
     * @param array|Iterator|IteratorAggregate|Enumerable|iterable $source
     * @return Enumerable
     * @throws InvalidArgumentException If source is not array or Traversable or Enumerable.
     * @throws Exception If source iterator throws.
     * @see Enumerable::from
     */
    function from($source): Enumerable
    {
        return Enumerable::from($source);
    }
}
