<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get reviews for a specific product
     */
    public function getProductReviews($productId, Request $request)
    {
        \Log::info('Getting reviews for product', [
            'product_id' => $productId,
            'request_url' => request()->url(),
            'rating_filter' => $request->get('rating'),
            'timestamp' => now()
        ]);
        
        try {
            $product = Product::findOrFail($productId);
            
            $query = Review::with(['user'])
                ->where('product_id', $productId)
                ->approved()
                ->orderByFeatured();
            
            // Apply rating filter if provided
            if ($request->filled('rating')) {
                $query->where('rating', $request->rating);
            }
            
            $reviews = $query->paginate(10);
                
            \Log::info('Reviews found', [
                'product_id' => $productId,
                'product_name' => $product->name,
                'reviews_count' => $reviews->count(),
                'review_ids' => $reviews->pluck('id')->toArray()
            ]);

            // Calculate average rating and total reviews based on filter
            $baseQuery = Review::where('product_id', $productId)->approved();
            if ($request->filled('rating')) {
                $baseQuery->where('rating', $request->rating);
            }
            
            $averageRating = $baseQuery->avg('rating') ?? 0;
            $totalReviews = $baseQuery->count();

            // Rating distribution (always show all ratings, not filtered)
            $ratingDistribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $ratingDistribution[$i] = Review::where('product_id', $productId)
                    ->approved()
                    ->byRating($i)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'reviews' => $reviews,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
                'rating_distribution' => $ratingDistribution,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load reviews'
            ], 500);
        }
    }

    /**
     * Store a new review
     */
    public function store(Request $request, $productId)
    {
        \Log::info('Review submission attempt', [
            'product_id' => $productId,
            'request_data' => $request->all(),
            'user_id' => Auth::id(),
            'csrf_token' => $request->input('_token'),
            'headers' => $request->headers->all()
        ]);
        
        try {
            $product = Product::findOrFail($productId);
            
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to submit a review'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:5|max:1000'
            ]);

            if ($validator->fails()) {
                \Log::info('Review validation failed', [
                    'request_data' => $request->all(),
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user already reviewed this product
            $existingReview = Review::where('product_id', $productId)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reviewed this product'
                ], 409);
            }

            $review = Review::create([
                'product_id' => $productId,
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_approved' => true, // Auto-approve for now
                'is_featured' => false
            ]);

            $review->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully!',
                'review' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review'
            ], 500);
        }
    }

    /**
     * Update an existing review
     */
    public function update(Request $request, $productId, $reviewId)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to update review'
                ], 401);
            }

            $review = Review::where('id', $reviewId)
                ->where('product_id', $productId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:5|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review->update([
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            $review->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully!',
                'review' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy($productId, $reviewId)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to delete review'
                ], 401);
            }

            $review = Review::where('id', $reviewId)
                ->where('product_id', $productId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

}
