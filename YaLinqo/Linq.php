<?php

// @codeCoverageIgnoreStart
spl_autoload_register(function($class)
{
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file))
        require_once($file);
});
// @codeCoverageIgnoreEnd

/**
 * @param array|\Iterator|\IteratorAggregate|\YaLinqo\Enumerable $source
 * @throws \InvalidArgumentException If source is not array or Traversible or Enumerable.
 * @return \YaLinqo\Enumerable
 */
function from ($source)
{
    return \YaLinqo\Enumerable::from($source);
}
