<?php

namespace YaLinqo\exceptions;
use YaLinqo\exceptions;

class NotSupportedException extends \RuntimeException
{
    const ERROR_NOT_SUPPORTED = 'Specified method is not supported.';

    public function __construct ($message = self::ERROR_NOT_SUPPORTED, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
