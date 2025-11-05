<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view banner list')) {
            abort(403, 'Unauthorized action.');
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
        return view('erp.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'position' => 'required|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'link_url' => 'nullable|url',
            'link_text' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(8) . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/banners'), $imageName);
            $validated['image'] = 'uploads/banners/' . $imageName;
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
        return view('erp.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'position' => 'required|string|max:50',
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
                $oldPath = public_path($banner->image);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(8) . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/banners'), $imageName);
            $validated['image'] = 'uploads/banners/' . $imageName;
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
        // Delete image if exists
        if ($banner->image) {
            $oldPath = public_path($banner->image);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
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
