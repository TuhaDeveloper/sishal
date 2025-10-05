<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to submit a review.'
            ], 401);
        }

        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $request->product_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product.'
            ], 422);
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reviews', 'public');
        }

        // Create the review
        $review = Review::create([
            'product_id' => $request->product_id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
            'image' => $imagePath,
            'is_approved' => false // Reviews need approval by default
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully! It will be visible after approval.',
            'review' => $review
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you are not authorized to edit it.'
            ], 404);
        }

        // Handle image upload
        $imagePath = $review->image; // Keep existing image by default
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($review->image && Storage::disk('public')->exists($review->image)) {
                Storage::disk('public')->delete($review->image);
            }
            $imagePath = $request->file('image')->store('reviews', 'public');
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'image' => $imagePath,
            'is_approved' => false // Reset approval status when updated
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully! It will be visible after approval.',
            'review' => $review
        ]);
    }

    public function destroy($id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you are not authorized to delete it.'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully!'
        ]);
    }

    public function getProductReviews($productId)
    {
        $product = Product::findOrFail($productId);
        
        // Debug logging
        \Log::info('Getting reviews for product ID: ' . $productId . ' (' . $product->name . ')');
        
        $reviews = $product->approvedReviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Debug: Log the reviews being returned
        \Log::info('Reviews found for product ' . $productId . ': ' . $reviews->count());
        foreach ($reviews->items() as $review) {
            \Log::info('Review ID: ' . $review->id . ', Product ID: ' . $review->product_id . ', Comment: ' . $review->comment);
        }

        return response()->json([
            'success' => true,
            'product_id' => $productId,
            'product_name' => $product->name,
            'reviews' => [
                'data' => $reviews->items(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'next_page_url' => $reviews->nextPageUrl(),
                'prev_page_url' => $reviews->previousPageUrl()
            ],
            'average_rating' => $product->averageRating() ?? 0,
            'total_reviews' => $product->totalReviews()
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }
}