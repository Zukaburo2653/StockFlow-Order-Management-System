<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add policy-based auth here if needed
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:1000'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'At least one item is required.',
            'items.array'                 => 'Items must be an array.',
            'items.min'                   => 'At least one item is required.',
            'items.max'                   => 'Maximum 20 items per order.',
            'items.*.product_id.required' => 'Each item must have a product_id.',
            'items.*.product_id.integer'  => 'product_id must be an integer.',
            'items.*.quantity.required'   => 'Each item must have a quantity.',
            'items.*.quantity.min'        => 'Quantity must be at least 1.',
            'items.*.quantity.max'        => 'Quantity cannot exceed 1000 per item.',
        ];
    }

    // Return JSON error instead of redirect
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
