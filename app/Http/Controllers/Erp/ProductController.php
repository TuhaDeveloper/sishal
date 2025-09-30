<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function categoryList(Request $request)
    {
        $query = ProductServiceCategory::with(['parent', 'children'])->whereNull('parent_id');
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ;
            });
        }
        $categories = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        $allCategories = ProductServiceCategory::orderBy('name')->get(['id','name']);
        return view('erp.productCategory.categoryList', compact('categories', 'allCategories'));
    }

    public function subcategoryList(Request $request)
    {
        $query = ProductServiceCategory::with('parent')->whereNotNull('parent_id');
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ;
            });
        }
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }
        $subcategories = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        $parentCategories = ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get(['id','name']);
        return view('erp.productCategory.subcategoryList', compact('subcategories', 'parentCategories'));
    }

    public function storeSubcategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|unique:product_service_categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'parent_id' => 'required|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        }

        ProductServiceCategory::create($data);

        return redirect()->back()->with('success', 'Subcategory created successfully!');
    }

    public function updateSubcategory(Request $request, $id)
    {
        $subcategory = ProductServiceCategory::findOrFail($id);
        // Handle AJAX status toggle
        if ($request->ajax() && $request->has('status')) {
            $subcategory->update(['status' => $request->status]);
            return response()->json(['success' => true]);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [Rule::unique('product_service_categories', 'slug')->ignore($subcategory->id)],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'parent_id' => 'required|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id']);

        if ((int)$data['parent_id'] === (int)$subcategory->id) {
            return redirect()->back()->withErrors(['parent_id' => 'A subcategory cannot be its own parent.'])->withInput();
        }

        if ($request->hasFile('image')) {
            if ($subcategory->image && file_exists(public_path($subcategory->image))) {
                @unlink(public_path($subcategory->image));
            }
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        }

        $subcategory->update($data);

        return redirect()->back()->with('success', 'Subcategory updated successfully!');
    }

    public function deleteSubcategory($id)
    {
        $subcategory = ProductServiceCategory::findOrFail($id);
        if ($subcategory->image && file_exists(public_path($subcategory->image))) {
            @unlink(public_path($subcategory->image));
        }
        $subcategory->delete();
        return redirect()->back()->with('success', 'Subcategory deleted successfully!');
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|unique:product_service_categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'parent_id' => 'nullable|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        }

        ProductServiceCategory::create($data);

        return redirect()->back()->with('success', 'Category created successfully!');
    }

    
    public function updateCategory(Request $request, $id)
    {
        $category = ProductServiceCategory::findOrFail($id);
        
        // Handle AJAX status toggle
        if ($request->ajax() && $request->has('status')) {
            $category->update(['status' => $request->status]);
            // If parent category is set to inactive, cascade to subcategories
            if ($request->status === 'inactive') {
                ProductServiceCategory::where('parent_id', $category->id)->update(['status' => 'inactive']);
            }
            return response()->json(['success' => true]);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [Rule::unique('product_service_categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status']);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image && file_exists(public_path($category->image))) {
                @unlink(public_path($category->image));
            }
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        }

        $category->update($data);

        // Cascade inactivation to subcategories when status explicitly provided
        if (array_key_exists('status', $data) && $data['status'] === 'inactive') {
            ProductServiceCategory::where('parent_id', $category->id)->update(['status' => 'inactive']);
        }

        return redirect()->back()->with('success', 'Category updated successfully!');
    }

    public function deleteCategory($id)
    {
        $category = ProductServiceCategory::findOrFail($id);
        // Delete image file if exists
        if ($category->image && file_exists(public_path($category->image))) {
            @unlink(public_path($category->image));
        }
        $category->delete();
        return redirect()->back()->with('success', 'Category deleted successfully!');
    }

    /**
     * Store a newly created resource in storage.
     */

    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by category if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by product name or SKU if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%")
                  ;
            });
        }

        $products = $query->with('category')->paginate(12)->withQueryString();

        return view('erp.products.productlist', compact('products'));
    }

    public function create()
    {
        return view('erp.products.create');
    }
    
    public function store(Request $request)
    {
        // Debug: Log the request data
        \Log::info('Product Store Request Data:', $request->all());
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|unique:products,slug',
            'type' => 'required|in:product,service',
            'sku' => 'required|string|unique:products,sku',
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:product_service_categories,id',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'cost' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'gallery' => 'nullable',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'nullable|in:active,inactive',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'slug', 'type', 'sku', 'short_desc', 'description', 'category_id', 'price', 'discount', 'cost', 'status', 'meta_title', 'meta_description']);
        
        // Handle meta_keywords array - convert to JSON string for storage
        if ($request->has('meta_keywords') && is_array($request->meta_keywords)) {
            // Filter out empty keywords
            $keywords = array_filter($request->meta_keywords, function($keyword) {
                return !empty(trim($keyword));
            });
            $data['meta_keywords'] = json_encode(array_values($keywords));
        }

        // Handle main image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $data['image'] = 'uploads/products/' . $imageName;
        }

        $product = Product::create($data);

        // Handle gallery images upload
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $galleryImage) {
                $galleryImageName = time().'_'.uniqid().'.'.$galleryImage->getClientOriginalExtension();
                $galleryImage->move(public_path('uploads/products/gallery'), $galleryImageName);
                $product->galleries()->create([
                    'image' => 'uploads/products/gallery/' . $galleryImageName
                ]);
            }
        }

        return redirect()->route('product.list')->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category', 'galleries')->findOrFail($id);
        return view('erp.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::with('category', 'galleries')->findOrFail($id);
        return view('erp.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Debug: Log the request data
        \Log::info('Product Update Request Data:', $request->all());
        
        $product = Product::with('galleries')->findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'unique:products,slug,' . $product->id,
            'type' => 'required|in:product,service',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:product_service_categories,id',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'cost' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gallery' => 'nullable',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'slug', 'type', 'sku', 'short_desc', 'description', 'category_id', 'price', 'discount', 'cost', 'status', 'meta_title', 'meta_description']);
        
        // Handle meta_keywords array - convert to JSON string for storage
        if ($request->has('meta_keywords') && is_array($request->meta_keywords)) {
            // Filter out empty keywords
            $keywords = array_filter($request->meta_keywords, function($keyword) {
                return !empty(trim($keyword));
            });
            $data['meta_keywords'] = json_encode(array_values($keywords));
        }

        // Handle main image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image));
            }
            $image = $request->file('image');
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $data['image'] = 'uploads/products/' . $imageName;
        }

        $product->update($data);

        // Handle gallery images upload
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $galleryImage) {
                $galleryImageName = time().'_'.uniqid().'.'.$galleryImage->getClientOriginalExtension();
                $galleryImage->move(public_path('uploads/products/gallery'), $galleryImageName);
                $product->galleries()->create([
                    'image' => 'uploads/products/gallery/' . $galleryImageName
                ]);
            }
        }

        return redirect()->route('product.list')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return redirect()->route('product.list')->with('success', 'Product deleted successfully!');
    }

    public function searchCategory(Request $request)
    {
        $q = $request->q;
        $query = ProductServiceCategory::query();
        if ($q) {
            $query->where('name', 'like', "%$q%")
                  ->orWhere('description', 'like', "%$q%")
                  ->orWhere('status', 'like', "%$q%")
                  ;
        }
        $categories = $query->orderBy('name')->limit(20)->get(['id', 'name']);
        return response()->json($categories);
    }

    /**
     * Remove a gallery image from a product.
     */

     public function addGalleryImage(Request $request)
     {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);
        $product = Product::findOrFail($request->product_id);
        $image = $request->file('image');
        $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
        $image->move(public_path('uploads/products/gallery'), $imageName);
        $product->galleries()->create([
            'image' => 'uploads/products/gallery/' . $imageName
        ]);
        return response()->json(['success' => true, 'message' => 'Gallery image added successfully!']);
     }
    public function deleteGalleryImage($id)
    {
        $gallery = \App\Models\ProductGallery::findOrFail($id);
        // Delete image file if exists
        if ($gallery->image && file_exists(public_path($gallery->image))) {
            @unlink(public_path($gallery->image));
        }
        $gallery->delete();
        return redirect()->back()->with('success', 'Gallery image removed successfully!');
    }

    public function productSearch(Request $request)
    {
        $q = $request->q;
        $query = Product::query();
        if ($q) {
            $query->where('name', 'like', "%$q%")
                  ->orWhere('sku', 'like', "%$q%")
                  ;
        }
        $products = $query->orderBy('name')->limit(20)->get(['id', 'name']);
        return response()->json($products);
    }

    public function getPrice($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        return response()->json(['price' => $product->price]);
    }


    public function searchProductWithFilters(Request $request, $branchId)
    {
        $query = Product::with(['category', 'branchStock' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }])
        ->whereHas('branchStock', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('id', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('sku', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Always paginate for testing (per page = 1)
        $products = $query->paginate(12); // 1 per page for testing

        $products->getCollection()->transform(function($product) use ($branchId) {
            $branchStock = $product->branchStock->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => $product->type,
                'price' => $product->price,
                'cost' => $product->cost,
                'discount' => $product->discount,
                'status' => $product->status,
                'image' => $product->image,
                'description' => $product->description,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'branch_stock' => [
                    'branch_id' => $branchStock->branch_id,
                    'branch_name' => $branchStock->branch->name ?? 'Unknown Branch',
                    'quantity' => $branchStock->quantity,
                    'last_updated_at' => $branchStock->last_updated_at
                ],
                'total_stock' => $product->branchStock->sum('quantity')
            ];
        });

        // Return paginated response with meta
        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

}
