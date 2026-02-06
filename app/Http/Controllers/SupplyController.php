<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplyController extends Controller
{
    // 🔥 SUPPLY LIST
    public function index(Request $request)
    {
        try {
            $query = Supply::with(['employee', 'items.customer']);

            if ($request->has('is_assigned_only')) {
                $query->where('is_assigned_only', (int) $request->is_assigned_only);
            }

            if (
                (int) $request->is_assigned_only !== 1 &&
                $request->filled('from_date') &&
                $request->filled('to_date')
            ) {
                $query->whereBetween(
                    'supply_date',
                    [
                        Carbon::parse($request->from_date)->startOfDay(),
                        Carbon::parse($request->to_date)->endOfDay(),
                    ]
                );
            }

            return response()->json([
                'status' => true,
                'data'   => $query->latest()->get(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Supply index error', ['error' => $e->getMessage()]);
            return response()->json(['status' => false], 500);
        }
    }

    // 🔥 CREATE SUPPLY
    public function store(Request $request)
{
    try {
        // 🔥 Normalize employee_id
        if ($request->has('employee.id')) {
            $request->merge([
                'employee_id' => $request->input('employee.id'),
            ]);
        }

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'supply_date' => 'nullable',
            'is_assigned_only' => 'nullable|boolean',
            'items' => 'required|array|min:1',

            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.cow_milk_quantity' => 'nullable|numeric|min:0',
            'items.*.cow_price_per_liter' => 'nullable|numeric|min:0',
            'items.*.buffalo_milk_quantity' => 'nullable|numeric|min:0',
            'items.*.buffalo_price_per_liter' => 'nullable|numeric|min:0',
        ]);

        Log::info('Store supply payload', $data);

        return DB::transaction(function () use ($data) {

            $totalCow = 0;
            $totalBuffalo = 0;
            $totalQty = 0;
            $totalAmount = 0;

            $supply = Supply::create([
                'employee_id' => $data['employee_id'],
                'supply_date' => isset($data['supply_date'])
                    ? Carbon::parse($data['supply_date'])
                    : now(),
                'is_assigned_only' => $data['is_assigned_only'] ?? 0,
            ]);

            foreach ($data['items'] as $item) {

                $cowQty  = (float) ($item['cow_milk_quantity'] ?? 0);
                $cowRate = (float) ($item['cow_price_per_liter'] ?? 0);
                $cowAmt  = $cowQty * $cowRate;

                $bufQty  = (float) ($item['buffalo_milk_quantity'] ?? 0);
                $bufRate = (float) ($item['buffalo_price_per_liter'] ?? 0);
                $bufAmt  = $bufQty * $bufRate;

                SupplyItem::create([
                    'supply_id' => $supply->id,
                    'employee_id' => $data['employee_id'],
                    'customer_id' => $item['customer_id'],

                    'cow_milk_quantity' => $cowQty,
                    'cow_price_per_liter' => $cowRate,
                    'cow_amount' => $cowAmt,

                    'buffalo_milk_quantity' => $bufQty,
                    'buffalo_price_per_liter' => $bufRate,
                    'buffalo_amount' => $bufAmt,

                    'total_quantity' => $cowQty + $bufQty,
                    'total_amount' => $cowAmt + $bufAmt,
                ]);

                $totalCow += $cowQty;
                $totalBuffalo += $bufQty;
                $totalQty += ($cowQty + $bufQty);
                $totalAmount += ($cowAmt + $bufAmt);
            }

            $supply->update([
                'total_cow_milk_quantity' => $totalCow,
                'total_buffalo_milk_quantity' => $totalBuffalo,
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Supply created successfully',
                'data' => $supply->load('items.customer'),
            ], 201);
        });

    } catch (\Throwable $e) {
        Log::error('Supply store error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong',
        ], 500);
    }
}


    // 🔥 UPDATE SUPPLY
    public function update(Request $request)
    {
        try {
               Log::info('update data'.json_encode( $request->all()));

                if ($request->has('employee.id')) {
            $request->merge([
                'employee_id' => $request->input('employee.id'),
            ]);
        }
            $data = $request->validate([
                'supply_id' => 'required|exists:supplies,id',
                'employee_id' => 'required|exists:employees,id',
                'items' => 'required|array|min:1',
                'items.*.customer_id' => 'required|exists:customers,id',
                'items.*.cow_milk_quantity' => 'nullable|numeric|min:0',
                'items.*.cow_price_per_liter' => 'nullable|numeric|min:0',
                'items.*.buffalo_milk_quantity' => 'nullable|numeric|min:0',
                'items.*.buffalo_price_per_liter' => 'nullable|numeric|min:0',
            ]);
         
            return DB::transaction(function () use ($data) {

                $supply = Supply::findOrFail($data['supply_id']);
                $supply->items()->delete();

                $totalCow = $totalBuffalo = $totalQty = $totalAmount = 0;

                foreach ($data['items'] as $item) {

                    $cowQty = $item['cow_milk_quantity'] ?? 0;
                    $cowRate = $item['cow_price_per_liter'] ?? 0;
                    $cowAmt = $cowQty * $cowRate;

                    $bufQty = $item['buffalo_milk_quantity'] ?? 0;
                    $bufRate = $item['buffalo_price_per_liter'] ?? 0;
                    $bufAmt = $bufQty * $bufRate;

                    SupplyItem::create([
                        'supply_id' => $supply->id,
                        'employee_id' => $data['employee_id'],
                        'customer_id' => $item['customer_id'],

                        'cow_milk_quantity' => $cowQty,
                        'cow_price_per_liter' => $cowRate,
                        'cow_amount' => $cowAmt,

                        'buffalo_milk_quantity' => $bufQty,
                        'buffalo_price_per_liter' => $bufRate,
                        'buffalo_amount' => $bufAmt,

                        'total_quantity' => $cowQty + $bufQty,
                        'total_amount' => $cowAmt + $bufAmt,
                    ]);

                    $totalCow += $cowQty;
                    $totalBuffalo += $bufQty;
                    $totalQty += ($cowQty + $bufQty);
                    $totalAmount += ($cowAmt + $bufAmt);
                }

                $supply->update([
                    'total_cow_milk_quantity' => $totalCow,
                    'total_buffalo_milk_quantity' => $totalBuffalo,
                    'total_quantity' => $totalQty,
                    'total_amount' => $totalAmount,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Supply updated successfully',
                ]);
            });

        } catch (\Throwable $e) {
            Log::error('Supply update error', ['error' => $e->getMessage()]);
            return response()->json(['status' => false], 500);
        }
    }

   public function show($id)
{
    try {
        Log::info("Supply details requested", ['supply_id' => $id]);

        $supply = Supply::with(['employee', 'items','items.customer'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Supply details fetched successfully',
            'data' => $supply,
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Supply not found", ['supply_id' => $id]);

        return response()->json([
            'success' => false,
            'message' => 'Supply not found',
        ], 404);

    } catch (\Exception $e) {
        Log::error("Supply fetch failed", [
            'supply_id' => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch supply details',
        ], 500);
    }
}


    // 🔥 MARK DELIVERED
    public function markDelivered(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'supply_item_ids' => 'required|array|min:1',
        ]);

        SupplyItem::where('employee_id', $data['employee_id'])
            ->whereIn('id', $data['supply_item_ids'])
            ->update(['is_delivered' => 1]);

        return response()->json(['status' => true]);
    }
}


