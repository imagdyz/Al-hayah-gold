<?php

namespace App\Models;

use App\Enums\Category;
use App\Services\GoldPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'karat', 'weight_g', 'making_fee', 'sku', 'subtitle',
        'description', 'image', 'gallery', 'is_published', 'is_featured', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'karat' => 'integer',
            'weight_g' => 'decimal:3',
            'making_fee' => 'decimal:2',
            'gallery' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withPivot('quantity');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeBullion(Builder $query): void
    {
        $query->whereIn('category', [Category::Bar->value, Category::Coin->value]);
    }

    public function scopeJewelry(Builder $query): void
    {
        $query->whereNotIn('category', [Category::Bar->value, Category::Coin->value]);
    }

    public function isBullion(): bool
    {
        return $this->category->isBullion();
    }

    public function price(): int
    {
        return app(GoldPricing::class)->productPrice($this);
    }

    public function goldValue(): int
    {
        return app(GoldPricing::class)->goldValue($this);
    }

    public function weightLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->weight_g, 3, '.', ''), '0'), '.').' جم';
    }

    public function karatLabel(): string
    {
        return 'عيار '.$this->karat;
    }

    public static function imageUrl(?string $path): string
    {
        if (! $path) {
            return asset('images/placeholder.svg');
        }
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function imageSrc(): string
    {
        return self::imageUrl($this->image);
    }

    /** @return list<string> */
    public function gallerySrcs(): array
    {
        return array_map(self::imageUrl(...), array_values(array_filter([$this->image, ...($this->gallery ?? [])])));
    }

    /** Branches that hold this piece right now, with quantity > 0. */
    public function stockedBranches()
    {
        return $this->branches->filter(fn (Branch $b) => $b->is_active && $b->pivot->quantity > 0)->values();
    }

    public function stockLabel(): string
    {
        $total = Branch::where('is_active', true)->count();
        $n = $this->stockedBranches()->count();

        return match (true) {
            $n === 0 => 'بالطلب',
            $n === $total && $total > 1 => 'كل الفروع',
            $n === 1 => 'فرع واحد',
            $n === 2 => 'فرعين',
            default => "{$n} فروع",
        };
    }
}
