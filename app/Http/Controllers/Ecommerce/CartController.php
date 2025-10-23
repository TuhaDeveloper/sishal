<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    public function addToCartByCard($productId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to add items to cart',
                'redirect' => route('login')
            ], 401);
        }

        try {
            // Validate product exists
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $userId = Auth::user()->id;
            $sessionId = session()->getId();

            // Find existing cart item for this user and product
            $existingCart = Cart::where('product_id', $productId)
                ->where('user_id', $userId)
                ->first();

            if ($existingCart) {
                $existingCart->qty += 1;
                $existingCart->save();
            } else {
                $cartData = [
                    'product_id' => $productId,
                    'qty' => 1,
                    'user_id' => $userId
                ];
                $existingCart = Cart::create($cartData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully!',
                'cart' => $existingCart
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding product to cart', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart'
            ], 500);
        }
    }

    public function addToCartByPage($productId, Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to add items to cart',
                'redirect' => route('login')
            ], 401);
        }

        // Validate product ID
        if (!$productId || $productId === 'null' || $productId === '') {
            Log::error('Invalid product ID received', ['product_id' => $productId]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid product ID'
            ], 400);
        }

        // Debug logging
        Log::info('Cart add request', [
            'product_id' => $productId,
            'qty' => $request->input('qty'),
            'variation_id' => $request->input('variation_id'),
            'attribute_value_ids' => $request->input('attribute_value_ids'),
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name
        ]);

        $userId = Auth::user()->id;

        $qty = (int) $request->input('qty', 1);
        $variationId = $request->input('variation_id');
        $attributeValueIds = $request->input('attribute_value_ids', []);
        
        if ($qty < 1)
            $qty = 1;

        // Always load product with variations to validate variation requirements
        $product = \App\Models\Product::with('variations.combinations')->findOrFail($productId);

        // Resolve variation either by id or by attribute value ids
        $variation = null;
        if ($variationId) {
            $variation = \App\Models\ProductVariation::find($variationId);
        } elseif (is_array($attributeValueIds) && count($attributeValueIds) > 0) {
            $variation = $product->getVariationByAttributeValueIds($attributeValueIds);
            if ($variation) {
                $variationId = $variation->id;
            }
        }

        // Validate resolved variation
        if ($variation) {
            if ($variation->product_id != $productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid variation selected'
                ], 400);
            }
            if (!$variation->isInStock()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected variation is out of stock'
                ], 400);
            }
        } else {
            // If the product has variations, a valid variation must be selected
            if ($product->has_variations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a valid variation'
                ], 400);
            }
        }

        // Find existing cart item for this user, product, and variation
        $cartQuery = Cart::where('product_id', $productId)
            ->where('user_id', $userId);
        
        // Always check for variation_id (column exists)
        if ($variationId) {
            $cartQuery->where('variation_id', $variationId);
        } else {
            $cartQuery->whereNull('variation_id');
        }
        
        $existingCart = $cartQuery->first();

        if ($existingCart) {
            $existingCart->qty += $qty;
            $existingCart->save();
        } else {
            $cartData = [
                'product_id' => $productId,
                'qty' => $qty,
                'user_id' => $userId
            ];
            
            if ($variationId) {
                $cartData['variation_id'] = $variationId;
            }
            
            Log::info('Creating new cart item', [
                'cart_data' => $cartData,
                'user_id' => $userId
            ]);
            
            $existingCart = Cart::create($cartData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart' => $existingCart
        ]);
    }

    public function getCartQtySum()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['qty_sum' => 0]);
        }

        $userId = Auth::user()->id;
        $sum = Cart::where('user_id', $userId)->sum('qty');
        return response()->json(['qty_sum' => $sum]);
    }

    public function getCartList()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'cart' => [],
                'cart_total' => 0
            ]);
        }

        $userId = Auth::user()->id;
        $cartQuery = Cart::with('product')->where('user_id', $userId);
        
        $cartItems = $cartQuery->get();
        
        // Clean up old cart items (older than 24 hours) to prevent confusion
        $this->cleanupOldCartItems();
        
        // Debug logging
        Log::info('Cart list request', [
            'user_id' => $userId,
            'items_found' => $cartItems->count(),
            'items' => $cartItems->map(function($item) {
                return [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'user_id' => $item->user_id
                ];
            })->toArray()
        ]);

        $cartList = [];
        $cartTotal = 0;
        $itemsToRemove = []; // Track items to remove
        
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product) {
                // Product no longer exists, mark for removal
                $itemsToRemove[] = $item->id;
                Log::warning('Cart item references non-existent product', [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'user_id' => $item->user_id
                ]);
                continue;
            }
            // Use variation price if available, otherwise use product price
            $price = $product->price;
            if ($item->variation_id) {
                $variation = \App\Models\ProductVariation::find($item->variation_id);
                if ($variation && $variation->price) {
                    $price = $variation->price;
                }
            }
            
            // Apply discount if available
            if ($product->discount && $product->discount > 0) {
                $price = $product->discount;
            }
            
            $total = $price * $item->qty;
            $cartList[] = [
                'cart_id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'qty' => $item->qty,
                'price' => $price,
                'total' => $total,
            ];
            $cartTotal += $total;
        }
        
        // Remove orphaned cart items
        if (!empty($itemsToRemove)) {
            Cart::whereIn('id', $itemsToRemove)->delete();
            Log::info('Removed orphaned cart items', ['removed_ids' => $itemsToRemove]);
        }

        return response()->json([
            'cart' => $cartList,
            'cart_total' => $cartTotal
        ]);
    }
    
    private function cleanupOldCartItems()
    {
        // Remove cart items older than 24 hours to prevent confusion
        $cutoffTime = now()->subHours(24);
        $oldItems = Cart::where('created_at', '<', $cutoffTime)->get();
        
        if ($oldItems->count() > 0) {
            Log::info('Cleaning up old cart items', ['count' => $oldItems->count()]);
            Cart::where('created_at', '<', $cutoffTime)->delete();
        }
    }

    public function increaseQuantity($cartId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to modify cart',
                'redirect' => route('login')
            ], 401);
        }

        $userId = Auth::user()->id;
        
        $cartItem = Cart::where('id', $cartId)
            ->where('user_id', $userId)
            ->first();
        
        if ($cartItem) {
            $cartItem->qty += 1;
            $cartItem->save();
            return response()->json(['success' => true, 'qty' => $cartItem->qty]);
        }
        return response()->json(['success' => false, 'message' => 'Cart item not found']);
    }

    public function decreaseQuantity($cartId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to modify cart',
                'redirect' => route('login')
            ], 401);
        }

        $userId = Auth::user()->id;
        
        $cartItem = Cart::where('id', $cartId)
            ->where('user_id', $userId)
            ->first();
        
        if ($cartItem && $cartItem->qty > 1) {
            $cartItem->qty -= 1;
            $cartItem->save();
            return response()->json(['success' => true, 'qty' => $cartItem->qty]);
        }
        return response()->json(['success' => false, 'message' => 'Cannot decrease quantity']);
    }

    public function deleteCartItem($cartId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to modify cart',
                'redirect' => route('login')
            ], 401);
        }

        $userId = Auth::user()->id;
        
        $deleted = Cart::where('id', $cartId)
            ->where('user_id', $userId)
            ->delete();
        
        return response()->json(['success' => $deleted > 0]);
    }

    public function buyNow($productId)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $userId = Auth::user()->id;
            $cartItem = Cart::where('product_id', $productId)
                ->where('user_id', $userId)
                ->first();

            if ($cartItem) {
                // Update existing cart item quantity to 1
                $cartItem->qty += 1;
                $cartItem->save();
            } else {
                // Create new cart item if it doesn't exist
                $cartData = [
                    'product_id' => $productId,
                    'qty' => 1,
                    'user_id' => $userId
                ];

                Cart::create($cartData);
            }

            return redirect('/checkout');
        } catch (\Exception $e) {
            Log::error('Error buying now: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}