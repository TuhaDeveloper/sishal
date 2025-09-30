<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{

    public function index()
    {
        $pageTitle = 'Wishlist';

        $wishlists = Wishlist::where('user_id',auth()->id())->get();
        return view('ecommerce.wishlist',compact('pageTitle','wishlists'));
    }
    public function addToWishlist($productId)
    {
        $userID = auth()->id();
        $existingWishlist = Wishlist::where('user_id',$userID)->where('product_id',$productId)->first();

        if($existingWishlist)
        {
            $existingWishlist->delete();
            return response()->json(['success' => true, 'message' => 'Wishlist Removed.']);
        }else{
            $wishlist = new Wishlist();
            $wishlist->user_id = $userID;
            $wishlist->product_id = $productId;
            $wishlist->save();
            return response()->json(['success' => true, 'message' => 'Added To Wishlist.']);
        }

    }

    public function wishlistCount()
    {
        $userId = auth()->id();

        $wishlistCount = Wishlist::where('user_id',$userId)->count();

        return response()->json($wishlistCount);
    }

    public function removeAllWishlist()
    {
        $userId = auth()->id();
        $wishlists = Wishlist::where('user_id',$userId)->get();
        
        foreach($wishlists as $wishlist)
        {
            $wishlist->delete();
        }

        return redirect()->back();
    }
}
