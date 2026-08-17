<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\Season;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function productSearch(Request $request)
    {
        $search = $request->get('q');
        $query = Product::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('sku', 'LIKE', "%$search%")
                  ->orWhere('style_number', 'LIKE', "%$search%");
            });
        }

        $perPage = 30;
        $products = $query->latest()->paginate($perPage, ['id', 'name', 'sku', 'style_number', 'price', 'discount', 'cost', 'wholesale_price', 'has_variations']);

        return response()->json([
            'results' => $products->items(),
            'total_count' => $products->total()
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function categoryList(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
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
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
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
        $parentCategories = ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get(['id','name','slug']);
        return view('erp.productCategory.subcategoryList', compact('subcategories', 'parentCategories'));
    }

    public function storeSubcategory(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                Rule::unique('product_service_categories', 'slug')->where(function ($query) use ($request) {
                    return $query->where('parent_id', $request->parent_id);
                })
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'show_in_ecommerce' => 'nullable|boolean',
            'parent_id' => 'required|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id', 'show_in_ecommerce']);
        if (!isset($data['show_in_ecommerce'])) {
            $data['show_in_ecommerce'] = true;
        }

        if ($request->hasFile('image')) {
            $data['image'] = \App\Services\ImageService::compressAndSave(
                $request->file('image'),
                'uploads/categories'
            );
        }

        ProductServiceCategory::create($data);

        return redirect()->back()->with('success', 'Subcategory created successfully!');
    }

    public function updateSubcategory(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $subcategory = ProductServiceCategory::findOrFail($id);
        // Handle AJAX status toggle
        if ($request->ajax() && $request->has('status')) {
            $subcategory->update(['status' => $request->status]);
            return response()->json(['success' => true]);
        }
        if ($request->ajax() && $request->has('show_in_ecommerce')) {
            $subcategory->update(['show_in_ecommerce' => $request->boolean('show_in_ecommerce')]);
            return response()->json(['success' => true]);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                Rule::unique('product_service_categories', 'slug')
                    ->ignore($subcategory->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('parent_id', $request->parent_id);
                    })
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'show_in_ecommerce' => 'nullable|boolean',
            'parent_id' => 'required|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id', 'show_in_ecommerce']);

        if ((int)$data['parent_id'] === (int)$subcategory->id) {
            return redirect()->back()->withErrors(['parent_id' => 'A subcategory cannot be its own parent.'])->withInput();
        }

        if ($request->hasFile('image')) {
            if ($subcategory->image && file_exists(public_path($subcategory->image))) {
                @unlink(public_path($subcategory->image));
            }
            $data['image'] = \App\Services\ImageService::compressAndSave(
                $request->file('image'),
                'uploads/categories'
            );
        }

        $subcategory->update($data);

        return redirect()->back()->with('success', 'Subcategory updated successfully!');
    }

    public function deleteSubcategory($id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $subcategory = ProductServiceCategory::findOrFail($id);
        if ($subcategory->image && file_exists(public_path($subcategory->image))) {
            @unlink(public_path($subcategory->image));
        }
        $subcategory->delete();
        return redirect()->back()->with('success', 'Subcategory deleted successfully!');
    }

    public function storeCategory(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|unique:product_service_categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'show_in_ecommerce' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:product_service_categories,id',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'parent_id', 'show_in_ecommerce']);
        if (!isset($data['show_in_ecommerce'])) {
            $data['show_in_ecommerce'] = true;
        }

        if ($request->hasFile('image')) {
            $data['image'] = \App\Services\ImageService::compressAndSave(
                $request->file('image'),
                'uploads/categories'
            );
        }

        ProductServiceCategory::create($data);
        
        // Clear category caches
        \App\Services\CacheService::clearCategoryCaches();

        return redirect()->back()->with('success', 'Category created successfully!');
    }

    
    public function updateCategory(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
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
        if ($request->ajax() && $request->has('show_in_ecommerce')) {
            $category->update(['show_in_ecommerce' => $request->boolean('show_in_ecommerce')]);
            \App\Services\CacheService::clearCategoryCaches();
            return response()->json(['success' => true]);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [Rule::unique('product_service_categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'status' => 'nullable|in:active,inactive',
            'show_in_ecommerce' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'slug', 'description', 'status', 'show_in_ecommerce']);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image && file_exists(public_path($category->image))) {
                @unlink(public_path($category->image));
            }
            $data['image'] = \App\Services\ImageService::compressAndSave(
                $request->file('image'),
                'uploads/categories'
            );
        }

        $category->update($data);

        // Cascade inactivation to subcategories when status explicitly provided
        if (array_key_exists('status', $data) && $data['status'] === 'inactive') {
            ProductServiceCategory::where('parent_id', $category->id)->update(['status' => 'inactive']);
        }
        
        // Clear category caches
        \App\Services\CacheService::clearCategoryCaches();

        return redirect()->back()->with('success', 'Category updated successfully!');
    }

    public function deleteCategory($id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $category = ProductServiceCategory::findOrFail($id);
        // Delete image file if exists
        if ($category->image && file_exists(public_path($category->image))) {
            @unlink(public_path($category->image));
        }
        $category->delete();
        
        // Clear category caches
        \App\Services\CacheService::clearCategoryCaches();
        
        return redirect()->back()->with('success', 'Category deleted successfully!');
    }

    /**
     * Store a newly created resource in storage.
     */

    public function index(Request $request)
    {
        if (auth()->user()->hasPermissionTo('view products')) {
            $reportType = $request->get('report_type', 'yearly');
            $restrictedBranchId = $this->getRestrictedBranchId();
            $selectedBranchId = $restrictedBranchId ?: $request->branch_id;
            $selectedWarehouseId = $request->warehouse_id;
            
            if ($reportType == 'monthly') {
                $month = $request->get('month', date('n'));
                $year = $request->get('year', date('Y'));
                $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } elseif ($reportType == 'yearly') {
                $year = $request->get('year', date('Y'));
                $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
                $endDate = $startDate->copy()->endOfYear();
            } else {
                $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
                $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
            }

            $query = $this->buildProductQuery($request);
            $perPage = (int) $request->get('per_page', 50);

            $products = $query->latest()->paginate($perPage)->withQueryString();

            // Fetch lists for filters
            $categories = ProductServiceCategory::where('status', 'active')->get()->sortBy('full_path_name');
            $brands = Brand::where('status', 'active')->orderBy('name')->get();
            $seasons = Season::where('status', 'active')->orderBy('name')->get();
            $genders = Gender::all();
            $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();
            $warehouses = \App\Models\Warehouse::where('status', 'active')->orderBy('name')->get();
            $allProducts = Product::orderBy('name')->get(['id', 'name']);
            $allStyleNumbers = Product::whereNotNull('style_number')->orderBy('style_number')->distinct()->pluck('style_number');

            if ($request->ajax()) {
                return view('erp.products.partials.table', compact('products'))->render();
            }

            return view('erp.products.productlist', compact('products', 'categories', 'brands', 'seasons', 'genders', 'branches', 'warehouses', 'allProducts', 'allStyleNumbers', 'reportType', 'startDate', 'endDate', 'selectedBranchId', 'selectedWarehouseId'));
        }
        else{
            abort(403, 'Unauthorized action.');
        }
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
        $query = $this->buildProductQuery($request);
        $products = $query->latest()->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['SN', 'Entry Date', 'Product Name', 'Style #', 'Category', 'Brand', 'Season', 'Gender', 'Purchase Price', 'MRP', 'Whole Sale', 'Total Stock'];
        foreach($headers as $k => $h) { 
            $sheet->setCellValue(chr(65+$k).'1', $h); 
            $sheet->getStyle(chr(65+$k).'1')->getFont()->setBold(true);
        }
        
        $row = 2;
        foreach($products as $index => $p) {
            $totalVarStock = $p->total_stock_variation ?? 0;
            $totalSimpleStock = ($p->total_stock_branch ?? 0) + ($p->total_stock_warehouse ?? 0);
            $displayStock = $p->has_variations ? $totalVarStock : $totalSimpleStock;

            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, $p->created_at->format('d/m/Y'));
            $sheet->setCellValue('C'.$row, $p->name);
            $sheet->setCellValue('D'.$row, $p->style_number ?? $p->sku);
            $sheet->setCellValue('E'.$row, $p->category->name ?? '-');
            $sheet->setCellValue('F'.$row, $p->brand->name ?? '-');
            $sheet->setCellValue('G'.$row, $p->season->name ?? 'ALL');
            $sheet->setCellValue('H'.$row, $p->gender->name ?? 'ALL');
            $sheet->setCellValue('I'.$row, $p->cost);
            $sheet->setCellValue('J'.$row, $p->price);
            $sheet->setCellValue('K'.$row, $p->wholesale_price ?? 0);
            $sheet->setCellValue('L'.$row, $displayStock);
            $row++;
        }

        foreach(range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'product_list_' . date('Ymd_His') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);
        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function exportCsv(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
        $query = $this->buildProductQuery($request);
        $products = $query->latest()->get();

        $filename = "product_list_" . date('Y-m-d_His') . ".csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('SN', 'Entry Date', 'Product Name', 'Style #', 'Category', 'Brand', 'Season', 'Gender', 'Purchase Price', 'MRP', 'Wholesale Price', 'Total Stock');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $index => $p) {
                $totalVarStock = $p->total_stock_variation ?? 0;
                $totalSimpleStock = ($p->total_stock_branch ?? 0) + ($p->total_stock_warehouse ?? 0);
                $displayStock = $p->has_variations ? $totalVarStock : $totalSimpleStock;

                fputcsv($file, array(
                    $index + 1,
                    $p->created_at->format('d-m-Y'),
                    $p->name,
                    $p->style_number ?? $p->sku,
                    $p->category->name ?? '-',
                    $p->brand->name ?? '-',
                    $p->season->name ?? 'ALL',
                    $p->gender->name ?? 'ALL',
                    $p->cost,
                    $p->price,
                    $p->wholesale_price ?? 0,
                    $displayStock
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
        $query = $this->buildProductQuery($request);
        $products = $query->latest()->limit(100)->get(); 
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.products.product-export-pdf', compact('products'));
        return $pdf->download('product_report_' . date('Y-m-d') . '.pdf');
    }

    private function buildProductQuery(Request $request)
    {
        $reportType = $request->get('report_type', 'yearly');
        $restrictedBranchId = $this->getRestrictedBranchId();
        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;
        $selectedWarehouseId = $request->warehouse_id;
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('n'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = Product::with(['category', 'brand', 'season', 'gender']);

        $query->withSum(['branchStock as total_stock_branch' => function($q) use ($selectedBranchId, $selectedWarehouseId) {
            if ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            } elseif ($selectedWarehouseId) {
                $q->whereRaw('1=0');
            }
        }], 'quantity');
        
        $query->withSum(['warehouseStock as total_stock_warehouse' => function($q) use ($selectedWarehouseId, $selectedBranchId) {
            if ($selectedWarehouseId) {
                $q->where('warehouse_id', $selectedWarehouseId);
            } elseif ($selectedBranchId) {
                $q->whereRaw('1=0'); 
            }
        }], 'quantity');
        
        $query->withSum(['variationStocks as total_stock_variation' => function($q) use ($selectedBranchId, $selectedWarehouseId) {
            if ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            } elseif ($selectedWarehouseId) {
                $q->where('warehouse_id', $selectedWarehouseId);
            }
        }], 'quantity');

        // Date Filters
        if ($startDate) { $query->where('created_at', '>=', $startDate); }
        if ($endDate) { $query->where('created_at', '<=', $endDate); }

        // Relationship Filters
        if ($request->filled('category_id')) { $query->where('category_id', $request->category_id); }
        if ($request->filled('brand_id')) { $query->where('brand_id', $request->brand_id); }
        if ($request->filled('season_id')) { $query->where('season_id', $request->season_id); }
        if ($request->filled('gender_id')) { $query->where('gender_id', $request->gender_id); }

        // Search Logic (Name, SKU, Style)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%")
                  ->orWhere('style_number', 'like', "%$search%");
            });
        }

        if ($request->filled('product_id')) { $query->where('id', $request->product_id); }
        if ($request->filled('style_number')) { $query->where('style_number', 'like', "%$request->style_number%"); }

        return $query;
    }


    public function create()
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $attributes = \App\Models\Attribute::where('status', 'active')->orderBy('name')->get();
        $brands = \App\Models\Brand::where('status', 'active')->get();
        $seasons = \App\Models\Season::where('status', 'active')->get();
        $genders = \App\Models\Gender::all();
        $units = \App\Models\Unit::all();
        $categories = \App\Models\ProductServiceCategory::where('status', 'active')->get();
        return view('erp.products.create', compact('attributes', 'brands', 'seasons', 'genders', 'units', 'categories'));
    }
    
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        // Debug: Log the request data
        \Log::info('Product Store Request Data:', $request->all());
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|unique:products,slug',
            'sku' => 'required|string|unique:products,sku',
            'style_number' => 'nullable|string|max:100',
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'category_id' => 'required|exists:product_service_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'season_id' => 'nullable|exists:seasons,id',
            'gender_id' => 'nullable|exists:genders,id',
            'unit_id' => 'nullable|exists:units,id',
            'price' => 'required|numeric',
            'wholesale_price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'cost' => 'required|numeric',
            'alert_quantity' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'size_chart' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'gallery' => 'nullable',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'nullable|in:active,inactive',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'nullable|string|max:255',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_id' => 'nullable|exists:attributes,id',
            'attributes.*.value' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'slug', 'sku', 'style_number', 'short_desc', 'description', 'features', 'category_id', 'brand_id', 'season_id', 'gender_id', 'unit_id', 'price', 'wholesale_price', 'discount', 'cost', 'alert_quantity', 'status', 'meta_title', 'meta_description']);
        
        // Ensure nullable text fields are not null to avoid DB integrity constraint violations
        $data['features'] = $data['features'] ?? '';
        $data['short_desc'] = $data['short_desc'] ?? '';
        $data['description'] = $data['description'] ?? '';
        $data['type'] = 'product'; // Always set type to product
        $data['has_variations'] = $request->boolean('has_variations');
        $data['manage_stock'] = $request->boolean('manage_stock');
        $data['show_in_ecommerce'] = $request->boolean('show_in_ecommerce');
        
        // Handle meta_keywords array - convert to JSON string for storage
        if ($request->has('meta_keywords') && is_array($request->meta_keywords)) {
            // Filter out empty keywords
            $keywords = array_filter($request->meta_keywords, function($keyword) {
                return !empty(trim($keyword));
            });
            $data['meta_keywords'] = json_encode(array_values($keywords));
        }

        try {
            return \DB::transaction(function() use ($request, $data) {
                // Handle main image upload
                if ($request->hasFile('image')) {
                    $data['image'] = \App\Services\ImageService::compressAndSave(
                        file: $request->file('image'),
                        directory: 'uploads/products',
                        cropSquare: true
                    );
                }

                // Handle size chart image upload
                if ($request->hasFile('size_chart')) {
                    $sizeChartName = time().'_'.uniqid().'_sizechart.'.$request->file('size_chart')->getClientOriginalExtension();
                    $data['size_chart'] = \App\Services\ImageService::compressAndSave(
                        $request->file('size_chart'),
                        'uploads/products',
                        $sizeChartName
                    );
                }

                $product = Product::create($data);

                // Handle gallery images upload
                if ($request->hasFile('gallery')) {
                    foreach ($request->file('gallery') as $galleryImage) {
                        $galleryPath = \App\Services\ImageService::compressAndSave(
                            file: $galleryImage,
                            directory: 'uploads/products/gallery',
                            cropSquare: true
                        );
                        $product->galleries()->create([
                            'image' => $galleryPath
                        ]);
                    }
                }

                // Handle product attributes (specifications)
                if ($request->has('attributes')) {
                    $attributesData = $request->get('attributes');
                    foreach ($attributesData as $attributeData) {
                        if (!empty($attributeData['attribute_id']) && !empty($attributeData['value']) && trim($attributeData['value']) !== '') {
                            $product->productAttributes()->attach($attributeData['attribute_id'], [
                                'value' => trim($attributeData['value'])
                            ]);
                        }
                    }
                }

                // Clear product cache after creating new product
                $this->clearProductCache($product->id);

                return redirect()->route('product.list')->with('success', 'Product created successfully!');
            });
        } catch (\Exception $e) {
            \Log::error('Product creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create product. Please try again or contact support.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!auth()->user()->hasPermissionTo('view products')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::with([
            'category', 
            'galleries',
            'branchStock.branch',
            'warehouseStock.warehouse',
            'saleItems.pos.invoice',
            'variations.stocks.branch',
            'variations.stocks.warehouse'
        ])->findOrFail($id);
        
        $restrictedBranchId = $this->getRestrictedBranchId();

        // Calculate units sold from POS items (respect branch isolation)
        $unitsSoldQuery = $product->saleItems();
        if ($restrictedBranchId) {
            $unitsSoldQuery->whereHas('pos', function($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId);
            });
        }
        $unitsSold = $unitsSoldQuery->sum('quantity');
        
        // Calculate total stock - filtered by branch if restricted
        $totalStock = 0;
        $branchStocksData = [];
        $warehouseStocksData = [];
        
        if ($product->has_variations && $product->variations && $product->variations->isNotEmpty()) {
            // Product has variations - calculate from variation stocks
            foreach ($product->variations as $variation) {
                if ($variation->stocks && $variation->stocks->isNotEmpty()) {
                    foreach ($variation->stocks as $stock) {
                        // Branch Isolation Filter
                        if ($restrictedBranchId && $stock->branch_id != $restrictedBranchId) continue;

                        $totalStock += $stock->quantity;
                        
                        if ($stock->branch_id && $stock->branch) {
                            $branchName = $stock->branch->name ?? 'Unknown Branch';
                            if (!isset($branchStocksData[$branchName])) {
                                $branchStocksData[$branchName] = [
                                    'branch_name' => $branchName,
                                    'quantity' => 0,
                                    'updated_at' => $stock->last_updated_at ?? $stock->updated_at
                                ];
                            }
                            $branchStocksData[$branchName]['quantity'] += $stock->quantity;
                        }
                        
                        if ($stock->warehouse_id && $stock->warehouse) {
                            $warehouseName = $stock->warehouse->name ?? 'Unknown Warehouse';
                            if (!isset($warehouseStocksData[$warehouseName])) {
                                $warehouseStocksData[$warehouseName] = [
                                    'warehouse_name' => $warehouseName,
                                    'quantity' => 0,
                                    'updated_at' => $stock->last_updated_at ?? $stock->updated_at
                                ];
                            }
                            $warehouseStocksData[$warehouseName]['quantity'] += $stock->quantity;
                        }
                    }
                }
            }
            
            $branchStocks = collect(array_values($branchStocksData));
            $warehouseStocks = collect(array_values($warehouseStocksData));
        } else {
            // Product doesn't have variations - use direct product stocks
            $branchStockQuery = $product->branchStock();
            if ($restrictedBranchId) {
                $branchStockQuery->where('branch_id', $restrictedBranchId);
            }
            
            $branchStocks = $branchStockQuery->get()->map(function($stock) {
                return [
                    'branch_name' => $stock->branch->name ?? 'Unknown Branch',
                    'quantity' => $stock->quantity,
                    'updated_at' => $stock->last_updated_at ?? $stock->updated_at
                ];
            });
            
            $warehouseStockQuery = $product->warehouseStock();
            if ($restrictedBranchId) {
                $warehouseStockQuery->whereRaw('1=0'); // Restricted branches usually don't see warehouse stock
            }
            
            $warehouseStocks = $warehouseStockQuery->get()->map(function($stock) {
                return [
                    'warehouse_name' => $stock->warehouse->name ?? 'Unknown Warehouse',
                    'quantity' => $stock->quantity,
                    'updated_at' => $stock->last_updated_at ?? $stock->updated_at
                ];
            });
            
            $totalStock = $branchStocks->sum('quantity') + $warehouseStocks->sum('quantity');
        }
        
        // Get recent activity (recent sales - respect branch isolation)
        $recentActivityQuery = $product->saleItems()->with(['pos.invoice'])->orderBy('created_at', 'desc');
        if ($restrictedBranchId) {
            $recentActivityQuery->whereHas('pos', function($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId);
            });
        }
        
        $recentActivity = $recentActivityQuery->limit(5)->get()
            ->map(function($item) {
                return [
                    'type' => 'sale',
                    'description' => 'Order fulfilled',
                    'details' => $item->quantity . ' units sold',
                    'time' => $item->created_at->diffForHumans(),
                    'date' => $item->created_at
                ];
            });
        
        return view('erp.products.show', compact(
            'product', 
            'unitsSold',
            'branchStocks', 
            'warehouseStocks', 
            'recentActivity',
            'totalStock'
        ));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::with('category.parent', 'galleries', 'productAttributes')->findOrFail($id);
        $attributes = \App\Models\Attribute::where('status', 'active')->orderBy('name')->get();
        $brands = \App\Models\Brand::where('status', 'active')->get();
        $seasons = \App\Models\Season::where('status', 'active')->get();
        $genders = \App\Models\Gender::all();
        $units = \App\Models\Unit::all();
        $categories = \App\Models\ProductServiceCategory::where('status', 'active')->get();
        return view('erp.products.edit', compact('product', 'attributes', 'brands', 'seasons', 'genders', 'units', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        // Debug: Log the request data
        \Log::info('Product Update Request Data:', $request->all());
        
        $product = Product::with('galleries')->findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'unique:products,slug,' . $product->id,
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'style_number' => 'nullable|string|max:100',
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'category_id' => 'required|exists:product_service_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'season_id' => 'nullable|exists:seasons,id',
            'gender_id' => 'nullable|exists:genders,id',
            'unit_id' => 'nullable|exists:units,id',
            'price' => 'required|numeric',
            'wholesale_price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'cost' => 'required|numeric',
            'alert_quantity' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'size_chart' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'gallery' => 'nullable',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'nullable|in:active,inactive',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'nullable|string|max:255',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_id' => 'nullable|exists:attributes,id',
            'attributes.*.value' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'slug', 'sku', 'style_number', 'short_desc', 'description', 'features', 'category_id', 'brand_id', 'season_id', 'gender_id', 'unit_id', 'price', 'wholesale_price', 'discount', 'cost', 'alert_quantity', 'status', 'meta_title', 'meta_description']);
        
        // Ensure nullable text fields are not null to avoid DB integrity constraint violations
        $data['features'] = $data['features'] ?? '';
        $data['short_desc'] = $data['short_desc'] ?? '';
        $data['description'] = $data['description'] ?? '';
        $data['type'] = 'product'; // Always set type to product
        $data['show_in_ecommerce'] = $request->boolean('show_in_ecommerce');
        
        // Handle meta_keywords array - convert to JSON string for storage
        if ($request->has('meta_keywords') && is_array($request->meta_keywords)) {
            // Filter out empty keywords
            $keywords = array_filter($request->meta_keywords, function($keyword) {
                return !empty(trim($keyword));
            });
            $data['meta_keywords'] = json_encode(array_values($keywords));
        }

        try {
            return \DB::transaction(function() use ($request, $product, $data) {
                // Handle main image upload
                if ($request->hasFile('image')) {
                    if ($product->image && file_exists(public_path($product->image))) {
                        @unlink(public_path($product->image));
                    }
                    $data['image'] = \App\Services\ImageService::compressAndSave(
                        file: $request->file('image'),
                        directory: 'uploads/products',
                        cropSquare: true
                    );
                }

                // Handle size chart image deletion/upload
                if ($request->has('delete_size_chart') && $request->delete_size_chart == '1') {
                    if ($product->size_chart && file_exists(public_path($product->size_chart))) {
                        @unlink(public_path($product->size_chart));
                    }
                    $data['size_chart'] = null;
                }

                if ($request->hasFile('size_chart')) {
                    if ($product->size_chart && file_exists(public_path($product->size_chart))) {
                        @unlink(public_path($product->size_chart));
                    }
                    $sizeChartName = time().'_'.uniqid().'_sizechart.'.$request->file('size_chart')->getClientOriginalExtension();
                    $data['size_chart'] = \App\Services\ImageService::compressAndSave(
                        $request->file('size_chart'),
                        'uploads/products',
                        $sizeChartName
                    );
                }

                $product->update($data);

                // Handle gallery images upload
                if ($request->hasFile('gallery')) {
                    foreach ($request->file('gallery') as $galleryImage) {
                        $galleryPath = \App\Services\ImageService::compressAndSave(
                            file: $galleryImage,
                            directory: 'uploads/products/gallery',
                            cropSquare: true
                        );
                        $product->galleries()->create([
                            'image' => $galleryPath
                        ]);
                    }
                }

                // Handle product attributes (specifications) - sync
                $product->productAttributes()->detach();
                if ($request->has('attributes')) {
                    $attributesData = $request->get('attributes');
                    foreach ($attributesData as $attributeData) {
                        if (!empty($attributeData['attribute_id']) && !empty($attributeData['value']) && trim($attributeData['value']) !== '') {
                            $product->productAttributes()->attach($attributeData['attribute_id'], [
                                'value' => trim($attributeData['value'])
                            ]);
                        }
                    }
                }

                // Clear product cache after updating product
                $this->clearProductCache($product->id);

                return redirect()->route('product.list')->with('success', 'Product updated successfully!');
            });
        } catch (\Exception $e) {
            \Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update product. Please try again or contact support.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::findOrFail($id);
        $productId = $product->id;
        $product->delete();
        
        // Clear product cache after deleting product
        $this->clearProductCache($productId);
        
        return redirect()->route('product.list')->with('success', 'Product deleted successfully!');
    }

    public function searchCategory(Request $request)
    {
        $q = $request->q;
        
        // Fetch categories with status active (optional, depends on your preference)
        $query = ProductServiceCategory::query()->where('status', 'active');
        
        // If searching, we fetch all to build their paths and then filter
        // or we can do multiple OR likes if the hierarchy is shallow.
        // For best UX with parent > child, we'll fetch a larger set and filter by path.
        $categories = $query->get();
        
        if ($q) {
            $q = strtolower($q);
            $categories = $categories->filter(function($category) use ($q) {
                return str_contains(strtolower($category->full_path_name), $q) || 
                       str_contains(strtolower($category->description), $q);
            });
        }
        
        $formatted = $categories->sortBy('full_path_name')->take(30)->map(function($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'display_name' => $category->full_path_name,
                'parent_id' => $category->parent_id,
            ];
        })->values();
        
        return response()->json($formatted);
    }

    /**
     * Remove a gallery image from a product.
     */

     public function addGalleryImage(Request $request)
     {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);
        $product = Product::findOrFail($request->product_id);
        $galleryPath = \App\Services\ImageService::compressAndSave(
            file: $request->file('image'),
            directory: 'uploads/products/gallery',
            cropSquare: true
        );
        $product->galleries()->create([
            'image' => $galleryPath
        ]);
        return response()->json(['success' => true, 'message' => 'Gallery image added successfully!']);
     }
    public function deleteGalleryImage($id)
    {
        if (!auth()->user()->hasPermissionTo('manage products')) {
            abort(403, 'Unauthorized action.');
        }
        $gallery = \App\Models\ProductGallery::findOrFail($id);
        // Delete image file if exists
        if ($gallery->image && file_exists(public_path($gallery->image))) {
            @unlink(public_path($gallery->image));
        }
        $gallery->delete();
        return redirect()->back()->with('success', 'Gallery image removed successfully!');
    }

    public function searchByStyle(Request $request)
    {
        $q = $request->q;
        $query = Product::with(['category', 'brand', 'season', 'gender']);
        
        if ($q) {
            $query->where('style_number', 'like', "%$q%")
                  ->orWhere('name', 'like', "%$q%");
        }
        
        $products = $query->orderBy('style_number')->limit(20)->get();
        return response()->json($products);
    }

    public function getVariationsWithStock(Request $request, $productId)
    {
        $product = Product::with([
            'branchStock',
            'warehouseStock',
            'variations.stocks' => function($query) {
                $query->select('variation_id', 'branch_id', 'warehouse_id', 'quantity', 'id');
            },
            'variations.combinations.attribute',
            'variations.combinations.attributeValue'
        ])->findOrFail($productId);
        
        $locationType = $request->query('location_type');
        $locationId = $request->query('location_id');

        if (!$product->has_variations) {
            if ($locationType && $locationId) {
                // Check both branch_id (for Branch-Warehouses) and warehouse_id (for standalone Warehouses)
                $bsQty = $product->branchStock->where('branch_id', $locationId)->sum('quantity');
                $wsQty = $product->warehouseStock->where('warehouse_id', $locationId)->sum('quantity');
                $totalStock = $bsQty + $wsQty;
            } else {
                $totalStock = $product->branchStock->sum('quantity') + $product->warehouseStock->sum('quantity');
            }

            return response()->json([[
                'id' => null,
                'name' => 'Standard',
                'size' => null,
                'color' => null,
                'price' => (float)$product->effective_price,
                'cost' => (float)$product->cost,
                'wholesale_price' => (float)$product->wholesale_price,
                'stock' => (float)$totalStock
            ]]);
        }
        
        $variations = $product->variations->map(function($variation) use ($locationType, $locationId, $product) {
            // Calculate stock based on location filter if provided, checking both branch_id and warehouse_id
            if ($locationType && $locationId) {
                $matchedStocks = $variation->stocks->filter(function($s) use ($locationId) {
                    return (string)$s->branch_id === (string)$locationId || (string)$s->warehouse_id === (string)$locationId;
                });
                $totalStock = $matchedStocks->sum('quantity');
            } else {
                $totalStock = $variation->stocks->sum('quantity');
            }
            
            // Extract size and color from combinations
            $size = null;
            $color = null;
            
            foreach ($variation->combinations as $combination) {
                $attributeName = strtolower($combination->attribute->name ?? '');
                $attributeValue = $combination->attributeValue->value ?? '';
                
                if (in_array($attributeName, ['size', 'sizes'])) {
                    $size = $attributeValue;
                } elseif (in_array($attributeName, ['color', 'colour', 'colors'])) {
                    $color = $attributeValue;
                }
            }
            
            return [
                'id' => $variation->id,
                'name' => $variation->name,
                'image' => $variation->image,
                'size' => $size,
                'color' => $color,
                'price' => (float)$variation->effective_price,
                'cost' => (float)($variation->cost > 0 ? $variation->cost : $product->cost),
                'wholesale_price' => (float)(($variation->wholesale_price ?? 0) > 0 ? $variation->wholesale_price : $product->wholesale_price),
                'stock' => $totalStock
            ];
        });
        
        return response()->json($variations);
    }

    public function getProductVariations(Request $request, $productId)
    {
        $branchId = $request->input('branch_id');
        $product = Product::with(['variations.combinations.attribute', 'variations.combinations.attributeValue'])
            ->findOrFail($productId);

        if (!$product->has_variations) {
            return response()->json([]);
        }

        $variations = $product->variations()->where('status', 'active')->get()->map(function($variation) use ($product, $branchId) {
            $attributes = $variation->combinations->map(function($combination) {
                return $combination->attributeValue->value ?? '';
            })->filter()->implode(' - ');

            // Price logic: If variation has price, use it; otherwise use product price
            // Then apply discount if exists (variation discount or product discount)
            $basePrice = ($variation->price && $variation->price > 0) ? (float) $variation->price : (float) $product->price;
            $effectivePrice = (float) $variation->effective_price;
            $hasDiscount = $effectivePrice < $basePrice;

            // Get branch-specific stock if branch_id is provided
            if ($branchId) {
                $branchStock = \App\Models\ProductVariationStock::where('variation_id', $variation->id)
                    ->where('branch_id', $branchId)
                    ->whereNull('warehouse_id')
                    ->first();
                $stock = $branchStock ? ($branchStock->available_quantity ?? ($branchStock->quantity - ($branchStock->reserved_quantity ?? 0))) : 0;
            } else {
                $stock = (float) $variation->total_stock;
            }
            
            return [
                'id' => $variation->id,
                'name' => $variation->name ?: ($product->name . ($attributes ? ' - ' . $attributes : '')),
                'display_name' => $variation->name ?: ($product->name . ($attributes ? ' - ' . $attributes : '')),
                'sku' => $variation->sku,
                'price' => $effectivePrice,
                'cost' => $variation->cost ?: $product->cost,
                'base_price' => $basePrice,
                'discount' => $hasDiscount ? ($basePrice - $effectivePrice) : 0,
                'has_discount' => $hasDiscount,
                'stock' => $stock
            ];
        });
        
        return response()->json($variations);
    }

    /**
     * Find product by SKU/barcode for POS scanning
     */
    public function findProductByBarcode(Request $request, $branchId)
    {
        $barcode = $request->input('barcode') ?? $request->input('sku');
        
        if (!$barcode) {
            return response()->json(['success' => false, 'message' => 'Barcode/SKU is required'], 400);
        }

        // First, try to find by product SKU, style number, or exact name
        $product = Product::with(['category', 'branchStock' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }, 'variations.stocks' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId)->whereNull('warehouse_id');
        }])
        ->where(function($q) use ($barcode) {
            $q->where('sku', $barcode)
              ->orWhere('style_number', $barcode)
              ->orWhere('name', $barcode);
        })
        ->where('status', 'active')
        ->whereIn('type', ['product', 'combo'])
        ->where(function($q) use ($branchId) {
            $q->whereHas('branchStock', function($subQ) use ($branchId) {
                $subQ->where('branch_id', $branchId);
            })
            ->orWhereHas('variations.stocks', function($subQ) use ($branchId) {
                $subQ->where('branch_id', $branchId)->whereNull('warehouse_id');
            })
            ->orWhere('type', 'combo');
        })
        ->first();

        // If product found, return it with stock info
        if ($product) {
            $productData = $this->transformProductForPOS($product, $branchId);
            return response()->json(['success' => true, 'product' => $productData, 'type' => 'product']);
        }

        // If not found, try to find by variation SKU
        $variation = \App\Models\ProductVariation::with(['product' => function($q) use ($branchId) {
            $q->where('status', 'active')
              ->where('type', 'product')
              ->with(['category', 'branchStock' => function($sq) use ($branchId) {
                $sq->where('branch_id', $branchId);
            }]);
        }, 'stocks' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId)->whereNull('warehouse_id');
        }])
        ->where('sku', $barcode)
        ->where('status', 'active')
        ->whereHas('product', function($q) {
            $q->where('status', 'active')->where('type', 'product');
        })
        ->whereHas('stocks', function($q) use ($branchId) {
            $q->where('branch_id', $branchId)->whereNull('warehouse_id');
        })
        ->first();

        if ($variation && $variation->product) {
            $product = $variation->product;
            $productData = $this->transformProductForPOS($product, $branchId);
            
            // Add variation info
            $variationStock = 0;
            foreach ($variation->stocks as $stock) {
                if ($stock->branch_id == $branchId && !$stock->warehouse_id) {
                    $variationStock += $stock->available_quantity ?? ($stock->quantity - ($stock->reserved_quantity ?? 0));
                }
            }
            
            $productData['selected_variation'] = [
                'id' => $variation->id,
                'sku' => $variation->sku,
                'name' => $variation->name,
                'price' => ($variation->price && $variation->price > 0) ? $variation->price : null,
                'stock' => $variationStock,
            ];
            
            return response()->json(['success' => true, 'product' => $productData, 'type' => 'variation', 'variation_id' => $variation->id]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found with this barcode/SKU'], 404);
    }

    /**
     * Transform product data for POS display
     */
    private function transformProductForPOS($product, $branchId)
    {
        if ($product->type === 'combo') {
            $comboStock = PHP_INT_MAX;
            if (!$product->relationLoaded('comboItems')) {
                $product->load('comboItems');
            }
            foreach ($product->comboItems as $comboItem) {
                $itemStock = 0;
                if ($comboItem->variation_id) {
                    $vStock = \App\Models\ProductVariationStock::where('variation_id', $comboItem->variation_id)
                        ->where('branch_id', $branchId)->whereNull('warehouse_id')->first();
                    $itemStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                } else {
                    $pStock = \App\Models\BranchProductStock::where('product_id', $comboItem->product_id)
                        ->where('branch_id', $branchId)->first();
                    $itemStock = $pStock ? $pStock->quantity : 0;
                }
                $possibleCombos = floor($itemStock / max(1, $comboItem->quantity));
                if ($possibleCombos < $comboStock) {
                    $comboStock = $possibleCombos;
                }
            }
            if ($comboStock === PHP_INT_MAX) $comboStock = 0;
            
            $branch = \App\Models\Branch::find($branchId);
            $branchName = $branch ? $branch->name : 'Unknown Branch';
            
            return [
                'id' => $product->id,
                'name' => $product->name . ' [COMBO]',
                'sku' => $product->sku,
                'style_number' => $product->style_number,
                'type' => $product->type,
                'price' => $product->price,
                'cost' => $product->cost,
                'discount' => $product->discount,
                'status' => $product->status,
                'image' => $product->image,
                'description' => $product->description,
                'has_variations' => false,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'branch_stock' => [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'quantity' => $comboStock,
                    'last_updated_at' => null
                ],
                'total_stock' => $comboStock
            ];
        }

        if ($product->has_variations) {
            // Load variations if not already loaded
            if (!$product->relationLoaded('variations')) {
                $product->load(['variations.stocks' => function($q) use ($branchId) {
                    $q->where('branch_id', $branchId)->whereNull('warehouse_id');
                }]);
            }
            
            // Sum all variation stocks for this branch
            $totalVariationStock = 0;
            $lastUpdatedAt = null;
            foreach ($product->variations as $variation) {
                if ($variation->relationLoaded('stocks')) {
                    foreach ($variation->stocks as $vStock) {
                        if ($vStock->branch_id == $branchId && !$vStock->warehouse_id) {
                            $totalVariationStock += $vStock->quantity;
                            if (!$lastUpdatedAt || ($vStock->last_updated_at && $vStock->last_updated_at > $lastUpdatedAt)) {
                                $lastUpdatedAt = $vStock->last_updated_at;
                            }
                        }
                    }
                }
            }
            
            $branch = \App\Models\Branch::find($branchId);
            $branchName = $branch ? $branch->name : 'Unknown Branch';
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'style_number' => $product->style_number,
                'type' => $product->type,
                'price' => $product->price,
                'cost' => $product->cost,
                'discount' => $product->discount,
                'status' => $product->status,
                'image' => $product->image,
                'description' => $product->description,
                'has_variations' => $product->has_variations,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'branch_stock' => [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'quantity' => $totalVariationStock,
                    'last_updated_at' => $lastUpdatedAt
                ],
                'total_stock' => $totalVariationStock
            ];
        } else {
            $branchStock = $product->branchStock->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'style_number' => $product->style_number,
                'type' => $product->type,
                'price' => $product->price,
                'cost' => $product->cost,
                'discount' => $product->discount,
                'status' => $product->status,
                'image' => $product->image,
                'description' => $product->description,
                'has_variations' => $product->has_variations,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'branch_stock' => $branchStock ? [
                    'branch_id' => $branchStock->branch_id,
                    'branch_name' => $branchStock->branch->name ?? 'Unknown Branch',
                    'quantity' => $branchStock->quantity,
                    'last_updated_at' => $branchStock->last_updated_at
                ] : [
                    'branch_id' => $branchId,
                    'branch_name' => 'Unknown Branch',
                    'quantity' => 0,
                    'last_updated_at' => null
                ],
                'total_stock' => $product->branchStock->sum('quantity')
            ];
        }
    }

    public function getPrice($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        
        $basePrice = (float) $product->price;
        $effectivePrice = (float) $product->effective_price;
        $hasDiscount = $effectivePrice < $basePrice;
        
        return response()->json([
            'price' => $effectivePrice,
            'cost' => $product->cost,
            'base_price' => $basePrice,
            'discount' => $hasDiscount ? ($basePrice - $effectivePrice) : 0,
            'has_discount' => $hasDiscount,
            'stock' => (float) $product->total_variation_stock
        ]);
    }

    public function getSalePrice($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        // For sales/returns, use the selling price (consider discount if applicable)
        // Use discount price if available, otherwise use regular price
        $price = ($product->discount && $product->discount > 0 && $product->discount < $product->price) 
            ? $product->discount 
            : $product->price;
        $stock = $product->warehouseStock()->sum('quantity') + $product->branchStock()->sum('quantity');
        return response()->json(['price' => $price, 'stock' => $stock]);
    }

    

    public function searchProductWithFilters(Request $request, $branchId)
    {
        // For products with variations, we need to check variation stocks
        // For products without variations, we check branch stocks
        $query = Product::with(['category', 'branchStock' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }, 'variations.stocks' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId)->whereNull('warehouse_id');
        }])
        ->where('status', 'active')
        ->whereIn('type', ['product', 'combo'])
        ->where(function($q) use ($branchId) {
            $q->whereHas('branchStock', function($sq) use ($branchId) {
                $sq->where('branch_id', $branchId)->where('quantity', '>', 0);
            })
            ->orWhereHas('variations.stocks', function($sq) use ($branchId) {
                $sq->where('branch_id', $branchId)->whereNull('warehouse_id')->where('quantity', '>', 0);
            })
            ->orWhere('type', 'combo');
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
        $products = $query->paginate(24); 

        // Get branch name once for all products in this request
        $branch = \App\Models\Branch::find($branchId);
        $branchName = $branch ? $branch->name : 'Unknown Branch';

        $products->getCollection()->transform(function($product) use ($branchId, $branchName) {
            if ($product->type === 'combo') {
                $comboStock = PHP_INT_MAX;
                if (!$product->relationLoaded('comboItems')) {
                    $product->load('comboItems');
                }
                foreach ($product->comboItems as $comboItem) {
                    $itemStock = 0;
                    if ($comboItem->variation_id) {
                        $vStock = \App\Models\ProductVariationStock::where('variation_id', $comboItem->variation_id)
                            ->where('branch_id', $branchId)->whereNull('warehouse_id')->first();
                        $itemStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                    } else {
                        $pStock = \App\Models\BranchProductStock::where('product_id', $comboItem->product_id)
                            ->where('branch_id', $branchId)->first();
                        $itemStock = $pStock ? $pStock->quantity : 0;
                    }
                    $possibleCombos = floor($itemStock / max(1, $comboItem->quantity));
                    if ($possibleCombos < $comboStock) {
                        $comboStock = $possibleCombos;
                    }
                }
                if ($comboStock === PHP_INT_MAX) $comboStock = 0;
                
                return [
                    'id' => $product->id,
                    'name' => $product->name . ' [COMBO]',
                    'sku' => $product->sku,
                    'type' => $product->type,
                    'price' => $product->price,
                    'wholesale_price' => $product->wholesale_price,
                    'cost' => $product->cost,
                    'discount' => $product->discount,
                    'style_number' => $product->style_number,
                    'status' => $product->status,
                    'image' => $product->image,
                    'description' => $product->description,
                    'has_variations' => false,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'branch_stock' => [
                        'branch_id' => $branchId,
                        'branch_name' => $branchName,
                        'quantity' => $comboStock,
                        'last_updated_at' => null
                    ],
                    'total_stock' => $comboStock
                ];
            }

            // For products with variations, calculate total stock from variation stocks
            if ($product->has_variations) {
                // Variations and stocks are already eager loaded via 'with' on the query
                // No need to call $product->load() here as it causes N+1 issues
                
                // Sum all variation stocks for this branch
                $totalVariationStock = 0;
                $lastUpdatedAt = null;
                foreach ($product->variations as $variation) {
                    foreach ($variation->stocks as $vStock) {
                        if ($vStock->branch_id == $branchId && !$vStock->warehouse_id) {
                            $totalVariationStock += $vStock->quantity;
                            if (!$lastUpdatedAt || ($vStock->last_updated_at && $vStock->last_updated_at > $lastUpdatedAt)) {
                                $lastUpdatedAt = $vStock->last_updated_at;
                            }
                        }
                    }
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'type' => $product->type,
                    'price' => $product->price,
                    'wholesale_price' => $product->wholesale_price,
                    'cost' => $product->cost,
                    'discount' => $product->discount,
                    'style_number' => $product->style_number,
                    'status' => $product->status,
                    'image' => $product->image,
                    'description' => $product->description,
                    'has_variations' => $product->has_variations,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'branch_stock' => [
                        'branch_id' => $branchId,
                        'branch_name' => $branchName,
                        'quantity' => $totalVariationStock,
                        'last_updated_at' => $lastUpdatedAt
                    ],
                    'total_stock' => $totalVariationStock,
                    'variations' => $product->variations->map(function($v) use ($branchId, $product) {
                         // Find stock for this variation in this branch
                         $stock = $v->stocks->where('branch_id', $branchId)->whereNull('warehouse_id')->first();
                         return [
                             'id' => $v->id,
                             'name' => $v->name, // e.g. "Color: Red, Size: XL"
                             'price' => $v->price ?? null, // Override price if set
                             'wholesale_price' => $product->wholesale_price, // Fallback to product wholesale price
                             'sku' => $v->sku ?? (($product->style_number ?? $product->sku) . '-' . $v->id),
                             'stock' => $stock ? $stock->quantity : 0
                         ];
                    })->values()
                ];
            } else {
                // For products without variations, use branch stock
                $branchStock = $product->branchStock->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'style_number' => $product->style_number,
                    'type' => $product->type,
                    'price' => $product->price,
                    'wholesale_price' => $product->wholesale_price,
                    'cost' => $product->cost,
                    'discount' => $product->discount,
                    'status' => $product->status,
                    'image' => $product->image,
                    'description' => $product->description,
                    'has_variations' => $product->has_variations,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'branch_stock' => [
                        'branch_id' => $branchId,
                        'branch_name' => $branchName,
                        'quantity' => $branchStock ? $branchStock->quantity : 0,
                        'last_updated_at' => $branchStock ? ($branchStock->last_updated_at ?? $branchStock->updated_at) : null
                    ],
                    'total_stock' => $branchStock ? $branchStock->quantity : 0
                ];
            }
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

    /**
     * Search products by Style Number (and SKU/Name)
     */
    public function searchStyleNumber(Request $request)
    {
        $q = $request->q;
        $branchId = $request->branch_id;
        
        $query = Product::where('status', 'active');
        if ($request->exclude_combo) {
            $query->where('type', 'product');
        } else {
            $query->whereIn('type', ['product', 'combo']);
        }
        
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('style_number', 'like', "%$q%")
                    ->orWhere('sku', 'like', "%$q%")
                    ->orWhere('name', 'like', "%$q%");
            });
        }
        
        $warehouseId = $request->warehouse_id;

        $products = $query->with(['variations.stocks' => function($s) use ($branchId, $warehouseId) {
            if ($branchId) { 
                $s->where('branch_id', $branchId)->whereNull('warehouse_id'); 
            } elseif ($warehouseId) {
                $s->where('warehouse_id', $warehouseId)->whereNull('branch_id');
            }
        }])
        ->with(['branchStock' => function($s) use ($branchId) {
            if ($branchId) { $s->where('branch_id', $branchId); }
        }, 'warehouseStocks' => function($s) use ($warehouseId) {
            if ($warehouseId) { $s->where('warehouse_id', $warehouseId); }
        }, 'comboItems'])
        ->orderBy('id', 'desc')
        ->limit(20)->get();
            
        $results = $products->map(function($product) use ($branchId, $warehouseId) {
            $desc = $product->style_number ?: ($product->sku ?: 'No Style/SKU');
            
            $stock = 0;
            if ($product->type === 'combo') {
                $comboStock = PHP_INT_MAX;
                foreach ($product->comboItems as $comboItem) {
                    $itemStock = 0;
                    if ($comboItem->variation_id) {
                        $vStockQuery = \App\Models\ProductVariationStock::where('variation_id', $comboItem->variation_id);
                        if ($branchId) {
                            $vStockQuery->where('branch_id', $branchId)->whereNull('warehouse_id');
                        } elseif ($warehouseId) {
                            $vStockQuery->where('warehouse_id', $warehouseId)->whereNull('branch_id');
                        }
                        $vStock = $vStockQuery->first();
                        $itemStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                    } else {
                        if ($branchId) {
                            $pStock = \App\Models\BranchProductStock::where('product_id', $comboItem->product_id)
                                ->where('branch_id', $branchId)->first();
                            $itemStock = $pStock ? $pStock->quantity : 0;
                        } elseif ($warehouseId) {
                            $pStock = \App\Models\WarehouseProductStock::where('product_id', $comboItem->product_id)
                                ->where('warehouse_id', $warehouseId)->first();
                            $itemStock = $pStock ? $pStock->quantity : 0;
                        } else {
                            $itemStock = \App\Models\BranchProductStock::where('product_id', $comboItem->product_id)->sum('quantity') + 
                                         \App\Models\WarehouseProductStock::where('product_id', $comboItem->product_id)->sum('quantity');
                        }
                    }
                    $possibleCombos = floor($itemStock / max(1, $comboItem->quantity));
                    if ($possibleCombos < $comboStock) {
                        $comboStock = $possibleCombos;
                    }
                }
                $stock = ($comboStock === PHP_INT_MAX) ? 0 : $comboStock;
            } else {
                if ($branchId) {
                    $stock = (float)($product->branchStock->sum('quantity') ?? 0);
                } elseif ($warehouseId) {
                    $stock = (float)($product->warehouseStocks->sum('quantity') ?? 0);
                } else {
                    $stock = (float)($product->branchStock->sum('quantity') ?? 0) + (float)($product->warehouseStocks->sum('quantity') ?? 0);
                }
            }

            return [
                'id' => $product->id, 
                'name' => $product->name,
                'text' => $product->name . " [" . $desc . "]" . ($product->has_variations ? "" : " (Stock: $stock)"),
                'stock' => $stock,
                'price' => (float)$product->price,
                'wholesale_price' => (float)$product->wholesale_price,
                'discount' => (float)$product->discount,
                'has_variations' => (bool)$product->has_variations,
                'sku' => $product->sku,
                'style_number' => $product->style_number,
                'variations' => $product->variations->map(function($v) {
                    $vStock = (float)($v->stocks->sum('quantity') ?? 0);
                    return [
                        'id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sku,
                        'stock' => $vStock,
                        'price' => (float)($v->price ?: 0),
                        'wholesale_price' => (float)($v->wholesale_price ?: 0)
                    ];
                })
            ];
        });
        
        return response()->json(['results' => $results]);
    }

    /**
     * Find product and its variations by Identity (ID, Style Number, or SKU)
     */
    public function findProductByStyle($identity)
    {
        $query = Product::with(['category', 'brand', 'season', 'gender', 'variations.combinations.attributeValue.attribute']);
        
        // Search by ID first (most reliable), then fallback to style_number or SKU
        $query->where(function($q) use ($identity) {
            $q->where('id', $identity)
              ->orWhere('style_number', $identity)
              ->orWhere('sku', $identity);
        });

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Product not found with this identity'], 404);
        }

        $results = $products->map(function($product) {
            // Determine if it has variations more reliably
            $hasActuallyVariations = $product->has_variations || $product->variations->isNotEmpty();
            
            // Get unique sizes and colors from variations
            $sizes = [];
            $colors = [];
            
            if ($hasActuallyVariations) {
                foreach ($product->variations as $variation) {
                    foreach ($variation->combinations as $combination) {
                        $val = $combination->attributeValue;
                        if (!$val) continue;
                        
                        $attr = $val->attribute;
                        $attrName = strtolower($attr->name ?? '');
                        
                        if ($attr && ($attr->is_color || $attrName == 'color' || $attrName == 'colour')) {
                            $colors[$val->id] = $val->value;
                        } elseif ($attr && ($attrName == 'size')) {
                            $sizes[$val->id] = $val->value;
                        } else {
                            // Catch-all for other attributes, categorized as 'sizes' for UI simplicity
                            $sizes[$val->id] = $val->value;
                        }
                    }
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image ? asset($product->image) : asset('assets/images/product-placeholder.png'),
                'category' => $product->category->name ?? '-',
                'brand' => $product->brand->name ?? '-',
                'season' => $product->season->name ?? '-',
                'gender' => $product->gender->name ?? '-',
                'style_number' => $product->style_number,
                'sku' => $product->sku,
                'price' => $product->price,
                'wholesale_price' => $product->wholesale_price,
                'cost' => $product->cost,
                'has_variations' => $hasActuallyVariations,
                'sizes' => array_map(function($id, $name) { return ['id' => $id, 'name' => $name]; }, array_keys($sizes), $sizes),
                'colors' => array_map(function($id, $name) { return ['id' => $id, 'name' => $name]; }, array_keys($colors), $colors),
                'variations' => $product->variations->map(function($v) use ($product) {
                    return [
                        'id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sku ?? (($product->style_number ?? $product->sku) . '-' . $v->id),
                        'price' => $v->price ?? $product->price,
                        'wholesale_price' => $v->wholesale_price ?? $product->wholesale_price,
                        'cost' => $v->cost ?? $product->cost,
                        'attributes' => $v->combinations->map(function($c) {
                            return [
                                'attribute_id' => $c->attribute_id,
                                'attribute_name' => strtolower($c->attributeValue->attribute->name ?? ''),
                                'value_id' => $c->attribute_value_id,
                                'value' => $c->attributeValue->value ?? ''
                            ];
                        })
                    ];
                })
            ];
        });

        return response()->json(['success' => true, 'products' => $results]);
    }

    /**
     * Clear product-related cache
     */
    private function clearProductCache($productId = null)
    {
        try {
            \App\Services\CacheService::clearProductCaches($productId);
        } catch (\Exception $e) {
            \Log::warning('Failed to clear product cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear cache by pattern - Use CacheService instead of flushing all cache
     */
    private function clearCachePattern($pattern)
    {
        try {
            // Use CacheService which handles database cache properly
            // This prevents clearing ALL cache which causes performance issues
            \App\Services\CacheService::clearProductCaches();
            \Log::info("Cleared product caches using CacheService for pattern: {$pattern}");
        } catch (\Exception $e) {
            // If CacheService fails, try to clear specific known cache keys
            \Log::warning("Could not clear cache pattern {$pattern}: " . $e->getMessage());
            // Don't use Cache::flush() - it clears ALL cache and causes performance issues
            // Instead, clear only known product-related cache keys
            try {
                Cache::forget('max_product_price');
                Cache::forget('home_page_data');
                Cache::forget('products_list_*');
                Cache::forget('product_details_*');
                Cache::forget('new_arrivals_products_*');
                Cache::forget('best_deals_products_*');
            } catch (\Exception $clearException) {
                \Log::error("Failed to clear specific cache keys: " . $clearException->getMessage());
            }
        }
    }

}
