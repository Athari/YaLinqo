<?php

namespace YaLinqo\exceptions;
use YaLinqo\exceptions;

class NotSupportedException extends \RuntimeException
{
    public function __construct ($message = 'Not supported', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
