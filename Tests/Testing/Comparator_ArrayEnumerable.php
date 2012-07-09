<?php

namespace Tests\Testing;

use \YaLinqo\Enumerable;

class Comparator_ArrayEnumerable extends \PHPUnit_Framework_Comparator_Array
{
    /** {@inheritdoc} */
    public function accepts ($actual, $expected)
    {
        return is_array($expected) && $actual instanceof Enumerable;
    }

    /**
     * {@inheritdoc}
     * @param Enumerable $actual
     */
    public function assertEquals ($expected, $actual, $delta = 0, $canonicalize = FALSE, $ignoreCase = FALSE, array &$processed = array())
    {
        parent::assertEquals($expected, $actual->toArrayDeep(), $delta, $canonicalize, $ignoreCase, $processed);
    }
}
