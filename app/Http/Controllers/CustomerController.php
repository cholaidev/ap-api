<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index()
{
    try {
        Log::info('Fetching customers data');

        $customers = Customer::with(['staff', 'deliveries', 'payments'])->get();

        Log::info('Customers data: ' . json_encode($customers));

        return response()->json([
            'status' => true,
            'message' => 'Customers fetched successfully',
            'data' => $customers
        ], 200);

    } catch (\Exception $e) {
        Log::error("Customer index error: " . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch customers',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'phone' => 'required|string|unique:customers',
                'address' => 'nullable|string',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $customer = Customer::create($data);
            return response()->json(['message' => 'Customer created', 'data' => $customer]);

        } catch (\Exception $e) {
            Log::error("Customer store error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create customer'], 500);
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::with(['staff', 'deliveries', 'payments'])->findOrFail($id);
            return response()->json($customer);

        } catch (\Exception $e) {
            Log::error("Customer show error: " . $e->getMessage());
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|string',
                'phone' => 'sometimes|string|unique:customers,phone,' . $id,
                'address' => 'nullable|string',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $customer->update($data);

            return response()->json(['message' => 'Customer updated', 'data' => $customer]);

        } catch (\Exception $e) {
            Log::error("Customer update error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update customer'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Customer::findOrFail($id)->delete();
            return response()->json(['message' => 'Customer deleted']);

        } catch (\Exception $e) {
            Log::error("Customer destroy error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete customer'], 500);
        }
    }
}
