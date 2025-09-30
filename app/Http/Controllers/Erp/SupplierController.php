<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ;
            });
        }
        $suppliers = $query->paginate(10)->appends($request->except('page'));
        return view('erp.supplier.supplierlist', compact('suppliers'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
        ]);
        Supplier::create($validated);
        return redirect()->back()->with('success', 'Supplier added successfully!');
    }

    public function show($id)
    {
        $supplier = Supplier::find($id);

        return view('erp.supplier.show', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
        ]);
        $supplier = Supplier::findOrFail($id);
        $supplier->update($validated);
        return redirect()->back()->with('success', 'Supplier updated successfully!');
    }

    public function delete($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return redirect()->back()->with('success', 'Supplier deleted successfully!');
    }

    public function supplierSearch(Request $request)
    {
        $search = $request->q;
        $suppliers = Supplier::query()
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            })
            ->select('id', 'name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $suppliers->map(function($supplier) {
                return ['id' => $supplier->id, 'text' => $supplier->name];
            }),
        ]);
    }
}
