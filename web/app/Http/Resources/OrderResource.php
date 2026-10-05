<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'title' => $this->title(),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'product' => $this->product ? ['slug' => $this->product->slug, 'name' => $this->product->name, 'image' => $this->product->imageSrc()] : null,
            'quantity' => $this->quantity,
            'karat' => $this->karat,
            'weight_g' => $this->weight_g ? (float) $this->weight_g : null,
            'unit_price' => (float) $this->unit_price,
            'total' => (float) $this->total,
            'deposit' => (float) $this->deposit,
            'pay_method' => $this->pay_method,
            'locked_until' => $this->locked_until?->toIso8601String(),
            'slot_at' => $this->slot_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
