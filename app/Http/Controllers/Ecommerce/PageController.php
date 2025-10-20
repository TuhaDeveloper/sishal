<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductServiceCategory;
use App\Models\Vlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use App\Models\AdditionalPage;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = null;
        // Load only active parent categories with their active children for homepage menu
        $categories = ProductServiceCategory::whereNull('parent_id')
            ->where('status', 'active')
            ->with(['children' => function($q) {
                $q->where('status', 'active');
            }])
            ->get();
        // Try loading banners if a model exists; otherwise provide empty array
        $banners = [];
        $vlogBottomBanners = [];
        if (class_exists('App\\Models\\Banner')) {
            $banners = \App\Models\Banner::currentlyActive()
                ->where('position','hero')
                ->orderBy('sort_order', 'asc')
                ->get();
            $vlogBottomBanners = \App\Models\Banner::currentlyActive()
                ->where('position','vlogs_bottom')
                ->orderBy('sort_order','asc')
                ->get();
        }
        $featuredCategories = ProductServiceCategory::whereNull('parent_id')->get();
        $featuredServices = Product::where('type', 'service')
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->take(4)
            ->get();
        $bestDealProducts = Product::where('type','product')
            ->orderByDesc('discount')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
            
        $vlogs = Vlog::where('is_active', 1)
            ->latest()
            ->take(4)
            ->get();
        
        $viewData = compact('featuredCategories', 'featuredServices', 'vlogs', 'pageTitle','categories','banners','bestDealProducts','vlogBottomBanners');
        $response = response()->view('ecommerce.home', $viewData);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }

    public function products(Request $request)
    {
        $categories = ProductServiceCategory::where('status','active')->get();
        $query = Product::query();

        // Get the highest price of all products
        $maxProductPrice = Product::max('price') ?? 0;

        // Category filter
        if ($request->has('categories') && is_array($request->categories) && count($request->categories)) {
            $categoryIds = ProductServiceCategory::whereIn('slug', $request->categories)->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        } elseif ($request->has('category') && $request->category) {
            // Single category filter (from category page links)
            $categoryId = ProductServiceCategory::where('slug', $request->category)->value('id');
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
        }

        // Price range filter
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }


        // Sorting
        switch ($request->sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'featured':
                $query->orderByDesc('discount')->orderByDesc('created_at');
                break;
            case 'lowToHigh':
                $query->orderBy('price');
                break;
            case 'highToLow':
                $query->orderByDesc('price');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $pageTitle = 'Products';
        
        // Check if we want to show categories view
        if ($request->get('view') === 'categories') {
            $pageTitle = 'Categories';
            $viewData = compact('pageTitle', 'categories');
            $response = response()->view('ecommerce.categories', $viewData);
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            return $response;
        }

        $products = $query->where('type','product')->paginate(20)->appends($request->all());
        

        // Add is_wishlisted property for each product
        $userId = Auth::id();
        $wishlistedIds = [];
        if ($userId) {
            $wishlistedIds = Wishlist::where('user_id', $userId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->toArray();
        }
        foreach ($products as $product) {
            $product->is_wishlisted = in_array($product->id, $wishlistedIds);
        }

        // Handle selected categories for both array and single category
        $selectedCategories = [];
        if ($request->has('categories') && is_array($request->categories)) {
            $selectedCategories = $request->categories;
        } elseif ($request->has('category') && $request->category) {
            $selectedCategories = [$request->category];
        }

        $viewData = [
            'products' => $products,
            'categories' => $categories,
            'selectedCategories' => $selectedCategories,
            'selectedSort' => $request->sort ?? '',
            'priceMin' => $request->price_min ?? 0,
            'priceMax' => $request->price_max ?? $maxProductPrice,
            'maxProductPrice' => $maxProductPrice,
            'selectedRatings' => $request->rating ?? []
        ];
        
        $viewData['pageTitle'] = $pageTitle;
        $response = response()->view('ecommerce.products', $viewData);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }

    public function productDetails($slug, Request $request)
    {
        try {
            \Log::info('=== PRODUCT DETAILS REQUEST ===', [
                'slug' => $slug,
                'url' => $request->url(),
                'timestamp' => now(),
                'request_id' => uniqid()
            ]);
            
            // Check all products with similar slugs first
            $allSimilarProducts = Product::where('slug', 'like', '%' . $slug . '%')
                ->orWhere('name', 'like', '%' . $slug . '%')
                ->get(['id', 'name', 'slug']);
            
            \Log::info('Similar products found', [
                'similar_products' => $allSimilarProducts->toArray()
            ]);
            
            $product = Product::with([
                'branchStock',
                'warehouseStock',
                'productAttributes',
                // Eager-load only active variations and their nested relations
                'variations' => function($q) {
                    $q->where('status', 'active')
                      ->with([
                          'combinations.attribute', 
                          'combinations.attributeValue',
                          'stocks.branch',
                          'stocks.warehouse',
                          'galleries',
                      ]);
                },
            ])->where('slug', $slug)->first();
            
            if (!$product) {
                \Log::error('Product not found', [
                    'slug' => $slug,
                    'searched_slug' => $slug,
                    'available_products' => $allSimilarProducts->toArray()
                ]);
                abort(404, 'Product not found');
            }
            
            \Log::info('Product found successfully', [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'searched_slug' => $slug,
                'match_confirmed' => $product->slug === $slug,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'meta_keywords' => $product->meta_keywords
            ]);
            
            
            $pageTitle = $product->name;
            
            // Enhanced related products logic
            $relatedProducts = Product::where('type', 'product')
                ->where('status', 'active')
                ->where('id', '!=', $product->id)
                ->where(function($query) use ($product) {
                    // Same category products
                    $query->where('category_id', $product->category_id)
                          // Or similar price range products (±20%)
                          ->orWhere(function($q) use ($product) {
                              $priceRange = $product->price * 0.2;
                              $q->whereBetween('price', [
                                  $product->price - $priceRange,
                                  $product->price + $priceRange
                              ]);
                          });
                })
                ->orderByRaw("
                    CASE 
                        WHEN category_id = ? THEN 1
                        WHEN ABS(price - ?) <= ? * 0.2 THEN 2
                        ELSE 3
                    END
                ", [$product->category_id, $product->price, $product->price])
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();

            // Add wishlist status to related products
            $userId = Auth::id();
            $wishlistedIds = [];
            if ($userId) {
                $wishlistedIds = \App\Models\Wishlist::where('user_id', $userId)
                    ->whereIn('product_id', $relatedProducts->pluck('id'))
                    ->pluck('product_id')
                    ->toArray();
            }
            foreach ($relatedProducts as $relatedProduct) {
                $relatedProduct->is_wishlisted = in_array($relatedProduct->id, $wishlistedIds);
            }

            \Log::info('Returning view with product data', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'view_data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug
                ]
            ]);

            // Add cache-busting headers to prevent caching issues
            $seoProduct = $product; // ensure header receives the exact product for meta tags
            $response = response()->view('ecommerce.productDetails', compact('product','relatedProducts','pageTitle','seoProduct'));
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
            $response->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->header('ETag', md5($product->id . $product->updated_at));
            return $response;
        } catch (\Exception $e) {
            Log::error('Product details error: ' . $e->getMessage());
            abort(500, 'Error loading product details');
        }
    }

    public function search(Request $request)
    {
        $search = $request->search;
        $products = Product::where(function($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', '%'.$search.'%');
                  });
        })->paginate(20);
        
        
        $pageTitle = 'Search Result';
        
        $viewData = compact('products','search','pageTitle');
        $response = response()->view('ecommerce.searchresult', $viewData);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }

    public function services(Request $request)
    {
        $pageTitle = 'Services';
        $categories = ProductServiceCategory::where('status','active')->get();
        $services = Product::where('type','service')->paginate(12);
        
        return view('ecommerce.service',compact('pageTitle','services','categories'));
    }

    public function serviceDetails($slug, Request $request)
    {
        $service = Product::where('slug',$slug)->first();
        $pageTitle = $service->name;
        
        return view('ecommerce.servicedetails',compact('service','pageTitle'));
    }

    public function about(Request $request)
    {
        $pageTitle = 'About Us';
        
        return view('ecommerce.about',compact('pageTitle'));
    }

    public function contact(Request $request)
    {
        $pageTitle = 'Contact Us';
        
        return view('ecommerce.contact',compact('pageTitle'));
    }

    public function additionalPage($slug, Request $request)
    {
        $page = \App\Models\AdditionalPage::where('slug',$slug)->where('is_active',1)->firstOrFail();
        $pageTitle = $page->title;
        
        return view('ecommerce.additionalPage', compact('page','pageTitle'));
    }

    public function vlogs(Request $request)
    {
        $pageTitle = 'Vlogs';
        $sort = $request->get('sort', 'latest');

        $query = Vlog::where('is_active', 1);
        if ($sort === 'featured') {
            $query->latest();
        } else {
            $query->latest();
        }

        $vlogs = $query->paginate(12)->appends($request->all());

        return view('ecommerce.vlogs', compact('pageTitle','vlogs','sort'));
    }

    public function categories(Request $request)
    {
        $pageTitle = 'Categories';
        $categories = ProductServiceCategory::where('status', 'active')->get();
        
        return view('ecommerce.categories', compact('pageTitle', 'categories'));
    }

    public function bestDeals(Request $request)
    {
        $pageTitle = 'Best Deal';
        $query = Product::where('type', 'product');

        // Prioritize discounted products, then newest
        $query->orderByDesc('discount')->orderByDesc('created_at');

        $products = $query->paginate(20)->appends($request->all());
        

        // Wishlist status mapping for logged-in user
        $userId = Auth::id();
        $wishlistedIds = [];
        if ($userId) {
            $wishlistedIds = Wishlist::where('user_id', $userId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->toArray();
        }
        foreach ($products as $product) {
            $product->is_wishlisted = in_array($product->id, $wishlistedIds);
        }

        $viewData = compact('pageTitle', 'products');
        $response = response()->view('ecommerce.best-deal', $viewData);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }

    public function filterProducts(Request $request)
    {
        $categories = ProductServiceCategory::where('status','active')->get();
        
        // Get max price for price range
        $maxProductPrice = Product::max('price') ?? 1000;
        
        // Build query
        $query = Product::with(['category'])
            ->where('status', 'active')
            ->where('type', 'product');

        // Category filter
        if ($request->filled('categories') && !in_array('all', $request->categories)) {
            $query->whereIn('category_id', function($q) use ($request) {
                $q->select('id')
                  ->from('product_service_categories')
                  ->whereIn('slug', $request->categories);
            });
        }

        // Price range filter
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Rating filter
        if ($request->filled('rating') && is_array($request->rating)) {
            $query->whereHas('reviews', function($q) use ($request) {
                $q->where('is_approved', true);
                $q->where(function($subQuery) use ($request) {
                    foreach ($request->rating as $rating) {
                        $subQuery->orWhere('rating', '>=', $rating);
                    }
                });
            });
        }

        // Sort
        $sort = $request->sort;
        switch ($sort) {
            case 'newest':
                $query->latest();
                break;
            case 'featured':
                $query->where('is_featured', 1)->latest();
                break;
            case 'lowToHigh':
                $query->orderBy('price', 'asc');
                break;
            case 'highToLow':
                $query->orderBy('price', 'desc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(12)->appends($request->all());

        // Add wishlist status
        $wishlistedIds = [];
        if (auth()->check()) {
            $wishlistedIds = \App\Models\Wishlist::where('user_id', auth()->id())
                ->pluck('product_id')->toArray();
        }
        foreach ($products as $product) {
            $product->is_wishlisted = in_array($product->id, $wishlistedIds);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('ecommerce.partials.product-grid', compact('products'))->render(),
                'count' => $products->count(),
                'total' => $products->total()
            ]);
        }

        return view('ecommerce.products', compact('products', 'categories', 'pageTitle'));
    }
}
