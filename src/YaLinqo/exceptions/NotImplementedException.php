<?php

namespace YaLinqo\exceptions;
use YaLinqo\exceptions;

class NotImplementedException extends \RuntimeException
{
    const ERROR_NOT_IMPLEMENTED = 'The method or operation is not yet implemented.';

    public function __construct ($message = self::ERROR_NOT_IMPLEMENTED, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
