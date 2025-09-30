<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialAccount;
use App\Models\ChartOfAccount;

class FinancialAccountController extends Controller
{
    public function list()
    {
        $accounts = FinancialAccount::with(['account.parent', 'account.type', 'account.subType'])->get();
        $chartAccounts = ChartOfAccount::with(['parent', 'type', 'subType'])->get();

        return view('erp.financialAccount.list', compact('accounts', 'chartAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'type' => 'required|in:bank,mobile',
            'provider_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'branch_name' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:255',
        ]);

        FinancialAccount::create($request->all());
        return redirect()->route('financial-accounts.list')->with('success', 'Financial account created successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'type' => 'required|in:bank,mobile',
            'provider_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'branch_name' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:255',
        ]);

        $financialAccount = FinancialAccount::findOrFail($id);
        $financialAccount->update($request->all());
        return redirect()->route('financial-accounts.list')->with('success', 'Financial account updated successfully');
    }

    public function destroy($id)
    {
        $financialAccount = FinancialAccount::findOrFail($id);
        $financialAccount->delete();
        return response()->json(['success' => true, 'message' => 'Financial account deleted successfully']);
    }
}
