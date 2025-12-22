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
    public function index()
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Customers loaded successfully',
                'data' => Customer::orderBy('id', 'DESC')->get(),
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
                'milk'       => 'nullable|string|max:50',
                'quantity'   => 'nullable|integer|min:1',
            ]);

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
                'milk'       => 'nullable|string|max:50',
                'quantity'   => 'nullable|integer|min:1',
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

            $customer->update(['is_active' => 0]);

            return response()->json([
                'status' => true,
                'message' => 'Customer deactivated successfully',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Customer delete error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete customer',
            ], 500);
        }
    }

    // 🔥 DELIVERY HISTORY
    public function deliveryHistory(Request $request, $customerId)
    {
        try {
            $query = SupplyItem::with(['supply.employee'])
                ->where('customer_id', $customerId)
                ->where('is_delivered', 1);

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
}
