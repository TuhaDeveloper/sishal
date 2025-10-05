<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user']);

        // Filter by approval status
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search by user name or product name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%$search%")
                              ->orWhere('last_name', 'like', "%$search%")
                              ->orWhere('email', 'like', "%$search%");
                })->orWhereHas('product', function($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%$search%");
                });
            });
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('erp.reviews.index', compact('reviews', 'products'));
    }

    public function show($id)
    {
        $review = Review::with(['product', 'user'])->findOrFail($id);
        return view('erp.reviews.show', compact('review'));
    }

    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);

        return redirect()->back()->with('success', 'Review approved successfully!');
    }

    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => false]);

        return redirect()->back()->with('success', 'Review rejected successfully!');
    }

    public function feature($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_featured' => !$review->is_featured]);

        $status = $review->is_featured ? 'featured' : 'unfeatured';
        return redirect()->back()->with('success', "Review {$status} successfully!");
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully!');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id'
        ]);

        $reviewIds = $request->review_ids;
        $action = $request->action;

        switch ($action) {
            case 'approve':
                Review::whereIn('id', $reviewIds)->update(['is_approved' => true]);
                $message = 'Selected reviews approved successfully!';
                break;
            case 'reject':
                Review::whereIn('id', $reviewIds)->update(['is_approved' => false]);
                $message = 'Selected reviews rejected successfully!';
                break;
            case 'delete':
                Review::whereIn('id', $reviewIds)->delete();
                $message = 'Selected reviews deleted successfully!';
                break;
        }

        return redirect()->back()->with('success', $message);
    }
}