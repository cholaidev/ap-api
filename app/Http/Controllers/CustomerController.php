<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SupplyItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    // 🔥 GET ALL CUSTOMERS
   public function index(Request $request)
{
    try {
        $status = $request->query('status', 'all'); // default = all

        $query = Customer::orderBy('id', 'DESC');

        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'cancelled') {
            $query->where('is_active', 0);
        }
        // else 'all' → no filter

        return response()->json([
            'status' => true,
            'message' => 'Customers loaded successfully',
            'data' => $query->get(),
        ], 200);

    } catch (\Throwable $e) {
        Log::error('Customer index error', ['error' => $e->getMessage()]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch customers',
        ], 500);
    }
}

    // 🔥 CREATE CUSTOMER
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'       => 'nullable|string|max:255',
                'phone'      => 'required|string|unique:customers,phone',
                'house_name' => 'nullable|string|max:255',
                'location'   => 'nullable|string|max:255',
                'milk'       => 'nullable|string|max:5000',
                'quantity'   => 'nullable|numeric|min:0.1',
            ]);
             Log::info('Customer validated payload', $validated);
            $customer = Customer::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Customer created successfully',
                'data' => $customer,
            ], 201);

        }
        // 🔴 VALIDATION ERROR
        catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        // 🔴 SERVER ERROR
        catch (\Throwable $e) {
            Log::error('Customer store error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create customer',
            ], 500);
        }
    }

    // 🔥 GET SINGLE CUSTOMER
    public function show($id)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => Customer::findOrFail($id),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found',
            ], 404);
        }
    }

    // 🔥 UPDATE CUSTOMER
    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $validated = $request->validate([
                'name'       => 'nullable|string|max:255',
                'phone'      => 'required|string|unique:customers,phone,' . $id,
                'house_name' => 'nullable|string|max:255',
                'location'   => 'nullable|string|max:255',
                'milk'       => 'nullable|string|max:5000',
                'quantity'   => 'nullable|numeric|min:0.1',
            ]);

            $customer->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer,
            ], 200);

        }
        catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        catch (\Throwable $e) {
            Log::error('Customer update error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update customer',
            ], 500);
        }
    }

    // 🔥 DELETE (SOFT)
    public function destroy($id)
{
    try {
        $customer = Customer::findOrFail($id);

        $customer->update([
            'is_active'    => 0,
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Customer cancelled successfully',
        ], 200);

    } catch (\Throwable $e) {
        Log::error('Customer cancel error', [
            'customer_id' => $id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'status'  => false,
            'message' => 'Failed to cancel customer',
        ], 500);
    }
}


    // 🔥 DELIVERY HISTORY
    public function deliveryHistory(Request $request, $customerId)
    {
        try {
            $query = SupplyItem::with(['supply.employee'])
                ->where('customer_id', $customerId);
               

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to   = Carbon::parse($request->to_date)->endOfDay();

                $query->whereHas('supply', function ($q) use ($from, $to) {
                    $q->whereBetween('supply_date', [$from, $to]);
                });
            }

            return response()->json([
                'status' => true,
                'data' => $query->latest()->get(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Customer delivery history error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch delivery history',
            ], 500);
        }
    }

   public function bulkImport(Request $request)
{
    Log::info('Customer bulk import started');

    try {
        $request->validate([
            'customers' => 'required|array|min:1',
            'customers.*.phone' => 'required|string',
        ]);

        $customers = $request->customers;
        $total = count($customers);

        Log::info('Bulk import request received', [
            'total_records' => $total,
        ]);

        $inserted = 0;
        $skipped = 0;
        $skippedPhones = [];

        foreach ($customers as $index => $row) {

            // 🔴 Skip if phone already exists
            if (Customer::where('phone', $row['phone'])->exists()) {
                $skipped++;
                $skippedPhones[] = $row['phone'];

                Log::warning('Customer skipped (duplicate phone)', [
                    'row' => $index + 1,
                    'phone' => $row['phone'],
                ]);

                continue;
            }

            // ✅ Create customer
            $customer = Customer::create([
                'name'       => $row['name'] ?? null,
                'phone'      => $row['phone'],
                'house_name' => $row['house'] ?? null,
                'milk'       => $row['milk'] ?? null,
                'quantity'   => $row['quantity'] ?? null,
                'location'   => $row['location'] ?? null,
                'is_active'  => 1,
            ]);

            $inserted++;

            Log::info('Customer inserted successfully', [
                'customer_id' => $customer->id,
                'phone' => $customer->phone,
            ]);
        }

        Log::info('Customer bulk import completed', [
            'total' => $total,
            'inserted' => $inserted,
            'skipped' => $skipped,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Bulk import completed',
            'inserted' => $inserted,
            'skipped' => $skipped,
            'skipped_phones' => $skippedPhones,
        ], 200);

    } catch (ValidationException $e) {

        Log::warning('Customer bulk import validation failed', [
            'errors' => $e->errors(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        Log::error('Customer bulk import crashed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Bulk import failed',
        ], 500);
    }
}

}
