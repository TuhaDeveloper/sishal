<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vlog;
use Illuminate\Support\Facades\Log;

class VloggingController extends Controller
{
    public function index()
    {
        $vlogs = Vlog::all();
        return view('erp.vlogs.vloglist', compact('vlogs'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'frame_code' => ['required','string'],
            ]);

            $isActive = $request->boolean('is_active');
            $isFeatured = $request->boolean('is_featured');

            $vlog = Vlog::create([
                'frame_code' => $validated['frame_code'],
                'is_featured' => $isFeatured ? 1 : 0,
                'is_active' => $isActive ? 1 : 0,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'vlog' => $vlog]);
            }

            return redirect()->route('vlogging.index')->with('success','Vlog created successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->withErrors(['error' => 'Failed to create vlog.'])->withInput();
        }
    }

    public function update(Request $request, Vlog $vlog)
    {
        try {
            $validated = $request->validate([
                'frame_code' => ['required','string'],
            ]);

            $vlog->update([
                'frame_code' => $validated['frame_code'],
                'is_featured' => $request->boolean('is_featured') ? 1 : 0,
                'is_active' => $request->boolean('is_active') ? 1 : 0,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'vlog' => $vlog->fresh()]);
            }

            return redirect()->route('vlogging.index')->with('success','Vlog updated successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->withErrors(['error' => 'Failed to update vlog.'])->withInput();
        }
    }

    public function destroy(Request $request, Vlog $vlog)
    {
        try {
            $vlog->delete();
            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('vlogging.index')->with('success','Vlog deleted successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->withErrors(['error' => 'Failed to delete vlog.']);
        }
    }
}
