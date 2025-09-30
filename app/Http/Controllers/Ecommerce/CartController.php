<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function addToCartByCard($productId)
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();

        // Use user_id if authenticated, otherwise session_id
        $cartQuery = Cart::where('product_id', $productId);
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $existingCart = $cartQuery->first();

        if ($existingCart) {
            $existingCart->qty += 1;
            $existingCart->save();
        } else {
            $cartData = [
                'product_id' => $productId,
                'qty' => 1,
            ];
            if ($userId) {
                $cartData['user_id'] = $userId;
            } else {
                $cartData['session_id'] = $sessionId;
            }
            $existingCart = Cart::create($cartData);
        }

        return response()->json([
            'success' => true,
            'cart' => $existingCart
        ]);
    }

    public function addToCartByPage($productId, Request $request)
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();

        $qty = (int) $request->input('qty', 1);
        if ($qty < 1)
            $qty = 1;

        // Use user_id if authenticated, otherwise session_id
        $cartQuery = Cart::where('product_id', $productId);
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $existingCart = $cartQuery->first();

        if ($existingCart) {
            $existingCart->qty += $qty;
            $existingCart->save();
        } else {
            $cartData = [
                'product_id' => $productId,
                'qty' => $qty,
            ];
            if ($userId) {
                $cartData['user_id'] = $userId;
            } else {
                $cartData['session_id'] = $sessionId;
            }
            $existingCart = Cart::create($cartData);
        }

        return response()->json([
            'success' => true,
            'cart' => $existingCart
        ]);
    }

    public function getCartQtySum()
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();
        if ($userId) {
            $sum = Cart::where('user_id', $userId)->sum('qty');
        } else {
            $sum = Cart::where('session_id', $sessionId)->sum('qty');
        }
        return response()->json(['qty_sum' => $sum]);
    }

    public function getCartList()
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();
        $cartQuery = Cart::with('product');
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $cartItems = $cartQuery->get();

        $cartList = [];
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product)
                continue;
            $price = $product->discount && $product->discount > 0 ? $product->discount : $product->price;
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

        return response()->json([
            'cart' => $cartList,
            'cart_total' => $cartTotal
        ]);
    }

    public function increaseQuantity($productId)
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();
        $cartQuery = Cart::where('product_id', $productId);
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $cartItem = $cartQuery->first();
        if ($cartItem) {
            $cartItem->qty += 1;
            $cartItem->save();
            return response()->json(['success' => true, 'qty' => $cartItem->qty]);
        }
        return response()->json(['success' => false, 'message' => 'Cart item not found']);
    }

    public function decreaseQuantity($productId)
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();
        $cartQuery = Cart::where('product_id', $productId);
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $cartItem = $cartQuery->first();
        if ($cartItem && $cartItem->qty > 1) {
            $cartItem->qty -= 1;
            $cartItem->save();
            return response()->json(['success' => true, 'qty' => $cartItem->qty]);
        }
        return response()->json(['success' => false, 'message' => 'Cannot decrease quantity']);
    }

    public function deleteCartItem($productId)
    {
        $userId = Auth::check() ? Auth::user()->id : null;
        $sessionId = session()->getId();
        $cartQuery = Cart::where('product_id', $productId);
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }
        $deleted = $cartQuery->delete();
        return response()->json(['success' => $deleted > 0]);
    }

    public function buyNow($productId)
    {
        try {
            $userId = Auth::check() ? Auth::user()->id : null;
            $sessionId = session()->getId();
            $cartQuery = Cart::where('product_id', $productId);
            if ($userId) {
                $cartQuery->where('user_id', $userId);
            } else {
                $cartQuery->where('session_id', $sessionId);
            }
            $cartItem = $cartQuery->first();

            if ($cartItem) {
                // Update existing cart item quantity to 1
                $cartItem->qty += 1;
                $cartItem->save();
            } else {
                // Create new cart item if it doesn't exist
                $cartData = [
                    'product_id' => $productId,
                    'qty' => 1
                ];

                if ($userId) {
                    $cartData['user_id'] = $userId;
                } else {
                    $cartData['session_id'] = $sessionId;
                }

                Cart::create($cartData);
            }

            return redirect('/checkout');
        } catch (\Exception $e) {
            Log::error('Error buying now: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}