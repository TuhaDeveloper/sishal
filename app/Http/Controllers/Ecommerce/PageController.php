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
        if (class_exists('App\\Models\\Banner')) {
            $banners = \App\Models\Banner::currentlyActive()->orderBy('sort_order', 'asc')->get();
        }
        $featuredCategories = ProductServiceCategory::take(6)->get();
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
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.home', compact('featuredCategories', 'featuredServices', 'vlogs', 'pageTitle','categories','banners','bestDealProducts'))->render();
        }
        
        return view('ecommerce.home', compact('featuredCategories', 'featuredServices', 'vlogs', 'pageTitle','categories','banners','bestDealProducts'));
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

        // Rating filter
        if ($request->has('rating') && is_array($request->rating) && count($request->rating)) {
            $minRating = min($request->rating);
            $query->whereHas('reviews', function($q) use ($minRating) {
                $q->select('product_id')
                  ->groupBy('product_id')
                  ->havingRaw('AVG(rating) >= ?', [$minRating]);
            });
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
            return view('ecommerce.categories', compact('pageTitle', 'categories'));
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
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.products', $viewData, compact('pageTitle'))->render();
        }
        
        return view('ecommerce.products', $viewData, compact('pageTitle'));
    }

    public function productDetails($slug, Request $request)
    {
        try {
            $product = Product::where('slug', $slug)->first();
            
            if (!$product) {
                abort(404, 'Product not found');
            }
            
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

            // Handle AJAX requests - return only content
            if ($request->ajax()) {
                return view('ecommerce.productDetails', compact('product','relatedProducts','pageTitle'))->render();
            }

            return view('ecommerce.productDetails', compact('product','relatedProducts','pageTitle'));
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
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.searchresult',compact('products','search','pageTitle'))->render();
        }
        
        return view('ecommerce.searchresult',compact('products','search','pageTitle'));
    }

    public function services(Request $request)
    {
        $pageTitle = 'Services';
        $categories = ProductServiceCategory::where('status','active')->get();
        $services = Product::where('type','service')->paginate(12);
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.service',compact('pageTitle','services','categories'))->render();
        }
        
        return view('ecommerce.service',compact('pageTitle','services','categories'));
    }

    public function serviceDetails($slug, Request $request)
    {
        $service = Product::where('slug',$slug)->first();
        $pageTitle = $service->name;
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.servicedetails',compact('service','pageTitle'))->render();
        }
        
        return view('ecommerce.servicedetails',compact('service','pageTitle'));
    }

    public function about(Request $request)
    {
        $pageTitle = 'About Us';
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.about',compact('pageTitle'))->render();
        }
        
        return view('ecommerce.about',compact('pageTitle'));
    }

    public function contact(Request $request)
    {
        $pageTitle = 'Contact Us';
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.contact',compact('pageTitle'))->render();
        }
        
        return view('ecommerce.contact',compact('pageTitle'));
    }

    public function additionalPage($slug, Request $request)
    {
        $page = \App\Models\AdditionalPage::where('slug',$slug)->where('is_active',1)->firstOrFail();
        $pageTitle = $page->title;
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.additionalPage', compact('page','pageTitle'))->render();
        }
        
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

        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.vlogs', compact('pageTitle','vlogs','sort'))->render();
        }
        
        return view('ecommerce.vlogs', compact('pageTitle','vlogs','sort'));
    }

    public function categories(Request $request)
    {
        $pageTitle = 'Categories';
        $categories = ProductServiceCategory::where('status', 'active')->get();
        
        // Handle AJAX requests - return only content
        if ($request->ajax()) {
            return view('ecommerce.categories', compact('pageTitle', 'categories'))->render();
        }
        
        return view('ecommerce.categories', compact('pageTitle', 'categories'));
    }

    public function bestDeals(Request $request)
    {
        $pageTitle = 'Best Deal';
        $query = Product::query()->where('type', 'product');

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

        if ($request->ajax()) {
            return view('ecommerce.best-deal', compact('pageTitle', 'products'))->render();
        }

        return view('ecommerce.best-deal', compact('pageTitle', 'products'));
    }
}
