<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use App\Models\VariationAttributeValue;
use App\Models\ProductVariationCombination;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariationController extends Controller
{
    /**
     * Sanitize filename by removing special characters
     */
    private function sanitizeFilename($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nameWithoutExt);
        return $sanitizedName . '.' . $extension;
    }

    /**
     * Custom file validation that handles special characters in filenames
     */
    private function validateFiles($request)
    {
        $errors = [];
        
        \Log::info('Starting custom file validation');
        
        // Validate main image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            \Log::info('Validating main image', [
                'original_name' => $image->getClientOriginalName(),
                'is_valid' => $image->isValid(),
                'error_message' => $image->getErrorMessage(),
                'mime_type' => $image->getMimeType(),
                'size' => $image->getSize()
            ]);
            
            if (!$image->isValid()) {
                $errors['image'] = ['The main image is invalid: ' . $image->getErrorMessage()];
            } else {
                // Check file type
                $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];
                if (!in_array($image->getMimeType(), $allowedMimes)) {
                    $errors['image'] = ['The main image must be a file of type: jpeg, png, jpg, gif, svg, webp.'];
                }
                
                // Check file size (2MB = 2048KB)
                if ($image->getSize() > 2048 * 1024) {
                    $errors['image'] = ['The main image may not be greater than 2MB.'];
                }
            }
        }
        
        // Validate gallery images
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $index => $galleryImage) {
                \Log::info('Validating gallery image', [
                    'index' => $index,
                    'original_name' => $galleryImage->getClientOriginalName(),
                    'is_valid' => $galleryImage->isValid(),
                    'error_message' => $galleryImage->getErrorMessage(),
                    'mime_type' => $galleryImage->getMimeType(),
                    'size' => $galleryImage->getSize()
                ]);
                
                if (!$galleryImage->isValid()) {
                    $errors["gallery.{$index}"] = ['Gallery image ' . ($index + 1) . ' is invalid: ' . $galleryImage->getErrorMessage()];
                } else {
                    // Check file type
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];
                    if (!in_array($galleryImage->getMimeType(), $allowedMimes)) {
                        $errors["gallery.{$index}"] = ['Gallery image ' . ($index + 1) . ' must be a file of type: jpeg, png, jpg, gif, svg, webp.'];
                    }
                    
                    // Check file size (2MB = 2048KB)
                    if ($galleryImage->getSize() > 2048 * 1024) {
                        $errors["gallery.{$index}"] = ['Gallery image ' . ($index + 1) . ' may not be greater than 2MB.'];
                    }
                }
            }
        }
        
        \Log::info('File validation completed', ['errors' => $errors]);
        
        if (!empty($errors)) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                $errors
            );
        }
    }

    /**
     * Build a consistent variation name from selected attribute value IDs.
     */
    private function buildVariationName(array $attributeValueIds): string
    {
        $ids = collect($attributeValueIds)
            ->filter(fn($v) => !is_null($v) && $v !== '')
            ->map(fn($v) => (int) $v)
            ->values();

        if ($ids->isEmpty()) {
            return '';
        }

        $values = VariationAttributeValue::with('attribute')
            ->whereIn('id', $ids)
            ->get()
            // Order by attribute sort_order to keep stable naming like "Color - Size"
            ->sortBy(fn($val) => optional($val->attribute)->sort_order ?? 0)
            ->pluck('value')
            ->values()
            ->all();

        return implode(' - ', $values);
    }

    /**
     * Generate cartesian product of attribute value id sets.
     * Input: [ attributeId => [valueId, ...], ... ]
     * Output: array of combinations where each combination is [ attributeId => valueId, ... ]
     */
    private function generateCombinations(array $attributeIdToValueIds): array
    {
        // Normalize: filter empty and cast to ints
        $normalized = [];
        foreach ($attributeIdToValueIds as $attributeId => $valueIds) {
            $vals = collect((array) $valueIds)
                ->filter(fn($v) => !is_null($v) && $v !== '')
                ->map(fn($v) => (int) $v)
                ->values()
                ->all();
            if (count($vals) > 0) {
                $normalized[(int) $attributeId] = $vals;
            }
        }

        if (empty($normalized)) {
            return [];
        }

        // Build cartesian product
        $result = [[]];
        foreach ($normalized as $attrId => $vals) {
            $append = [];
            foreach ($result as $product) {
                foreach ($vals as $val) {
                    $new = $product;
                    $new[$attrId] = $val;
                    $append[] = $new;
                }
            }
            $result = $append;
        }

        return $result;
    }
    /**
     * Display a listing of variations for a product.
     */
    public function index($productId)
    {
        $product = Product::with(['variations.combinations.attribute', 'variations.combinations.attributeValue', 'variations.stocks'])
            ->findOrFail($productId);
        
        return view('erp.products.variations.index', compact('product'));
    }

    /**
     * Show the form for creating a new variation.
     */
    public function create($productId)
    {
        $product = Product::findOrFail($productId);
        $attributes = VariationAttribute::active()
            ->with(['values' => function($q){ $q->orderBy('sort_order'); }])
            ->get();
        
        return view('erp.products.variations.create', compact('product', 'attributes'));
    }

    /**
     * Store a newly created variation.
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        // Debug: Log the request data
        \Log::info('Variation Store Request Data:', $request->all());
        
        // Preprocess input for validation: detect bulk mode and normalize name length
        $valuesInputForDetection = $request->input('attribute_values', []);
        $isAssociativeForDetection = array_keys((array) $valuesInputForDetection) !== range(0, count((array) $valuesInputForDetection) - 1);
        $hasArraysForDetection = false;
        if ($isAssociativeForDetection) {
            foreach ($valuesInputForDetection as $v) {
                if (is_array($v) && count($v) > 1) { $hasArraysForDetection = true; break; }
            }
        }

        if ($isAssociativeForDetection && $hasArraysForDetection) {
            // In bulk mode, we will auto-generate names; avoid validating an overly long provided name
            $request->merge(['name' => null]);
        } else {
            // In single mode, if provided name is too long, truncate before validation
            $providedName = (string) $request->input('name', '');
            if (mb_strlen($providedName) > 255) {
                $request->merge(['name' => \Illuminate\Support\Str::limit($providedName, 255, '')]);
            }
        }

        // Custom validation for files with special characters
        $this->validateFiles($request);
        
        $request->validate([
            'sku' => 'required|string',
            'name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'status' => 'required|in:active,inactive',
            'attributes' => 'required|array',
            'attribute_values' => 'required|array',
        ]);

        // Check for potential SKU conflicts before processing
        $attributesInput = $request->input('attributes', []);
        $valuesInput = $request->input('attribute_values', []);
        $isAssociative = array_keys($valuesInput) !== range(0, count($valuesInput) - 1);
        $hasArrays = false;
        if ($isAssociative) {
            foreach ($valuesInput as $v) {
                if (is_array($v) && count($v) > 1) { $hasArrays = true; break; }
            }
        }

        if ($isAssociative && $hasArrays) {
            // Check for SKU conflicts in bulk mode
            $combinations = $this->generateCombinations($valuesInput);
            $baseSku = $request->sku;
            $conflictingSkus = [];
            
            foreach ($combinations as $combo) {
                $skuSuffix = collect($combo)->map(function($valId){
                    $val = VariationAttributeValue::with('attribute')->find($valId);
                    return $val ? Str::upper(Str::slug($val->value, '')) : (string) $valId;
                })->implode('');
                
                $fullSku = $baseSku . '-' . $skuSuffix;
                
                // Check if SKU already exists
                if (ProductVariation::where('sku', $fullSku)->exists()) {
                    $conflictingSkus[] = $fullSku;
                }
            }
            
            if (!empty($conflictingSkus)) {
                return back()->withInput()->withErrors([
                    'sku' => 'The following SKUs already exist: ' . implode(', ', $conflictingSkus) . '. Please use a different base SKU.'
                ]);
            }
        } else {
            // Check for SKU conflict in single mode
            if (ProductVariation::where('sku', $request->sku)->exists()) {
                return back()->withInput()->withErrors([
                    'sku' => 'This SKU already exists. Please use a different SKU.'
                ]);
            }
        }

        DB::beginTransaction();
        
        try {
            // Detect bulk create: attribute_values is associative and contains arrays for any attribute
            $attributesInput = $request->input('attributes', []);
            $valuesInput = $request->input('attribute_values', []);
            $isAssociative = array_keys($valuesInput) !== range(0, count($valuesInput) - 1);
            $hasArrays = false;
            if ($isAssociative) {
                foreach ($valuesInput as $v) {
                    if (is_array($v) && count($v) > 1) { $hasArrays = true; break; }
                }
            }

            // Upload main image once (reused for bulk)
            $uploadedImagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $originalName = $image->getClientOriginalName();
                $sanitizedFilename = $this->sanitizeFilename($originalName);
                $imageName = time() . '_' . $sanitizedFilename;
                
                try {
                    // Check if file is valid
                    if (!$image->isValid()) {
                        throw new \Exception('Invalid file: ' . $image->getErrorMessage());
                    }
                    
                    // Ensure directory exists
                    $uploadPath = public_path('uploads/products/variations');
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    \Log::info('Attempting to upload main image', [
                        'original_name' => $originalName,
                        'sanitized_name' => $sanitizedFilename,
                        'final_name' => $imageName,
                        'file_size' => $image->getSize(),
                        'file_mime' => $image->getMimeType()
                    ]);
                    
                    $image->move($uploadPath, $imageName);
                    $uploadedImagePath = 'uploads/products/variations/' . $imageName;
                    
                    \Log::info('Main image uploaded successfully', ['filename' => $imageName]);
                } catch (\Exception $e) {
                    \Log::error('Main image upload failed', [
                        'original_name' => $originalName,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    throw new \Exception('Failed to upload main image: ' . $originalName . ' - ' . $e->getMessage());
                }
            }

            if ($isAssociative && $hasArrays) {
                // BULK: generate all combinations
                $combinations = $this->generateCombinations($valuesInput);
                $createdVariations = [];
                
                foreach ($combinations as $combo) {
                    // Generate unique SKU with counter if needed
                    $baseSkuSuffix = collect($combo)->map(function($valId){
                        $val = VariationAttributeValue::with('attribute')->find($valId);
                        return $val ? Str::upper(Str::slug($val->value, '')) : (string) $valId;
                    })->implode('');
                    
                    $baseSku = $request->sku . '-' . $baseSkuSuffix;
                    $finalSku = $baseSku;
                    $counter = 1;
                    
                    // Ensure SKU uniqueness
                    while (ProductVariation::where('sku', $finalSku)->exists()) {
                        $finalSku = $baseSku . '-' . $counter;
                        $counter++;
                    }
                    
                    // Generate variation name
                    $generatedName = $this->buildVariationName(array_values($combo));
                    // Ensure name length does not exceed DB column limit
                    $generatedName = \Illuminate\Support\Str::limit($generatedName, 255, '');
                    
                    // Create variation per combo
                    $variationData = [
                        'product_id' => $productId,
                        'sku' => $finalSku,
                        'name' => $generatedName !== '' ? $generatedName : (string) $request->name,
                        'price' => $request->price,
                        'cost' => $request->cost,
                        'discount' => $request->discount,
                        'is_default' => false, // default set can be updated later individually
                        'status' => $request->status,
                    ];
                    if ($uploadedImagePath) { $variationData['image'] = $uploadedImagePath; }

                    $variation = ProductVariation::create($variationData);
                    $createdVariations[] = $variation;
                    
                    foreach ($combo as $attributeId => $attributeValueId) {
                        ProductVariationCombination::create([
                            'variation_id' => $variation->id,
                            'attribute_id' => (int) $attributeId,
                            'attribute_value_id' => (int) $attributeValueId,
                        ]);
                    }

                    // Optional: duplicate gallery images to each variation
                    if ($request->hasFile('gallery')) {
                        foreach ($request->file('gallery') as $index => $galleryImage) {
                            $originalName = $galleryImage->getClientOriginalName();
                            $sanitizedFilename = $this->sanitizeFilename($originalName);
                            $galleryName = time() . '_' . $variation->id . '_g' . $index . '_' . $sanitizedFilename;
                            
                            try {
                                // Check if file is valid
                                if (!$galleryImage->isValid()) {
                                    throw new \Exception('Invalid file: ' . $galleryImage->getErrorMessage());
                                }
                                
                                // Ensure directory exists
                                $uploadPath = public_path('uploads/products/variations/gallery');
                                if (!is_dir($uploadPath)) {
                                    mkdir($uploadPath, 0755, true);
                                }
                                
                                // Check directory permissions
                                if (!is_writable($uploadPath)) {
                                    throw new \Exception('Upload directory is not writable: ' . $uploadPath);
                                }
                                
                                \Log::info('Attempting to upload gallery image', [
                                    'original_name' => $originalName,
                                    'sanitized_name' => $sanitizedFilename,
                                    'final_name' => $galleryName,
                                    'upload_path' => $uploadPath,
                                    'file_size' => $galleryImage->getSize(),
                                    'file_mime' => $galleryImage->getMimeType()
                                ]);
                                
                                $galleryImage->move($uploadPath, $galleryName);
                                
                                ProductVariationGallery::create([
                                    'variation_id' => $variation->id,
                                    'image' => 'uploads/products/variations/gallery/' . $galleryName,
                                    'sort_order' => $index,
                                ]);
                                
                                \Log::info('Gallery image uploaded successfully', ['filename' => $galleryName]);
                            } catch (\Exception $e) {
                                \Log::error('Gallery image upload failed', [
                                    'original_name' => $originalName,
                                    'error' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine()
                                ]);
                                throw new \Exception('Failed to upload gallery image: ' . $originalName . ' - ' . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                // SINGLE: behaves as before
                $variationData = [
                    'product_id' => $productId,
                    'sku' => $request->sku,
                    // name may be overwritten below by generated name; ensure <=255
                    'name' => \Illuminate\Support\Str::limit((string) $request->name, 255, ''),
                    'price' => $request->price,
                    'cost' => $request->cost,
                    'discount' => $request->discount,
                    'is_default' => $request->boolean('is_default'),
                    'status' => $request->status,
                ];
                if ($uploadedImagePath) { $variationData['image'] = $uploadedImagePath; }

                $variation = ProductVariation::create($variationData);

                $isAssoc = $isAssociative;
                if ($isAssoc) {
                    foreach ($valuesInput as $attributeId => $attributeValueId) {
                        if (!$attributeId || !$attributeValueId) { continue; }
                        ProductVariationCombination::create([
                            'variation_id' => $variation->id,
                            'attribute_id' => (int) $attributeId,
                            'attribute_value_id' => (int) $attributeValueId,
                        ]);
                    }
                } else {
                    foreach ($attributesInput as $index => $attributeId) {
                        $attributeValueId = $valuesInput[$index] ?? null;
                        if (!$attributeId || !$attributeValueId) { continue; }
                        ProductVariationCombination::create([
                            'variation_id' => $variation->id,
                            'attribute_id' => (int) $attributeId,
                            'attribute_value_id' => (int) $attributeValueId,
                        ]);
                    }
                }

                $valueIds = $isAssoc ? array_values($valuesInput) : array_values($valuesInput);
                $generatedName = $this->buildVariationName($valueIds);
                if ($generatedName !== '') {
                    $variation->update(['name' => \Illuminate\Support\Str::limit($generatedName, 255, '')]);
                }

                if ($request->hasFile('gallery')) {
                    foreach ($request->file('gallery') as $index => $galleryImage) {
                        $originalName = $galleryImage->getClientOriginalName();
                        $sanitizedFilename = $this->sanitizeFilename($originalName);
                        $galleryName = time() . '_gallery_' . $index . '_' . $sanitizedFilename;
                        
                        try {
                            // Check if file is valid
                            if (!$galleryImage->isValid()) {
                                throw new \Exception('Invalid file: ' . $galleryImage->getErrorMessage());
                            }
                            
                            // Ensure directory exists
                            $uploadPath = public_path('uploads/products/variations/gallery');
                            if (!is_dir($uploadPath)) {
                                mkdir($uploadPath, 0755, true);
                            }
                            
                            \Log::info('Attempting to upload single variation gallery image', [
                                'original_name' => $originalName,
                                'sanitized_name' => $sanitizedFilename,
                                'final_name' => $galleryName,
                                'file_size' => $galleryImage->getSize(),
                                'file_mime' => $galleryImage->getMimeType()
                            ]);
                            
                            $galleryImage->move($uploadPath, $galleryName);
                            
                            ProductVariationGallery::create([
                                'variation_id' => $variation->id,
                                'image' => 'uploads/products/variations/gallery/' . $galleryName,
                                'sort_order' => $index,
                            ]);
                            
                            \Log::info('Single variation gallery image uploaded successfully', ['filename' => $galleryName]);
                        } catch (\Exception $e) {
                            \Log::error('Single variation gallery image upload failed', [
                                'original_name' => $originalName,
                                'error' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            throw new \Exception('Failed to upload gallery image: ' . $originalName . ' - ' . $e->getMessage());
                        }
                    }
                }

                if ($variation->is_default) {
                    ProductVariation::where('product_id', $productId)
                        ->where('id', '!=', $variation->id)
                        ->update(['is_default' => false]);
                }
            }

            // Update product to have variations
            $product->update(['has_variations' => true]);

            DB::commit();
            
            $message = 'Product variation created successfully.';
            if (isset($createdVariations) && count($createdVariations) > 1) {
                $message = count($createdVariations) . ' product variations created successfully.';
            }
            
            return redirect()->route('erp.products.variations.index', $productId)
                ->with('success', $message);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Variation creation error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error creating variation: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified variation.
     */
    public function show($productId, $variationId)
    {
        $product = Product::findOrFail($productId);
        $variation = ProductVariation::with([
            'combinations.attribute', 
            'combinations.attributeValue', 
            'stocks.branch', 
            'stocks.warehouse',
            'galleries'
        ])->findOrFail($variationId);
        
        return view('erp.products.variations.show', compact('product', 'variation'));
    }

    /**
     * Show the form for editing the specified variation.
     */
    public function edit($productId, $variationId)
    {
        $product = Product::findOrFail($productId);
        $variation = ProductVariation::with(['combinations.attribute', 'combinations.attributeValue', 'galleries'])
            ->findOrFail($variationId);
        $attributes = VariationAttribute::active()
            ->with(['values' => function($q){ $q->orderBy('sort_order'); }])
            ->get();
        
        return view('erp.products.variations.edit', compact('product', 'variation', 'attributes'));
    }

    /**
     * Update the specified variation.
     */
    public function update(Request $request, $productId, $variationId)
    {
        $product = Product::findOrFail($productId);
        $variation = ProductVariation::findOrFail($variationId);
        
        // Custom validation for files with special characters
        $this->validateFiles($request);
        
        $request->validate([
            'sku' => 'required|string|unique:product_variations,sku,' . $variationId,
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'status' => 'required|in:active,inactive',
            'attributes' => 'required|array',
            'attribute_values' => 'required|array',
        ]);

        DB::beginTransaction();
        
        try {
            // Update the variation
            $variationData = [
                'sku' => $request->sku,
                // Temporarily set; will be overwritten below from attribute values
                'name' => $request->name,
                'price' => $request->price,
                'cost' => $request->cost,
                'discount' => $request->discount,
                'is_default' => $request->boolean('is_default'),
                'status' => $request->status,
            ];

            // Handle main image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($variation->image) {
                    @unlink(public_path($variation->image));
                }
                
                $image = $request->file('image');
                $originalName = $image->getClientOriginalName();
                $sanitizedFilename = $this->sanitizeFilename($originalName);
                $imageName = time() . '_' . $sanitizedFilename;
                
                try {
                    $image->move(public_path('uploads/products/variations'), $imageName);
                    $variationData['image'] = 'uploads/products/variations/' . $imageName;
                } catch (\Exception $e) {
                    \Log::error('Main image upload failed during update: ' . $e->getMessage());
                    throw new \Exception('Failed to upload main image: ' . $originalName);
                }
            }

            $variation->update($variationData);

            // Update attribute combinations (supports both indexed and keyed formats)
            $variation->combinations()->delete();
            $attributesInput = $request->input('attributes', []);
            $valuesInput = $request->input('attribute_values', []);
            $isAssoc = array_keys($valuesInput) !== range(0, count($valuesInput) - 1);
            if ($isAssoc) {
                foreach ($valuesInput as $attributeId => $attributeValueId) {
                    if (!$attributeId || !$attributeValueId) { continue; }
                    ProductVariationCombination::create([
                        'variation_id' => $variation->id,
                        'attribute_id' => (int) $attributeId,
                        'attribute_value_id' => (int) $attributeValueId,
                    ]);
                }
            } else {
                foreach ($attributesInput as $index => $attributeId) {
                    $attributeValueId = $valuesInput[$index] ?? null;
                    if (!$attributeId || !$attributeValueId) { continue; }
                    ProductVariationCombination::create([
                        'variation_id' => $variation->id,
                        'attribute_id' => (int) $attributeId,
                        'attribute_value_id' => (int) $attributeValueId,
                    ]);
                }
            }

            // Overwrite variation name with auto-generated name from the saved combinations
            $valueIds = $isAssoc ? array_values($valuesInput) : array_values($valuesInput);
            $generatedName = $this->buildVariationName($valueIds);
            if ($generatedName !== '') {
                $variation->update(['name' => $generatedName]);
            }

            // Handle gallery images
            if ($request->hasFile('gallery')) {
                // Delete old gallery images
                foreach ($variation->galleries as $gallery) {
                    @unlink(public_path($gallery->image));
                }
                $variation->galleries()->delete();
                
                foreach ($request->file('gallery') as $index => $galleryImage) {
                    $originalName = $galleryImage->getClientOriginalName();
                    $sanitizedFilename = $this->sanitizeFilename($originalName);
                    $galleryName = time() . '_gallery_' . $index . '_' . $sanitizedFilename;
                    
                    try {
                        $galleryImage->move(public_path('uploads/products/variations/gallery'), $galleryName);
                        
                        ProductVariationGallery::create([
                            'variation_id' => $variation->id,
                            'image' => 'uploads/products/variations/gallery/' . $galleryName,
                            'sort_order' => $index,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Gallery image upload failed during update: ' . $e->getMessage());
                        throw new \Exception('Failed to upload gallery image: ' . $originalName);
                    }
                }
            }

            // If this is set as default, unset other defaults
            if ($variation->is_default) {
                ProductVariation::where('product_id', $productId)
                    ->where('id', '!=', $variation->id)
                    ->update(['is_default' => false]);
            }

            DB::commit();
            
            return redirect()->route('erp.products.variations.index', $productId)
                ->with('success', 'Product variation updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Error updating variation: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified variation.
     */
    public function destroy($productId, $variationId)
    {
        $variation = ProductVariation::findOrFail($variationId);
        
        DB::beginTransaction();
        
        try {
            // Delete images
            if ($variation->image) {
                @unlink(public_path($variation->image));
            }
            
            foreach ($variation->galleries as $gallery) {
                @unlink(public_path($gallery->image));
            }
            
            // Delete the variation (cascade will handle related records)
            $variation->delete();
            
            // Check if product still has variations
            $remainingVariations = ProductVariation::where('product_id', $productId)->count();
            if ($remainingVariations == 0) {
                Product::where('id', $productId)->update(['has_variations' => false]);
            }
            
            DB::commit();
            
            return redirect()->route('erp.products.variations.index', $productId)
                ->with('success', 'Product variation deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error deleting variation: ' . $e->getMessage());
        }
    }

    /**
     * Get attribute values for AJAX requests.
     */
    public function getAttributeValues($attributeId)
    {
        $values = VariationAttributeValue::where('attribute_id', $attributeId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
            
        return response()->json($values);
    }

    /**
     * Toggle variation status.
     */
    public function toggleStatus($productId, $variationId)
    {
        $variation = ProductVariation::findOrFail($variationId);
        $variation->update([
            'status' => $variation->status === 'active' ? 'inactive' : 'active'
        ]);
        
        return response()->json([
            'success' => true,
            'status' => $variation->status
        ]);
    }
}
