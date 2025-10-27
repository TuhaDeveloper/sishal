<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attribute;

class AttributeController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view attribute list')) {
            abort(403, 'Unauthorized action.');
        }
        $attributes = Attribute::orderByDesc('id')->paginate(12);
        return view('erp.attributes.attributelist', compact('attributes'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('create attribute')) {
            abort(403, 'Unauthorized action.');
        }
        return view('erp.attributes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:attributes,slug',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        Attribute::create($validated);

        return redirect()->route('attribute.list')->with('success', 'Attribute created successfully');
    }

    public function show($id)
    {
        $attribute = Attribute::findOrFail($id);
        return view('erp.attributes.show', compact('attribute'));
    }

    public function edit($id)
    {
        $attribute = Attribute::findOrFail($id);
        return view('erp.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:attributes,slug,' . $attribute->id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        $attribute->update($validated);

        return redirect()->route('attribute.list')->with('success', 'Attribute updated successfully');
    }

    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();
        return redirect()->route('attribute.list')->with('success', 'Attribute deleted successfully');
    }
}
