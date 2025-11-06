<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        return response()->json(Expense::with('vendor')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'amount' => 'required|numeric|min:1',
            'purpose' => 'required|string',
            'date' => 'required|date',
        ]);

        $expense = Expense::create($data);
        return response()->json(['message' => 'Expense recorded', 'data' => $expense]);
    }

    public function destroy($id)
    {
        Expense::findOrFail($id)->delete();
        return response()->json(['message' => 'Expense deleted']);
    }
}
