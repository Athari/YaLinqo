<?php

namespace YaLinqo\exceptions;
use YaLinqo\exceptions;

class NotSupportedException extends \RuntimeException
{
    public function __construct ($message = "", $code = 0, \Exception $previous = null)
    {
        parent::__construct('Not supported', $code, $previous);
    }
}
