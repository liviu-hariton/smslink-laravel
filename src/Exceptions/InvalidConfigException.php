<?php

namespace LHDev\Smslink\Exceptions;

use Exception;

class InvalidConfigException extends Exception
{
    public function __construct($message = "Invalid configuration provided", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}