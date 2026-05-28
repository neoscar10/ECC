<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OtpException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): ?JsonResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'data' => null,
                'meta' => null,
                'errors' => null,
            ], $this->getCode() ?: 400);
        }

        return null;
    }
}
