<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SupplyItem;
use Carbon\Carbon;
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

public function deliveryHistory(Request $request, $customerId)
{
    try {
        Log::info('Customer delivery history request', [
            'customer_id' => $customerId,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ]);

        $query = SupplyItem::with(['supply.employee'])
            ->where('customer_id', $customerId)
            ->where('is_delivered', 1);

        // ✅ FILTER BY SUPPLY DATE (FROM SUPPLIES TABLE)
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereHas('supply', function ($q) use ($from, $to) {
                $q->whereBetween('supply_date', [$from, $to]);
            });
        }

        $items = $query->latest()->get();

        Log::info('Customer delivery history result', [
            'count' => $items->count(),
        ]);

        return response()->json([
            'status' => true,
            'data' => $items,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('Customer delivery history failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch delivery history',
        ], 500);
    }
}


}
