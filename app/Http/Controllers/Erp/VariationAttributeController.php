<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\VariationAttribute;
use App\Models\VariationAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class VariationAttributeController extends Controller
{
    /**
     * Display a listing of attributes.
     */
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view attribute list')) {
            abort(403, 'Unauthorized action.');
        }
        $attributes = VariationAttribute::with('values')->orderBy('sort_order')->get();
        return view('erp.variation-attributes.index', compact('attributes'));
    }

    /**
     * Show the form for creating a new attribute.
     */
    public function create()
    {
        return view('erp.variation-attributes.create');
    }

    /**
     * Store a newly created attribute.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:variation_attributes,slug',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_color' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:active,inactive',
            'values' => 'required|array',
            'values.*.value' => 'required|string|max:255',
            'values.*.color_code' => 'nullable|string|max:7',
            'values.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $attribute = VariationAttribute::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_required' => $request->boolean('is_required'),
            'is_color' => $request->boolean('is_color'),
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        // Create attribute values
        foreach ($request->values as $index => $valueData) {
            $value = [
                'attribute_id' => $attribute->id,
                'value' => $valueData['value'],
                'color_code' => $valueData['color_code'] ?? null,
                'sort_order' => $index,
                'status' => 'active',
            ];

            // Handle image upload for attribute value
            if (isset($valueData['image']) && $valueData['image']) {
                $image = $valueData['image'];
                $imageName = time() . '_attr_' . $attribute->id . '_' . $index . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/attributes'), $imageName);
                $value['image'] = 'uploads/attributes/' . $imageName;
            }

            VariationAttributeValue::create($value);
        }

        return redirect()->route('erp.variation-attributes.index')
            ->with('success', 'Variation attribute created successfully.');
    }

    /**
     * Display the specified attribute.
     */
    public function show($id)
    {
        $attribute = VariationAttribute::with('values')->findOrFail($id);
        return view('erp.variation-attributes.show', compact('attribute'));
    }

    /**
     * Show the form for editing the specified attribute.
     */
    public function edit($id)
    {
        $attribute = VariationAttribute::with('values')->findOrFail($id);
        return view('erp.variation-attributes.edit', compact('attribute'));
    }

    /**
     * Update the specified attribute.
     */
    public function update(Request $request, $id)
    {
        $attribute = VariationAttribute::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:variation_attributes,slug,' . $id,
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_color' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:active,inactive',
            'values' => 'required|array',
            'values.*.value' => 'required|string|max:255',
            'values.*.color_code' => 'nullable|string|max:7',
            'values.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $attribute->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_required' => $request->boolean('is_required'),
            'is_color' => $request->boolean('is_color'),
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        // Update attribute values
        $existingValues = $attribute->values->keyBy('id');
        
        foreach ($request->values as $index => $valueData) {
            $valueId = $valueData['id'] ?? null;
            
            $value = [
                'value' => $valueData['value'],
                'color_code' => $valueData['color_code'] ?? null,
                'sort_order' => $index,
                'status' => 'active',
            ];

            // Handle image upload for attribute value
            if (isset($valueData['image']) && $valueData['image']) {
                // Delete old image if exists
                if ($valueId && $existingValues->has($valueId) && $existingValues[$valueId]->image) {
                    $oldPath = public_path($existingValues[$valueId]->image);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                
                $image = $valueData['image'];
                $imageName = time() . '_attr_' . $attribute->id . '_' . $index . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/attributes'), $imageName);
                $value['image'] = 'uploads/attributes/' . $imageName;
            }

            if ($valueId && $existingValues->has($valueId)) {
                $existingValues[$valueId]->update($value);
                $existingValues->forget($valueId);
            } else {
                VariationAttributeValue::create(array_merge($value, ['attribute_id' => $attribute->id]));
            }
        }

        // Delete removed values
        foreach ($existingValues as $value) {
            if ($value->image) {
                $oldPath = public_path($value->image);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $value->delete();
        }

        // Clear product cache after updating variation attributes
        $this->clearProductCache();

        return redirect()->route('erp.variation-attributes.index')
            ->with('success', 'Variation attribute updated successfully.');
    }

    /**
     * Remove the specified attribute.
     */
    public function destroy($id)
    {
        $attribute = VariationAttribute::with('values')->findOrFail($id);
        
        // Delete images
        foreach ($attribute->values as $value) {
            if ($value->image) {
                $oldPath = public_path($value->image);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }
        
        $attribute->delete();
        
        // Clear product cache after deleting variation attributes
        $this->clearProductCache();
        
        return redirect()->route('erp.variation-attributes.index')
            ->with('success', 'Variation attribute deleted successfully.');
    }

    /**
     * Toggle attribute status.
     */
    public function toggleStatus($id)
    {
        $attribute = VariationAttribute::findOrFail($id);
        $attribute->update([
            'status' => $attribute->status === 'active' ? 'inactive' : 'active'
        ]);
        
        // Clear product cache after toggling status
        $this->clearProductCache();
        
        return response()->json([
            'success' => true,
            'status' => $attribute->status
        ]);
    }

    /**
     * Clear product-related cache
     */
    private function clearProductCache()
    {
        try {
            // Clear product listing cache
            $this->clearCachePattern('products_list_*');
            
            // Clear product details cache
            $this->clearCachePattern('product_details_*');
            
            // Clear API cache
            $this->clearCachePattern('top_selling_products_*');
            $this->clearCachePattern('new_arrivals_products_*');
            $this->clearCachePattern('best_deals_products_*');
        } catch (\Exception $e) {
            \Log::warning('Failed to clear product cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear cache by pattern
     */
    private function clearCachePattern($pattern)
    {
        try {
            $store = Cache::getStore();
            
            // Check if we're using Redis cache driver
            if (method_exists($store, 'getRedis')) {
                // Redis-specific pattern clearing
                $keys = $store->getRedis()->keys($pattern);
                if (!empty($keys)) {
                    $store->getRedis()->del($keys);
                }
            } else {
                // For non-Redis drivers (database, file, array), we need to clear cache differently
                // Since pattern matching isn't available, we'll clear the entire cache
                // This is a fallback approach for database/file cache drivers
                \Log::info("Clearing entire cache due to pattern matching not supported for current driver: " . get_class($store));
                Cache::flush();
            }
        } catch (\Exception $e) {
            // Fallback: try to clear individual cache entries or flush entire cache
            \Log::warning("Could not clear cache pattern {$pattern}: " . $e->getMessage());
            try {
                Cache::flush();
            } catch (\Exception $flushException) {
                \Log::error("Failed to flush cache: " . $flushException->getMessage());
            }
        }
    }
}
