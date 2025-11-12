<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'sku', 'short_desc', 'description', 'features', 'category_id', 'price', 'discount', 'cost', 'image', 'size_chart', 'status', 'meta_title', 'meta_description', 'meta_keywords', 'has_variations', 'manage_stock', 'free_delivery'
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'has_variations' => 'boolean',
        'manage_stock' => 'boolean',
        'free_delivery' => 'boolean',
    ];

    public function galleries()
    {
        return $this->hasMany(ProductGallery::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductServiceCategory::class);
    }

    public function branchStock()
    {
        return $this->hasMany(BranchProductStock::class);
    }

    public function warehouseStock()
    {
        return $this->hasMany(WarehouseProductStock::class);
    }

    public function saleItems()
    {
        return $this->hasMany(PosItem::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->approved();
    }

    public function featuredReviews()
    {
        return $this->hasMany(Review::class)->featured();
    }

    public function averageRating()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    public function totalReviews()
    {
        return $this->approvedReviews()->count();
    }

    public function getRatingDistribution()
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->approvedReviews()->byRating($i)->count();
        }
        return $distribution;
    }


    /**
     * Get the product variations.
     */
    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get active product variations.
     */
    public function activeVariations()
    {
        return $this->variations()->where('status', 'active');
    }

    /**
     * Get the default variation.
     */
    public function defaultVariation()
    {
        return $this->variations()->where('is_default', true)->first();
    }

    /**
     * Get the minimum price from variations.
     */
    public function getMinPriceAttribute()
    {
        if ($this->has_variations && $this->activeVariations()->exists()) {
            return $this->activeVariations()->min('price') ?? $this->price;
        }
        return $this->price;
    }

    /**
     * Get the maximum price from variations.
     */
    public function getMaxPriceAttribute()
    {
        if ($this->has_variations && $this->activeVariations()->exists()) {
            return $this->activeVariations()->max('price') ?? $this->price;
        }
        return $this->price;
    }

    /**
     * Get the price range for variations.
     */
    public function getPriceRangeAttribute()
    {
        if ($this->has_variations && $this->variations()->exists()) {
            $minPrice = $this->min_price;
            $maxPrice = $this->max_price;
            
            if ($minPrice == $maxPrice) {
                return number_format($minPrice, 2);
            }
            
            return number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2);
        }
        
        return number_format($this->price, 2);
    }

    /**
     * Get the total stock across all variations.
     */
    public function getTotalVariationStockAttribute()
    {
        if ($this->has_variations) {
            return $this->activeVariations()->with('stocks')->get()->sum(function($variation) {
                return $variation->stocks->sum('quantity');
            });
        }
        
        // Use query builder instead of accessing loaded relationships directly
        $branchStock = $this->branchStock()->sum('quantity') ?? 0;
        $warehouseStock = $this->warehouseStock()->sum('quantity') ?? 0;
        
        return $branchStock + $warehouseStock;
    }

    /**
     * Check if product has stock.
     */
    public function hasStock()
    {
        if ($this->has_variations) {
            return $this->activeVariations()->whereHas('stocks', function($query) {
                $query->where('quantity', '>', 0);
            })->exists();
        }
        
        // Use query builder instead of accessing loaded relationships directly
        $branchStock = $this->branchStock()->sum('quantity') ?? 0;
        $warehouseStock = $this->warehouseStock()->sum('quantity') ?? 0;
        
        return $branchStock > 0 || $warehouseStock > 0;
    }

    /**
     * Get variation by attribute values.
     */
    public function getVariationByAttributes($attributeValues)
    {
        if (!$this->has_variations) {
            return null;
        }

        $variations = $this->variations()->with('combinations.attributeValue')->get();
        
        foreach ($variations as $variation) {
            $variationAttributeValues = $variation->combinations->pluck('attributeValue.value')->sort()->toArray();
            $requestedValues = collect($attributeValues)->sort()->toArray();
            
            if ($variationAttributeValues === $requestedValues) {
                return $variation;
            }
        }
        
        return null;
    }

    /**
     * Find a variation by attribute value IDs (order independent).
     */
    public function getVariationByAttributeValueIds(array $attributeValueIds)
    {
        if (!$this->has_variations) {
            return null;
        }

        $requestedIds = collect($attributeValueIds)->map(function($id){ return (int) $id; })->sort()->values()->all();

        $variations = $this->variations()->with('combinations')->get();
        foreach ($variations as $variation) {
            $variationIds = $variation->combinations->pluck('attribute_value_id')->map(function($id){ return (int) $id; })->sort()->values()->all();
            if ($variationIds === $requestedIds) {
                return $variation;
            }
        }

        return null;
    }

    /**
     * Get available attribute values for this product.
     */
    public function getAvailableAttributeValues()
    {
        if (!$this->has_variations) {
            return collect();
        }

        $attributes = collect();
        
        $this->variations()->with('combinations.attribute', 'combinations.attributeValue')->get()->each(function($variation) use ($attributes) {
            $variation->combinations->each(function($combination) use ($attributes) {
                $attribute = $combination->attribute;
                $value = $combination->attributeValue;
                
                if (!$attributes->has($attribute->id)) {
                    $attributes->put($attribute->id, [
                        'attribute' => $attribute,
                        'values' => collect()
                    ]);
                }
                
                $attributes[$attribute->id]['values']->put($value->id, $value);
            });
        });
        
        return $attributes;
    }

    /**
     * Get the product attributes (specifications).
     */
    public function productAttributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Get specifications as key-value pairs.
     */
    public function getSpecificationsAttribute()
    {
        return $this->productAttributes()->get()->mapWithKeys(function ($attribute) {
            return [$attribute->name => $attribute->pivot->value];
        });
    }

    /**
     * Get the effective price considering bulk discounts
     */
    public function getEffectivePriceAttribute()
    {
        // If product has a direct discount, use that
        if ($this->discount && $this->discount > 0) {
            return $this->discount;
        }

        // Check for applicable bulk discounts
        $bulkDiscount = $this->getApplicableBulkDiscount();
        
        if ($bulkDiscount) {
            return $bulkDiscount->calculateDiscountedPrice($this->price);
        }

        // Return original price if no discounts apply
        return $this->price;
    }

    /**
     * Get applicable bulk discount for this product
     */
    public function getApplicableBulkDiscount()
    {
        $validDiscounts = \App\Models\BulkDiscount::valid()->get();
        
        foreach ($validDiscounts as $discount) {
            if ($discount->appliesToProduct($this->id)) {
                return $discount;
            }
        }
        
        return null;
    }

    /**
     * Check if product has any discount (direct or bulk)
     */
    public function hasDiscount(): bool
    {
        if ($this->discount && $this->discount > 0) {
            return true;
        }

        return $this->getApplicableBulkDiscount() !== null;
    }

    /**
     * Get the original price (before any discounts)
     */
    public function getOriginalPriceAttribute()
    {
        return $this->price;
    }

}
