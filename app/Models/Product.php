<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'sku', 'short_desc', 'description', 'category_id', 'price', 'discount', 'cost', 'image', 'status', 'meta_title', 'meta_description', 'meta_keywords'
    ];

    protected $casts = [
        'meta_keywords' => 'array',
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

    public function averageRating()
    {
        return $this->reviews()->approved()->avg('rating') ?? 0;
    }

    public function totalReviews()
    {
        return $this->reviews()->approved()->count();
    }

}
