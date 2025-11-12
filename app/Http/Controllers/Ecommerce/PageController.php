<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductServiceCategory;
use App\Models\Vlog;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use App\Models\AdditionalPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Validator;
use App\Services\SmtpConfigService;

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
        // Create cache key based on request parameters
        $cacheKey = 'products_list_' . md5(serialize($request->all()));
        
        // Check if we should use cache (not for admin or when cache is disabled)
        $useCache = !$request->has('no_cache') && !$request->has('admin');
        
        if ($useCache) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return response()->view('ecommerce.products', $cachedData);
            }
        }
        
        $categories = ProductServiceCategory::where('status','active')->get();
        $query = Product::query();

        // Get the highest price of all products
        $maxProductPrice = Product::max('price') ?? 0;

        // Category filter - include child categories
        if ($request->has('categories') && is_array($request->categories) && count($request->categories)) {
            $categoryIds = ProductServiceCategory::whereIn('slug', $request->categories)->pluck('id')->toArray();
            // Get all child category IDs recursively
            $allCategoryIds = ProductServiceCategory::getAllChildIdsForCategories($categoryIds);
            $query->whereIn('category_id', $allCategoryIds);
        } elseif ($request->has('category') && $request->category) {
            // Single category filter (from category page links)
            $category = ProductServiceCategory::with('children')->where('slug', $request->category)->first();
            if ($category) {
                // Load all nested children recursively
                $category->loadNestedChildren();
                // Get all child category IDs recursively
                $allCategoryIds = $category->getAllChildIds();
                $query->whereIn('category_id', $allCategoryIds);
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

        // Optimize query with eager loading to prevent N+1 queries
        $products = $query->where('type','product')
            ->where('status', 'active')
            ->with([
                'category',
                'reviews' => function($q) { 
                    $q->where('is_approved', true); 
                },
                'branchStock',
                'warehouseStock',
                'variations' => function($q) {
                    $q->where('status', 'active');
                },
                'variations.stocks'
            ])
            ->paginate(20)->appends($request->all());
        
        // Pre-calculate ratings, reviews, and stock status to avoid N+1 queries
        $userId = Auth::id();
        $wishlistedIds = [];
        if ($userId) {
            $wishlistedIds = Wishlist::where('user_id', $userId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->toArray();
        }
        
        foreach ($products as $product) {
            // Add wishlist status
            $product->is_wishlisted = in_array($product->id, $wishlistedIds);
            
            // Pre-calculate ratings and reviews (avoid N+1 queries)
            $product->avg_rating = $product->reviews->avg('rating') ?? 0;
            $product->total_reviews = $product->reviews->count();
            
            // Pre-calculate stock status (avoid N+1 queries)
            // Check if product has variations - if so, check variation stocks
            if ($product->has_variations) {
                // For products with variations, check if any active variation has stock
                $product->has_stock = false;
                if ($product->variations && $product->variations->isNotEmpty()) {
                    foreach ($product->variations as $variation) {
                        // Check if variation has stocks loaded
                        if ($variation->relationLoaded('stocks') && $variation->stocks !== null) {
                            // Use loaded relationship collection
                            $totalQuantity = $variation->stocks->sum('quantity') ?? 0;
                            if ($totalQuantity > 0) {
                                $product->has_stock = true;
                                break; // Found at least one variation with stock, no need to check further
                            }
                        } else {
                            // Fallback: use query builder if relationship not loaded
                            $totalQuantity = $variation->stocks()->sum('quantity') ?? 0;
                            if ($totalQuantity > 0) {
                                $product->has_stock = true;
                                break;
                            }
                        }
                    }
                }
            } else {
                // For products without variations, check branch and warehouse stock
                $branchStock = $product->branchStock->sum('quantity') ?? 0;
                $warehouseStock = $product->warehouseStock->sum('quantity') ?? 0;
                $product->has_stock = ($branchStock + $warehouseStock) > 0;
            }
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
        
        // Cache the view data for 15 minutes if caching is enabled
        if ($useCache) {
            Cache::put($cacheKey, $viewData, 900); // 15 minutes
        }
        
        $response = response()->view('ecommerce.products', $viewData);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }

    public function productDetails($slug, Request $request)
    {
        try {
            // Create cache key for product details
            $cacheKey = 'product_details_' . $slug;
            $useCache = !$request->has('no_cache');
            
            // Try to get from cache first
            if ($useCache) {
                $cachedData = Cache::get($cacheKey);
                if ($cachedData) {
                    return response()->view('ecommerce.productDetails', $cachedData);
                }
            }
            
            \Log::info('=== PRODUCT DETAILS REQUEST ===', [
                'slug' => $slug,
                'url' => $request->url(),
                'timestamp' => now(),
                'request_id' => uniqid()
            ]);
            
            // First, get the basic product with minimal relations
            $product = Product::with([
                'category',
                'branchStock',
                'warehouseStock',
                'productAttributes',
                'galleries'
            ])->where('slug', $slug)->first();
            
            if (!$product) {
                \Log::error('Product not found', ['slug' => $slug]);
                abort(404, 'Product not found');
            }
            
            // Only load variations if the product has variations
            if ($product->has_variations) {
                $product->load([
                    'variations' => function($q) {
                        $q->where('status', 'active')
                          ->with([
                              'combinations.attribute', 
                              'combinations.attributeValue',
                              'stocks.branch',
                              'stocks.warehouse',
                              'galleries',
                          ]);
                    }
                ]);
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

            // Get general settings for social media links
            $settings = GeneralSetting::first();
            
            // Prepare view data
            $seoProduct = $product; // ensure header receives the exact product for meta tags
            $viewData = compact('product','relatedProducts','pageTitle','seoProduct','settings');
            
            // Cache the view data for 30 minutes if caching is enabled
            if ($useCache) {
                Cache::put($cacheKey, $viewData, 1800); // 30 minutes
            }
            
            $response = response()->view('ecommerce.productDetails', $viewData);
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

    public function submitContact(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill in all required fields correctly.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Configure SMTP from admin settings
            SmtpConfigService::configureFromSettings();
            
            // Prepare contact data
            $contactData = [
                'full_name' => $request->input('full_name'),
                'phone_number' => $request->input('phone_number'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'submitted_at' => now(),
            ];

            // Get the contact email from general settings
            $contactEmail = SmtpConfigService::getContactEmail();

            // Send email
            Mail::to($contactEmail)->send(new ContactMail($contactData));

            Log::info('Contact form submitted successfully', [
                'name' => $contactData['full_name'],
                'phone' => $contactData['phone_number'],
                'subject' => $contactData['subject']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you soon.'
            ]);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ], 500);
        }
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
        $query = Product::with([
                'category',
                'reviews' => function($q) { 
                    $q->where('is_approved', true); 
                },
                'branchStock',
                'warehouseStock',
                'variations' => function($q) {
                    $q->where('status', 'active');
                },
                'variations.stocks'
            ])
            ->where('status', 'active')
            ->where('type', 'product');

        // Category filter - include child categories
        if ($request->filled('categories') && !in_array('all', $request->categories)) {
            $categoryIds = ProductServiceCategory::whereIn('slug', $request->categories)->pluck('id')->toArray();
            // Get all child category IDs recursively
            $allCategoryIds = ProductServiceCategory::getAllChildIdsForCategories($categoryIds);
            $query->whereIn('category_id', $allCategoryIds);
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

        // Pre-calculate ratings, reviews, and stock status to avoid N+1 queries
        $userId = Auth::id();
        $wishlistedIds = [];
        if ($userId) {
            $wishlistedIds = Wishlist::where('user_id', $userId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->toArray();
        }
        
        foreach ($products as $product) {
            // Add wishlist status
            $product->is_wishlisted = in_array($product->id, $wishlistedIds);
            
            // Pre-calculate ratings and reviews (avoid N+1 queries)
            $product->avg_rating = $product->reviews->avg('rating') ?? 0;
            $product->total_reviews = $product->reviews->count();
            
            // Pre-calculate stock status (avoid N+1 queries)
            if ($product->has_variations) {
                // For products with variations, check if any active variation has stock
                $product->has_stock = false;
                if ($product->variations && $product->variations->isNotEmpty()) {
                    foreach ($product->variations as $variation) {
                        // Check if variation has stocks loaded
                        if ($variation->relationLoaded('stocks') && $variation->stocks !== null) {
                            // Use loaded relationship collection
                            $totalQuantity = $variation->stocks->sum('quantity') ?? 0;
                            if ($totalQuantity > 0) {
                                $product->has_stock = true;
                                break; // Found at least one variation with stock, no need to check further
                            }
                        } else {
                            // Fallback: use query builder if relationship not loaded
                            $totalQuantity = $variation->stocks()->sum('quantity') ?? 0;
                            if ($totalQuantity > 0) {
                                $product->has_stock = true;
                                break;
                            }
                        }
                    }
                }
            } else {
                // For products without variations, check branch and warehouse stock
                $branchStock = $product->branchStock->sum('quantity') ?? 0;
                $warehouseStock = $product->warehouseStock->sum('quantity') ?? 0;
                $product->has_stock = ($branchStock + $warehouseStock) > 0;
            }
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
