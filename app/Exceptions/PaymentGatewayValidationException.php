<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;

class PaymentGatewayValidationException extends Exception implements Responsable
{
    protected array $customErrors;
    protected int $statusCode;

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message, array $errors = [], int $statusCode = 422)
    {
        parent::__construct($message);
        $this->customErrors = $errors;
        $this->statusCode = $statusCode;
    }

    /**
     * Get the custom errors.
     */
    public function getErrors(): array
    {
        return $this->customErrors;
    }

    /**
     * Get the status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Convert the exception into an HTTP response.
     */
    public function toResponse($request)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'data' => null,
                'meta' => (object)[],
                'errors' => $this->customErrors,
            ], $this->statusCode);
        }

        // Web default behavior
        return redirect()->back()
            ->withInput()
            ->withErrors($this->customErrors);
    }
}
