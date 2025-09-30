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

}
