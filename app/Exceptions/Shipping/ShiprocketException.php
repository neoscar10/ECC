<?php

namespace App\Exceptions\Shipping;

use Exception;

class ShiprocketException extends Exception
{
    protected $responseBody;

    public function __construct($message = "", $code = 0, $responseBody = null, Exception $previous = null)
    {
        $this->responseBody = $responseBody;
        parent::__construct($message, $code, $previous);
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
