<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if(Auth::user()->hasPermissionTo('manage global branches')){
            return view('erp.branches.branchlist');
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    public function fetchBranches(Request $request)
    {
        $query = Branch::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $branches = $query->with('manager')->orderBy('id', 'desc')->paginate(10);
        $branches->getCollection()->transform(function($branch) {
            $branch->status = $branch->status ?? 'active';
            return $branch;
        });
        return response()->json($branches);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if(Auth::user()->hasPermissionTo('create branch')){
            $users = User::where('is_admin', 1)->get();
            return view('erp.branches.create', compact('users'));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'contact_info' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id'
        ]);
        $branch = Branch::create($validated);
        return redirect()->route('branches.index')->with('status', 'Branch created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if(Auth::user()->hasPermissionTo('view branch details')){
            $branch = Branch::with(['manager', 'employees.user', 'warehouses.manager', 'branchProductStocks.product', 'pos.invoice'])
                ->findOrFail($id);
            
            // Dynamic counts
            $employees_count = $branch->employees->count();
            $warehouses_count = $branch->warehouses->count();
            $products_count = $branch->branchProductStocks->count();
            
            // Calculate revenue from POS sales
            $revenue = $branch->pos->where('status', '!=', 'cancelled')->sum('total_amount');
            
            // Get recent sales (last 10)
            $recent_sales = $branch->pos()
                ->with(['customer', 'invoice'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Get branch products with stock info
            $branch_products = $branch->branchProductStocks()
                ->with(['product.category'])->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Get employees with their roles
            $employees = $branch->employees()
                ->with(['user.roles'])->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            return view('erp.branches.show', compact(
                'branch', 
                'products_count', 
                'employees_count', 
                'warehouses_count',
                'revenue',
                'recent_sales',
                'branch_products',
                'employees'
            ));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if(Auth::user()->hasPermissionTo('edit branch')){
            $branch = Branch::with('manager')->findOrFail($id);
            $users = User::where('is_admin', 1)->get();
            return view('erp.branches.edit', compact('branch', 'users'));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'contact_info' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id'
        ]);
        $branch->update($validated);
        return redirect()->route('branches.index')->with('status', 'Branch updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if(Auth::user()->hasPermissionTo('delete branch')){
            $branch = Branch::findOrFail($id);
            $branch->delete();
            return redirect()->route('branches.index')->with('status', 'Branch deleted successfully!');
        }else{
            return redirect()->route('erp.dashboard');
        }
    }


    // Branch Employee

    public function getNonBranchEmployee($branchId)
    {
        $search = request('search');
        $query = Employee::where('branch_id', '!=', $branchId)->orWhere('branch_id',null);
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%$search%"]);
            });
        }
        $employees = $query->with('user')->limit(20)->get();
        return response()->json($employees);
    }

    public function addEmployee($branchId, $empId)
    {
        $employee = Employee::find($empId);

        $employee->branch_id = $branchId;

        $employee->save();

        $user = User::find($employee->user_id);

        $user->is_admin = 1;

        $user->save();

        return redirect()->back();
    }

    public function removeEmployeeFromBranch($empId)
    {
        $employee = Employee::find($empId);

        $employee->branch_id = null;

        $employee->save();

        $user = User::find($employee->user_id);

        $user->is_admin = 0;

        $user->save();

        return redirect()->back();
    }

    /**
     * Get report data for the modal
     */
    public function getReportData(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('manage global branches')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Branch::with('manager');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Manager filter
        if ($request->filled('manager')) {
            $query->whereHas('manager', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->manager . '%')
                  ->orWhere('last_name', 'like', '%' . $request->manager . '%');
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $branches = $query->get();

        // Transform data for frontend
        $transformedBranches = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'contact_info' => $branch->contact_info,
                'manager_name' => $branch->manager ? $branch->manager->first_name . ' ' . $branch->manager->last_name : 'N/A',
                'status' => $branch->status ?? 'active',
            ];
        });

        // Calculate summary statistics
        $summary = [
            'total_branches' => $branches->count(),
            'active_branches' => $branches->where('status', 'active')->count(),
            'inactive_branches' => $branches->where('status', 'inactive')->count(),
        ];

        return response()->json([
            'branches' => $transformedBranches,
            'summary' => $summary
        ]);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('manage global branches')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Branch::with('manager');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('manager')) {
            $query->whereHas('manager', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->manager . '%')
                  ->orWhere('last_name', 'like', '%' . $request->manager . '%');
            });
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $branches = $query->get();
        $selectedColumns = $request->filled('columns') ? explode(',', $request->columns) : [];

        // Validate that at least one column is selected
        if (empty($selectedColumns)) {
            return response()->json(['error' => 'Please select at least one column to export.'], 400);
        }

        // Prepare data for export
        $exportData = [];
        
        // Add headers
        $headers = [];
        $columnMap = [
            'id' => 'ID',
            'name' => 'Branch Name',
            'location' => 'Location',
            'contact_info' => 'Contact Info',
            'manager' => 'Manager',
            'status' => 'Status'
        ];

        foreach ($selectedColumns as $column) {
            if (isset($columnMap[$column])) {
                $headers[] = $columnMap[$column];
            }
        }
        $exportData[] = $headers;

        // Add data rows
        foreach ($branches as $branch) {
            $row = [];
            foreach ($selectedColumns as $column) {
                switch ($column) {
                    case 'id':
                        $row[] = $branch->id;
                        break;
                    case 'name':
                        $row[] = $branch->name;
                        break;
                    case 'location':
                        $row[] = $branch->location ?? '-';
                        break;
                    case 'contact_info':
                        $row[] = $branch->contact_info ?? '-';
                        break;
                    case 'manager':
                        $row[] = $branch->manager ? $branch->manager->first_name . ' ' . $branch->manager->last_name : 'N/A';
                        break;
                    case 'status':
                        $row[] = ucfirst($branch->status ?? 'Active');
                        break;
                }
            }
            $exportData[] = $row;
        }

        // Generate filename
        $filename = 'branches_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add title
        $sheet->setCellValue('A1', 'Branch Report');
        if (count($headers) > 0) {
            $sheet->mergeCells('A1:' . chr(65 + count($headers) - 1) . '1');
        }
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Add summary info
        $totalBranches = $branches->count();
        $activeBranches = $branches->where('status', 'active')->count();
        $inactiveBranches = $branches->where('status', 'inactive')->count();
        
        if (count($headers) > 0) {
            $sheet->setCellValue('A3', 'Total Branches: ' . $totalBranches);
            $sheet->setCellValue('A4', 'Active Branches: ' . $activeBranches);
            $sheet->setCellValue('A5', 'Inactive Branches: ' . $inactiveBranches);
        }
        
        // Add data starting from row 7
        $rowIndex = 7;
        foreach ($exportData as $row) {
            $colIndex = 1;
            foreach ($row as $cellValue) {
                $sheet->setCellValue(chr(64 + $colIndex) . $rowIndex, $cellValue);
                $colIndex++;
            }
            $rowIndex++;
        }
        
        // Style the header row
        if (count($exportData) > 0) {
            $headerRow = 7;
            $lastColumn = count($headers);
            $sheet->getStyle('A' . $headerRow . ':' . chr(65 + $lastColumn - 1) . $headerRow)
                  ->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':' . chr(65 + $lastColumn - 1) . $headerRow)
                  ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('E3F2FD');
        }
        
        // Auto-size columns
        foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create writer and output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);
        
        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('manage global branches')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Branch::with('manager');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('manager')) {
            $query->whereHas('manager', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->manager . '%')
                  ->orWhere('last_name', 'like', '%' . $request->manager . '%');
            });
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $branches = $query->get();
        $selectedColumns = $request->filled('columns') ? explode(',', $request->columns) : [];

        // Validate that at least one column is selected
        if (empty($selectedColumns)) {
            return response()->json(['error' => 'Please select at least one column to export.'], 400);
        }

        // Prepare data for export
        $columnMap = [
            'id' => 'ID',
            'name' => 'Branch Name',
            'location' => 'Location',
            'contact_info' => 'Contact Info',
            'manager' => 'Manager',
            'status' => 'Status'
        ];

        $headers = [];
        foreach ($selectedColumns as $column) {
            if (isset($columnMap[$column])) {
                $headers[] = $columnMap[$column];
            }
        }

        // Calculate summary
        $summary = [
            'total_branches' => $branches->count(),
            'active_branches' => $branches->where('status', 'active')->count(),
            'inactive_branches' => $branches->where('status', 'inactive')->count(),
        ];

        // Generate filename
        $filename = 'branches_report_' . date('Y-m-d_H-i-s') . '.pdf';

        // Create PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.branches.report-pdf', [
            'branches' => $branches,
            'headers' => $headers,
            'selectedColumns' => $selectedColumns,
            'summary' => $summary,
            'filters' => [
                'status' => $request->status,
                'location' => $request->location,
                'manager' => $request->manager,
                'search' => $request->search,
            ]
        ]);

        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download($filename);
    }
}
