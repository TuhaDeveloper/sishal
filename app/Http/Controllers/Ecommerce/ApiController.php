<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

class ApiController extends Controller
{
    public function mostSoldProducts()
    {
        $userId = Auth::id();
        $products = \App\Models\Product::with('category')->where('type','product')
            ->take(20)
            ->get();

        // Attach is_wishlisted and rating data to each product
        $products->transform(function ($product) use ($userId) {
            $product->is_wishlisted = false;
            if ($userId) {
                $product->is_wishlisted = Wishlist::where('user_id', $userId)
                    ->where('product_id', $product->id)
                    ->exists();
            }
            
            
            return $product;
        });

        return response()->json($products);
    }
}
