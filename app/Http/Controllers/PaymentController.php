<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::with('customer')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'payment_type' => 'required|string|in:full,partial',
            'payment_method' => 'required|string|in:cash,online,upi',
            'date' => 'required|date',
        ]);

        $payment = Payment::create($data);
        return response()->json(['message' => 'Payment recorded', 'data' => $payment]);
    }

    public function show($id)
    {
        return response()->json(Payment::with('customer')->findOrFail($id));
    }

    public function destroy($id)
    {
        Payment::findOrFail($id)->delete();
        return response()->json(['message' => 'Payment deleted']);
    }
}
