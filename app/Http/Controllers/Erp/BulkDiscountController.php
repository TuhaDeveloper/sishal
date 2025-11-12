<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\BulkDiscount;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BulkDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BulkDiscount::query();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%');
        }

        $discounts = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('erp.bulk-discounts.index', compact('discounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('status', 'active')->get();
        
        return view('erp.bulk-discounts.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,free_delivery',
            'value' => 'nullable|numeric|min:0',
            'scope_type' => 'required|in:all,products',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        // Validate value based on type (required for percentage and fixed, optional for free_delivery)
        if ($validated['type'] !== 'free_delivery' && empty($validated['value'])) {
            return back()->withErrors(['value' => 'Discount value is required for this discount type'])->withInput();
        }

        if ($validated['type'] === 'percentage' && isset($validated['value']) && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Percentage discount cannot exceed 100%'])->withInput();
        }

        // Set value to 0 or null for free_delivery
        if ($validated['type'] === 'free_delivery') {
            $validated['value'] = 0;
        }

        // Handle date conversion
        $inputTimezone = env('APP_INPUT_TZ', config('app.timezone', 'UTC'));
        if (!empty($validated['start_date'])) {
            $validated['start_date'] = Carbon::parse($validated['start_date'], $inputTimezone)->utc();
        }
        if (!empty($validated['end_date'])) {
            $validated['end_date'] = Carbon::parse($validated['end_date'], $inputTimezone)->utc();
        }

        // Convert empty arrays to null
        if (empty($validated['applicable_products'])) {
            $validated['applicable_products'] = null;
        }

        // Handle checkbox - if not present in request, it's unchecked (false)
        $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

        $bulkDiscount = BulkDiscount::create($validated);

        // Apply free delivery to products if active and type is free_delivery
        if ($validated['is_active'] && $validated['type'] === 'free_delivery') {
            $this->applyFreeDeliveryToProducts($bulkDiscount);
        }

        return redirect()->route('bulk-discounts.index')
                        ->with('success', 'Bulk discount created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(BulkDiscount $bulkDiscount)
    {
        return view('erp.bulk-discounts.show', compact('bulkDiscount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BulkDiscount $bulkDiscount)
    {
        $products = Product::where('status', 'active')->get();
        
        return view('erp.bulk-discounts.edit', compact('bulkDiscount', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BulkDiscount $bulkDiscount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,free_delivery',
            'value' => 'nullable|numeric|min:0',
            'scope_type' => 'required|in:all,products',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        // Validate value based on type (required for percentage and fixed, optional for free_delivery)
        if ($validated['type'] !== 'free_delivery' && empty($validated['value'])) {
            return back()->withErrors(['value' => 'Discount value is required for this discount type'])->withInput();
        }

        if ($validated['type'] === 'percentage' && isset($validated['value']) && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Percentage discount cannot exceed 100%'])->withInput();
        }

        // Store old state to check if we need to remove free delivery
        $wasFreeDelivery = $bulkDiscount->type === 'free_delivery';
        $wasActive = $bulkDiscount->is_active;

        // Set value to 0 or null for free_delivery
        if ($validated['type'] === 'free_delivery') {
            $validated['value'] = 0;
        }

        // Handle date conversion
        $inputTimezone = env('APP_INPUT_TZ', config('app.timezone', 'UTC'));
        if (!empty($validated['start_date'])) {
            $validated['start_date'] = Carbon::parse($validated['start_date'], $inputTimezone)->utc();
        } else {
            $validated['start_date'] = null;
        }
        if (!empty($validated['end_date'])) {
            $validated['end_date'] = Carbon::parse($validated['end_date'], $inputTimezone)->utc();
        } else {
            $validated['end_date'] = null;
        }

        // Convert empty arrays to null
        if (empty($validated['applicable_products'])) {
            $validated['applicable_products'] = null;
        }

        // Handle checkbox - if not present in request, it's unchecked (false)
        $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

        // Remove free delivery from products if it was active and now it's not
        if ($wasFreeDelivery && $wasActive && (!$validated['is_active'] || $validated['type'] !== 'free_delivery')) {
            $this->removeFreeDeliveryFromProducts($bulkDiscount);
        }

        $bulkDiscount->update($validated);

        // Apply free delivery to products if active and type is free_delivery
        if ($validated['is_active'] && $validated['type'] === 'free_delivery') {
            $this->applyFreeDeliveryToProducts($bulkDiscount);
        }

        return redirect()->route('bulk-discounts.index')
                        ->with('success', 'Bulk discount updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BulkDiscount $bulkDiscount)
    {
        // Remove free delivery from products if it was active
        if ($bulkDiscount->type === 'free_delivery' && $bulkDiscount->is_active) {
            $this->removeFreeDeliveryFromProducts($bulkDiscount);
        }

        $bulkDiscount->delete();

        return redirect()->route('bulk-discounts.index')
                        ->with('success', 'Bulk discount deleted successfully!');
    }

    /**
     * Toggle discount status
     */
    public function toggleStatus(BulkDiscount $bulkDiscount)
    {
        $wasActive = $bulkDiscount->is_active;
        $bulkDiscount->is_active = !$bulkDiscount->is_active;
        $bulkDiscount->save();

        // Handle free delivery based on status change
        if ($bulkDiscount->type === 'free_delivery') {
            if ($bulkDiscount->is_active) {
                // Apply free delivery when activated
                $this->applyFreeDeliveryToProducts($bulkDiscount);
            } else {
                // Remove free delivery when deactivated
                $this->removeFreeDeliveryFromProducts($bulkDiscount);
            }
        }

        return response()->json([
            'success' => true,
            'is_active' => $bulkDiscount->is_active,
            'message' => 'Bulk discount status updated successfully!'
        ]);
    }

    /**
     * Apply free delivery to products based on bulk discount
     */
    private function applyFreeDeliveryToProducts(BulkDiscount $bulkDiscount)
    {
        if ($bulkDiscount->scope_type === 'all') {
            // Apply to all active products
            Product::where('status', 'active')->update(['free_delivery' => true]);
        } else {
            // Apply to selected products
            if ($bulkDiscount->applicable_products && is_array($bulkDiscount->applicable_products)) {
                Product::whereIn('id', $bulkDiscount->applicable_products)
                    ->where('status', 'active')
                    ->update(['free_delivery' => true]);
            }
        }
    }

    /**
     * Remove free delivery from products based on bulk discount
     */
    private function removeFreeDeliveryFromProducts(BulkDiscount $bulkDiscount)
    {
        if ($bulkDiscount->scope_type === 'all') {
            // Check if there are other active free delivery discounts
            $otherActiveFreeDelivery = BulkDiscount::where('id', '!=', $bulkDiscount->id)
                ->where('type', 'free_delivery')
                ->where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', Carbon::now());
                })
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', Carbon::now());
                })
                ->exists();

            // Only remove if no other active free delivery discounts exist
            if (!$otherActiveFreeDelivery) {
                Product::where('status', 'active')->update(['free_delivery' => false]);
            }
        } else {
            // Remove from selected products
            if ($bulkDiscount->applicable_products && is_array($bulkDiscount->applicable_products)) {
                // Check if products are covered by other active free delivery discounts
                $otherActiveFreeDelivery = BulkDiscount::where('id', '!=', $bulkDiscount->id)
                    ->where('type', 'free_delivery')
                    ->where('is_active', true)
                    ->where(function($query) {
                        $query->whereNull('start_date')
                            ->orWhere('start_date', '<=', Carbon::now());
                    })
                    ->where(function($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', Carbon::now());
                    })
                    ->get();

                $productIdsToUpdate = $bulkDiscount->applicable_products;
                
                // Remove products that are covered by other discounts
                foreach ($otherActiveFreeDelivery as $otherDiscount) {
                    if ($otherDiscount->scope_type === 'all') {
                        // All products are covered, don't remove any
                        $productIdsToUpdate = [];
                        break;
                    } elseif ($otherDiscount->applicable_products) {
                        // Remove products that are in other discounts
                        $productIdsToUpdate = array_diff($productIdsToUpdate, $otherDiscount->applicable_products);
                    }
                }

                if (!empty($productIdsToUpdate)) {
                    Product::whereIn('id', $productIdsToUpdate)
                        ->where('status', 'active')
                        ->update(['free_delivery' => false]);
                }
            }
        }
    }
}
