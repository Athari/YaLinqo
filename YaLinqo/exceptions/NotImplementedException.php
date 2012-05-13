<?php

namespace YaLinqo\exceptions;
use YaLinqo\exceptions;

class NotImplementedException extends \RuntimeException
{
    public function __construct ($message = "", $code = 0, \Exception $previous = null)
    {
        parent::__construct('Not implemented', $code, $previous);
    }
}
