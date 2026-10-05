<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'is_bullion' => $this->isBullion(),
            'karat' => $this->karat,
            'weight_g' => (float) $this->weight_g,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price(),
            'gold_value' => $this->goldValue(),
            'making_fee' => (float) $this->making_fee,
            'image' => $this->imageSrc(),
            'gallery' => $this->gallerySrcs(),
            'stock' => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($b) => [
                'branch_id' => $b->id,
                'branch' => $b->fullName(),
                'quantity' => (int) $b->pivot->quantity,
            ])),
        ];
    }
}
