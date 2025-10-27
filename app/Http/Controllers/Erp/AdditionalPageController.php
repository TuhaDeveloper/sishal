<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AdditionalPage;
use Illuminate\Http\Request;

class AdditionalPageController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view additional list')) {
            abort(403, 'Unauthorized action.');
        }
        $additionalPages = AdditionalPage::all();
        return view('erp.additionalPages.additionalPageList', compact('additionalPages'));
    }

    public function create()
    {
        return view('erp.additionalPages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'slug' => ['required','string','max:255','alpha_dash','unique:additional_pages,slug'],
            'content' => ['required','string'],
            'positioned_at' => ['nullable','in:navbar,footer'],
        ]);

        AdditionalPage::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => $validated['content'],
            'positioned_at' => $validated['positioned_at'] ?? null,
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ]);

        return redirect()->route('additionalPages.index')->with('success','Page created');
    }

    public function show($id)
    {
        $page = AdditionalPage::findOrFail($id);
        return view('erp.additionalPages.show', compact('page'));
    }

    public function edit($id)
    {
        $page = AdditionalPage::findOrFail($id);
        return view('erp.additionalPages.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $page = AdditionalPage::findOrFail($id);
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'slug' => ['required','string','max:255','alpha_dash','unique:additional_pages,slug,'.$page->id],
            'content' => ['required','string'],
            'positioned_at' => ['nullable','in:navbar,footer'],
        ]);

        $page->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => $validated['content'],
            'positioned_at' => $validated['positioned_at'] ?? null,
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ]);

        return redirect()->route('additionalPages.index')->with('success','Page updated');
    }

    public function destroy($id)
    {
        $page = AdditionalPage::findOrFail($id);
        $page->delete();
        return redirect()->route('additionalPages.index')->with('success','Page deleted');
    }
}
