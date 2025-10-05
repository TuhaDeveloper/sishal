<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasPermissionTo('view banners')) {
            return redirect()->route('erp.dashboard')->with('error', 'You do not have permission to view banners.');
        }

        $query = Banner::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by position
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $banners = $query->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        return view('erp.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::user()->hasPermissionTo('create banners')) {
            return redirect()->route('banners.index')->with('error', 'You do not have permission to create banners.');
        }

        return view('erp.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermissionTo('create banners')) {
            return redirect()->route('banners.index')->with('error', 'You do not have permission to create banners.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'link_url' => 'nullable|url',
            'link_text' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('banners', 'public');
            $validated['image'] = $imagePath;
        }

        // Normalize date-times: treat input as business timezone and store as UTC
        $inputTimezone = env('APP_INPUT_TZ', config('app.timezone', 'UTC'));
        $validated['start_date'] = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'], $inputTimezone)->utc()
            : null;
        $validated['end_date'] = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'], $inputTimezone)->utc()
            : null;

        Banner::create($validated);

        return redirect()->route('banners.index')
                        ->with('success', 'Banner created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return view('erp.banners.show', compact('banner'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        if (!Auth::user()->hasPermissionTo('edit banners')) {
            return redirect()->route('banners.index')->with('error', 'You do not have permission to edit banners.');
        }

        return view('erp.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banner $banner)
    {
        if (!Auth::user()->hasPermissionTo('edit banners')) {
            return redirect()->route('banners.index')->with('error', 'You do not have permission to edit banners.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'link_url' => 'nullable|url',
            'link_text' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $imagePath = $request->file('image')->store('banners', 'public');
            $validated['image'] = $imagePath;
        }
        // Normalize date-times: treat input as business timezone and store as UTC
        $inputTimezone = env('APP_INPUT_TZ', config('app.timezone', 'UTC'));
        $validated['start_date'] = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'], $inputTimezone)->utc()
            : null;
        $validated['end_date'] = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'], $inputTimezone)->utc()
            : null;

        $banner->update($validated);

        return redirect()->route('banners.index')
                        ->with('success', 'Banner updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        if (!Auth::user()->hasPermissionTo('delete banners')) {
            return redirect()->route('banners.index')->with('error', 'You do not have permission to delete banners.');
        }

        // Delete image if exists
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()->route('banners.index')
                        ->with('success', 'Banner deleted successfully!');
    }

    /**
     * Toggle banner status
     */
    public function toggleStatus(Banner $banner)
    {
        $banner->status = $banner->status === 'active' ? 'inactive' : 'active';
        $banner->save();

        return response()->json([
            'success' => true,
            'status' => $banner->status,
            'message' => 'Banner status updated successfully!'
        ]);
    }
}
