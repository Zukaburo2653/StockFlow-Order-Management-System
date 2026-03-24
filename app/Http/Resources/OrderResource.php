<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'status'       => $this->status,
            'is_editable'  => $this->isEditable(),
            // Cast to float so assertJsonPath(..., 375.00) passes in tests.
            // Laravel's decimal:2 cast returns a string ("375.00"), not a float.
            'total_amount' => round((float) $this->total_amount, 2),
            'notes'        => $this->notes,
            // Use whenLoaded to avoid (a) lazy-loading N+1 on index and
            // (b) calling ->count() on an unloaded relation (returns 0 silently).
            'items_count'  => $this->whenLoaded(
                'orderItems',
                fn () => $this->orderItems->count(),
                0
            ),
            'items'        => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}