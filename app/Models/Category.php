<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'parent_id', 'name', 'slug', 'image', 'description', 'seo_content',
    'seo_title', 'seo_description', 'seo_keywords', 'is_active',
    'show_on_homepage', 'display_order',
])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_homepage' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $forgetStorefrontCaches = function (): void {
            Cache::forget('shop.nav.category_ids');
            Cache::forget('shop.collections');
        };

        static::saved($forgetStorefrontCaches);
        static::deleted($forgetStorefrontCaches);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subCategoryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Categories that currently have at least one active product in their tree.
     * Keeps empty lines out of nav, sitemap and browse pages (AdSense thin-content safe).
     */
    public function scopeWithCatalogProducts(Builder $query): Builder
    {
        $activeProduct = fn (Builder $products): Builder => $products->where('status', ProductStatus::Active);

        return $query->where(function (Builder $visible) use ($activeProduct): void {
            $visible
                ->whereHas('products', $activeProduct)
                ->orWhereHas('subCategoryProducts', $activeProduct)
                ->orWhereHas('children', function (Builder $child) use ($activeProduct): void {
                    $child->where('is_active', true)
                        ->where(function (Builder $childVisible) use ($activeProduct): void {
                            $childVisible
                                ->whereHas('products', $activeProduct)
                                ->orWhereHas('subCategoryProducts', $activeProduct);
                        });
                });
        });
    }

    public function hasCatalogProducts(): bool
    {
        if ($this->products()->where('status', ProductStatus::Active)->exists()) {
            return true;
        }

        if ($this->subCategoryProducts()->where('status', ProductStatus::Active)->exists()) {
            return true;
        }

        if ($this->parent_id !== null) {
            return false;
        }

        return $this->children()
            ->where('is_active', true)
            ->where(function (Builder $child): void {
                $child->whereHas('products', fn (Builder $products) => $products->where('status', ProductStatus::Active))
                    ->orWhereHas('subCategoryProducts', fn (Builder $products) => $products->where('status', ProductStatus::Active));
            })
            ->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        if ($this->parent) {
            return url('/'.$this->parent->slug.'/'.$this->slug);
        }

        return url('/'.$this->slug);
    }

    public function imageUrl(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/'.$this->image);
        }

        return asset('images/category-placeholder.svg');
    }

    /**
     * Shared visual for sibling lines (e.g. Mens + Womens Apparel use one house image).
     */
    public function mosaicImageUrl(): string
    {
        $parent = $this->parent;
        if ($parent) {
            if ($parent->image && Storage::disk('public')->exists($parent->image)) {
                return asset('storage/'.$parent->image);
            }

            $siblings = $parent->relationLoaded('children')
                ? $parent->children
                : $parent->children()->get();

            $shared = $siblings->first(function (self $sibling) {
                return $sibling->image
                    && Storage::disk('public')->exists($sibling->image);
            });

            if ($shared) {
                return asset('storage/'.$shared->image);
            }
        }

        return $this->imageUrl();
    }
}
