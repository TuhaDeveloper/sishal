<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductServiceCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'status', 'parent_id'
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get all child category IDs recursively (including nested children)
     * 
     * @return array
     */
    public function getAllChildIds()
    {
        $ids = [$this->id];
        
        // Load children if not already loaded
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllChildIds());
        }
        
        return $ids;
    }

    /**
     * Get all child category IDs for given category IDs (static method)
     * 
     * @param array $categoryIds
     * @return array
     */
    public static function getAllChildIdsForCategories(array $categoryIds)
    {
        $allIds = [];
        
        // Load categories with their children recursively
        $categories = self::with('children')->whereIn('id', $categoryIds)->get();
        
        foreach ($categories as $category) {
            // Load all nested children recursively
            $category->loadNestedChildren();
            $allIds = array_merge($allIds, $category->getAllChildIds());
        }
        
        return array_unique($allIds);
    }

    /**
     * Load all nested children recursively
     * 
     * @return void
     */
    public function loadNestedChildren()
    {
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        
        foreach ($this->children as $child) {
            $child->loadNestedChildren();
        }
    }
}
