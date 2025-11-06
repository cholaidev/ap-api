<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['staff', 'deliveries', 'payments'])->get();
        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:customers',
            'address' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $customer = Customer::create($data);
        return response()->json(['message' => 'Customer created', 'data' => $customer]);
    }

    public function show($id)
    {
        $customer = Customer::with(['staff', 'deliveries', 'payments'])->findOrFail($id);
        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'sometimes|string|unique:customers,phone,' . $id,
            'address' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $customer->update($data);
        return response()->json(['message' => 'Customer updated', 'data' => $customer]);
    }

    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();
        return response()->json(['message' => 'Customer deleted']);
    }
}
