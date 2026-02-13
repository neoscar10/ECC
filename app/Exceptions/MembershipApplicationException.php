<?php

namespace App\Exceptions;

use Exception;

class MembershipApplicationException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => null,
            'meta' => null,
            'errors' => null,
        ], $this->getCode() ?: 400); 
    }
}
