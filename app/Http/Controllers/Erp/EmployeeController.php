<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Balance;
use App\Models\Pos;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view employee list')) {
            abort(403, 'Unauthorized action.');
        }
        $query = Employee::with('user','balance');
        if ($request->filled('name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        if ($request->filled('designation')) {
            $query->where('designation', 'like', '%' . $request->designation . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $employees = $query->orderBy('id', 'desc')->paginate(10)->appends($request->except('page'));
        return view('erp.employees.employeelist', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasPermissionTo('employee create')) {
            abort(403, 'Unauthorized action.');
        }
        $roles = Role::all();
        return view('erp.employees.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20'
        ]);

        $user = new User();

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->is_admin = 1;
        $user->assignRole($request->role);
        $user->save();

        $employee = new Employee();
        $employee->user_id = $user->id;
        $employee->branch_id = $request->branch_id;
        $employee->designation = $request->role;
        $employee->phone = $request->phone;
        $employee->status = $request->status;

        $employee->save();

        return redirect()->route('employees.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!auth()->user()->hasPermissionTo('view employee')) {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::with(['user.roles', 'branch'])->findOrFail($id);

        // Derive simple computed fields for the view
        $fullName = trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''));
        $primaryRole = optional($employee->user->roles->first())->name;

        // Aggregate balance for this employee
        $employeeBalance = Balance::where('source_type', 'employee')
            ->where('source_id', $employee->id)
            ->sum('balance');

        // Count number of sales associated with this employee (as technician or salesperson)
        $salesCount = Pos::where('employee_id', $employee->id)
            ->orWhere('sold_by', $employee->user_id)
            ->count();

        return view('erp.employees.show', [
            'employee' => $employee,
            'fullName' => $fullName,
            'primaryRole' => $primaryRole,
            'employeeBalance' => $employeeBalance,
            'salesCount' => $salesCount,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('employee edit')) {
            abort(403, 'Unauthorized action.');
        }
        $employee = Employee::with(['user', 'branch'])->findOrFail($id);
        $roles = Role::all();
        return view('erp.employees.edit', compact('employee', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);
        $user = $employee->user;
        // Remove existing role if user has any
        if ($user->roles->first()) {
            $user->removeRole($user->roles->first()->name);
        }
        $user->assignRole($validated['role']);
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->save();
        $employee->branch_id = $validated['branch_id'];
        $employee->designation = $validated['role'];
        $employee->phone = $validated['phone'];
        $employee->status = $validated['status'];
        $employee->save();
        return redirect()->route('employees.index')->with('status', 'Employee updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasPermissionTo('employee delete')) {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::with('user')->findOrFail($id);
        if ($employee->user) {
            $employee->user->delete();
        }
        $employee->delete();
        return redirect()->route('employees.index')->with('status', 'Employee deleted successfully!');
    }

    public function employeeSearch(Request $request)
    {
        $q = $request->input('q');
        $query = Employee::with('user');
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('user', function($userQ) use ($q) {
                    $userQ->whereRaw("CONCAT(users.first_name, ' ', users.last_name) like ?", ["%$q%"])
                          ->orWhere('users.first_name', 'like', "%$q%")
                          ->orWhere('users.last_name', 'like', "%$q%")
                          ->orWhere('users.email', 'like', "%$q%")
                          ;
                })
                ->orWhere('phone', 'like', "%$q%")
                ->orWhere('id', $q);
            });
        }
        $employees = $query->orderBy('id', 'desc')->limit(20)->get();
        $results = $employees->map(function($employee) {
            $user = $employee->user;
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            return [
                'id' => $employee->id,
                'name' => $fullName,
                'email' => $user->email ?? '',
                'phone' => $employee->phone,
            ];
        });
        return response()->json($results);
    }
}
