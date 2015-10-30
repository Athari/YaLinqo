<?php

namespace YaLinqo;

class Errors
{
    /** Error message: "Sequence contains no elements." */
    const NO_ELEMENTS = 'Sequence contains no elements.';
    /** Error message: "Sequence contains no matching elements." */
    const NO_MATCHES = 'Sequence contains no matching elements.';
    /** Error message: "Sequence does not contain the key." */
    const NO_KEY = 'Sequence does not contain the key.';
    /** Error message: "Sequence contains more than one element." */
    const MANY_ELEMENTS = 'Sequence contains more than one element.';
    /** Error message: "Sequence contains more than one matching element." */
    const MANY_MATCHES = 'Sequence contains more than one matching element.';
    /** Error message: "count must be a non-negative value." */
    const COUNT_LESS_THAN_ZERO = 'count must be a non-negative value.';
    /** Error message: "step must be a positive value." */
    const STEP_NEGATIVE = 'step must be a positive value.';
    /** Error message: "type must by one of built-in types." */
    const UNSUPPORTED_BUILTIN_TYPE = 'type must by one of built-in types.';
}