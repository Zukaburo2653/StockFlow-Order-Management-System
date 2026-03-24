<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class OrderException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 422);
    }
}

// ─── Specific Exception Types ─────────────────────────────────────────────────

namespace App\Exceptions;

class InsufficientStockException extends OrderException
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for '{$productName}'. "
            . "Requested: {$requested}, Available: {$available}.",
            422
        );
    }
}