<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use Illuminate\Support\Facades\Auth;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountParent;

class ChartOfAccountController extends Controller
{
    public function accountType()
    {
        $accountTypes = ChartOfAccountType::with(['createdBy', 'subTypes.createdBy'])->orderBy('created_at', 'desc')->get();
        return view('erp.doubleEntry.accounttype', compact('accountTypes'));
    }

    public function accountTypeStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:chart_of_account_types,name',
        ]);

        if($request->type_id){
            $accountType = new ChartOfAccountSubType();
            $accountType->name = $request->name;
            $accountType->type_id = $request->type_id;
            $accountType->created_by = Auth::user()->id;
            $accountType->save();
        }else{
            $accountType = new ChartOfAccountType();
            $accountType->name = $request->name;
            $accountType->created_by = Auth::user()->id;
            $accountType->save();
        }

        return redirect()->back()->with('success', 'Account type created successfully!');
    }

    public function accountTypeUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if($request->type_id){
            // Update ChartOfAccountSubType
            $accountType = ChartOfAccountSubType::findOrFail($id);
            $accountType->name = $request->name;
            $accountType->type_id = $request->type_id;
            $accountType->save();
        } else {
            // Update ChartOfAccountType
            $request->validate([
                'name' => 'required|string|max:255|unique:chart_of_account_types,name,' . $id,
            ]);
            
            $accountType = ChartOfAccountType::findOrFail($id);
            $accountType->name = $request->name;
            $accountType->save();
        }

        return redirect()->back()->with('success', 'Account type updated successfully!');
    }

    public function accountTypeDelete($id)
    {
        try {
            // Try to find as ChartOfAccountType first
            $accountType = ChartOfAccountType::find($id);
            
            if ($accountType) {
                $accountType->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Account type deleted successfully!'
                ]);
            }
            
            // If not found, try as ChartOfAccountSubType
            $subType = ChartOfAccountSubType::find($id);
            
            if ($subType) {
                $subType->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Account sub-type deleted successfully!'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Account type not found.'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the account type.'
            ], 500);
        }
    }

    public function list()
    {
        $chartOfAccounts = ChartOfAccount::with(['parent', 'type', 'subType', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $accountTypes = ChartOfAccountType::with('subTypes')->get();
        $accountParents = ChartOfAccountParent::with(['type', 'subType'])->get();
        
        return view('erp.doubleEntry.chartofaccount', compact('chartOfAccounts', 'accountTypes', 'accountParents'));
    }

    public function getSubTypesByType($typeId)
    {
        $subTypes = ChartOfAccountSubType::where('type_id', $typeId)->get();
        return response()->json($subTypes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:chart_of_accounts,code',
            'parent_id' => 'required|exists:chart_of_account_parents,id',
            'type_id' => 'required|exists:chart_of_account_types,id',
            'sub_type_id' => 'required|exists:chart_of_account_sub_types,id',
            'description' => 'nullable|string',
        ]);

        try {
            $account = new ChartOfAccount();
            $account->name = $request->name;
            $account->code = $request->code;
            $account->parent_id = $request->parent_id;
            $account->type_id = $request->type_id;
            $account->sub_type_id = $request->sub_type_id;
            $account->description = $request->description;
            $account->is_cash_account = $request->is_cash_account;
            $account->created_by = Auth::user()->id;
            $account->save();

            return redirect()->back()->with('success', 'Chart account created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while creating the chart account.')->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:chart_of_accounts,code,' . $id,
            'parent_id' => 'required|exists:chart_of_account_parents,id',
            'type_id' => 'required|exists:chart_of_account_types,id',
            'sub_type_id' => 'required|exists:chart_of_account_sub_types,id',
            'description' => 'nullable|string',
        ]);

        try {
            $account = ChartOfAccount::findOrFail($id);
            $account->name = $request->name;
            $account->code = $request->code;
            $account->parent_id = $request->parent_id;
            $account->type_id = $request->type_id;
            $account->sub_type_id = $request->sub_type_id;
            $account->description = $request->description;
            $account->is_cash_account = $request->is_cash_account;
            $account->save();

            return redirect()->back()->with('success', 'Chart account updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the chart account.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $account = ChartOfAccount::findOrFail($id);
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chart account deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the chart account.'
            ], 500);
        }
    }

    public function storeParent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:chart_of_account_parents,code',
            'type_id' => 'required|exists:chart_of_account_types,id',
            'sub_type_id' => 'required|exists:chart_of_account_sub_types,id',
            'description' => 'nullable|string',
        ]);

        try {
            $parent = new ChartOfAccountParent();
            $parent->name = $request->name;
            $parent->code = $request->code;
            $parent->type_id = $request->type_id;
            $parent->sub_type_id = $request->sub_type_id;
            $parent->description = $request->description;
            $parent->created_by = Auth::user()->id;
            $parent->save();

            return redirect()->back()->with('success', 'Parent account created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while creating the parent account.')->withInput();
        }
    }

    public function updateParent(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:chart_of_account_parents,code,' . $id,
            'type_id' => 'required|exists:chart_of_account_types,id',
            'sub_type_id' => 'required|exists:chart_of_account_sub_types,id',
            'description' => 'nullable|string',
        ]);

        try {
            $parent = ChartOfAccountParent::findOrFail($id);
            $parent->name = $request->name;
            $parent->code = $request->code;
            $parent->type_id = $request->type_id;
            $parent->sub_type_id = $request->sub_type_id;
            $parent->description = $request->description;
            $parent->save();

            return redirect()->back()->with('success', 'Parent account updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the parent account.')->withInput();
        }
    }

    public function destroyParent($id)
    {
        try {
            $parent = ChartOfAccountParent::findOrFail($id);
            $parent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Parent account deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the parent account.'
            ], 500);
        }
    }
}
