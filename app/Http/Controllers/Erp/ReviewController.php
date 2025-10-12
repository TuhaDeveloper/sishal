<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews
     */
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user']);

        // Filter by approval status
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search by user name, product name, or review content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%$search%")
                              ->orWhere('last_name', 'like', "%$search%")
                              ->orWhere('email', 'like', "%$search%");
                })->orWhereHas('product', function($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%$search%");
                })->orWhere('title', 'like', "%$search%")
                  ->orWhere('comment', 'like', "%$search%");
            });
        }

        $reviews = $query->recent()->paginate(15)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('erp.reviews.index', compact('reviews', 'products'));
    }

    /**
     * Display the specified review
     */
    public function show($id)
    {
        $review = Review::with(['product', 'user'])->findOrFail($id);
        return view('erp.reviews.show', compact('review'));
    }

    /**
     * Approve a review
     */
    public function approve($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update(['is_approved' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Review approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review'
            ], 500);
        }
    }

    /**
     * Reject a review
     */
    public function reject($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update(['is_approved' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Review rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject review'
            ], 500);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update(['is_featured' => !$review->is_featured]);

            return response()->json([
                'success' => true,
                'message' => $review->is_featured ? 'Review featured successfully' : 'Review unfeatured successfully',
                'is_featured' => $review->is_featured
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status'
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

    /**
     * Bulk actions on reviews
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete,feature,unfeature',
            'review_ids' => 'required|array|min:1',
            'review_ids.*' => 'integer|exists:reviews,id'
        ]);

        try {
            $reviewIds = $request->review_ids;
            $action = $request->action;
            $count = 0;

            switch ($action) {
                case 'approve':
                    $count = Review::whereIn('id', $reviewIds)->update(['is_approved' => true]);
                    break;
                case 'reject':
                    $count = Review::whereIn('id', $reviewIds)->update(['is_approved' => false]);
                    break;
                case 'delete':
                    $count = Review::whereIn('id', $reviewIds)->delete();
                    break;
                case 'feature':
                    $count = Review::whereIn('id', $reviewIds)->update(['is_featured' => true]);
                    break;
                case 'unfeature':
                    $count = Review::whereIn('id', $reviewIds)->update(['is_featured' => false]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully processed {$count} reviews"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk action'
            ], 500);
        }
    }

    /**
     * Get review statistics
     */
    public function statistics()
    {
        $stats = [
            'total_reviews' => Review::count(),
            'approved_reviews' => Review::approved()->count(),
            'pending_reviews' => Review::where('is_approved', false)->count(),
            'featured_reviews' => Review::featured()->count(),
            'average_rating' => Review::approved()->avg('rating') ?? 0,
            'reviews_by_rating' => Review::approved()
                ->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get()
                ->pluck('count', 'rating')
                ->toArray()
        ];

        return response()->json($stats);
    }
}
