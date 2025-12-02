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
        Log::info('Fetching customers');

         $customers = Customer::orderBy('id', 'DESC')->get();

        return response()->json([
            'status' => true,
            'message' => 'Customers loaded successfully',
            'data' => $customers
        ]);
    } catch (\Exception $e) {
        Log::error("Customer index error: ".$e->getMessage());

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
        $customer = Customer::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Customer created successfully',
            'data' => $customer,
        ]);

    } catch (\Exception $e) {
        Log::error("Customer store error: ".$e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Failed to create customer',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function show($id)
    {
        try {
            Log::info('get customer data '.$id);
            $customer = Customer::findOrFail($id);
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

        $customer->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer,
        ]);

    } catch (\Exception $e) {
        Log::error("Customer update error: ".$e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Failed to update customer',
            'error' => $e->getMessage(),
        ], 500);
    }
}


   public function destroy($id)
{
    try {
        Customer::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Customer deleted successfully'
        ]);

    } catch (\Exception $e) {
        Log::error("Customer delete error: ".$e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Failed to delete customer',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
