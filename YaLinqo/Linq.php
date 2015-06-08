<?php

/**
 * Global functions.
 * @author Alexander Prokhorov
 * @license Simplified BSD
 * @link https://github.com/Athari/YaLinqo YaLinqo on GitHub
 */

if (!function_exists('from')) {
    /**
     * Create Enumerable from an array or any other traversible source.
     * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $source
     * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
     * @return \YaLinqo\Enumerable
     * @see \YaLinqo\Enumerable::from
     */
    function from ($source)
    {
        return \YaLinqo\Enumerable::from($source);
    }
}