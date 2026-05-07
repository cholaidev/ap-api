<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                'data' => $query->latest()->get(),
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

                $supplyDate = isset($data['supply_date'])
    ? Carbon::parse($data['supply_date'])->startOfDay()
    : now()->startOfDay();

                $existingSupply = Supply::where('employee_id', $data['employee_id'])
                    ->whereDate('supply_date', $supplyDate)
                    ->where('is_assigned_only', 0)
                    ->first();

                $totalCow = 0;
                $totalBuffalo = 0;
                $totalQty = 0;
                $totalAmount = 0;

                if ($existingSupply) {

                    Log::info('♻️ Updating existing supply', [
                        'supply_id' => $existingSupply->id,
                        'employee_id' => $data['employee_id'],
                        'date' => $supplyDate,
                    ]);

                    // 🧹 REMOVE OLD ITEMS
                    $existingSupply->items()->delete();

                    foreach ($data['items'] as $item) {

                        $cowQty = (float) ($item['cow_milk_quantity'] ?? 0);
                        $cowRate = (float) ($item['cow_price_per_liter'] ?? 0);
                        $cowAmt = $cowQty * $cowRate;

                        $bufQty = (float) ($item['buffalo_milk_quantity'] ?? 0);
                        $bufRate = (float) ($item['buffalo_price_per_liter'] ?? 0);
                        $bufAmt = $bufQty * $bufRate;

                        SupplyItem::create([
                            'supply_id' => $existingSupply->id,
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
                            'is_delivered' => 0,
                        ]);

                        $totalCow += $cowQty;
                        $totalBuffalo += $bufQty;
                        $totalQty += ($cowQty + $bufQty);
                        $totalAmount += ($cowAmt + $bufAmt);
                    }

                    $existingSupply->update([
                        'total_cow_milk_quantity' => $totalCow,
                        'total_buffalo_milk_quantity' => $totalBuffalo,
                        'total_quantity' => $totalQty,
                        'total_amount' => $totalAmount,
                    ]);

                    $supply = $existingSupply;

                } else {

                    $supply = Supply::create([
                        'employee_id' => $data['employee_id'],
                        'supply_date' => isset($data['supply_date'])
                            ? Carbon::parse($data['supply_date'])
                            : now(),
                        'is_assigned_only' => $data['is_assigned_only'] ?? 0,
                    ]);

                    foreach ($data['items'] as $item) {

                        $cowQty = (float) ($item['cow_milk_quantity'] ?? 0);
                        $cowRate = (float) ($item['cow_price_per_liter'] ?? 0);
                        $cowAmt = $cowQty * $cowRate;

                        $bufQty = (float) ($item['buffalo_milk_quantity'] ?? 0);
                        $bufRate = (float) ($item['buffalo_price_per_liter'] ?? 0);
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
                        'message' => 'Supply created successfully',
                        'data' => $supply->load('items.customer'),
                    ], 201);
                }
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
            Log::info('update data'.json_encode($request->all()));

            if ($request->has('employee.id')) {
                $request->merge([
                    'employee_id' => $request->input('employee.id'),
                ]);
            }
            $data = $request->validate([
                'supply_id' => 'required|exists:supplies,id',
                'employee_id' => 'required|exists:employees,id',
                'supply_date' => 'nullable',
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
                     'supply_date' => isset($data['supply_date'])
                            ? Carbon::parse($data['supply_date'])
                            : now(),
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
            Log::info('Supply details requested', ['supply_id' => $id]);

            $supply = Supply::with(['employee', 'items', 'items.customer'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Supply details fetched successfully',
                'data' => $supply,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Supply not found', ['supply_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Supply not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Supply fetch failed', [
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
        Log::info('Mark delivered request received', [
            'payload' => $request->all(),
        ]);

        try {
            $data = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'supply_ids' => 'required|array|min:1',
            ]);

            Log::info('Validated mark delivered data', $data);

            $updated = SupplyItem::where('employee_id', $data['employee_id'])
                ->whereIn('id', $data['supply_ids'])
                ->update(['is_delivered' => 1]);

            Log::info('Supply items marked as delivered', [
                'employee_id' => $data['employee_id'],
                'item_ids' => $data['supply_ids'],
                'updated_rows' => $updated,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Items marked as delivered',
                'updated' => $updated,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::warning('Mark delivered validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            Log::error('Mark delivered error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to mark items as delivered',
            ], 500);
        }
    }

   public function assignSupplyDate(Request $request)
{
    try {

        $data = $request->validate([
            'supply_dates' => 'required|array|min:1',
            'supply_dates.*' => 'required|date',
        ]);

        $dates = collect($data['supply_dates'])
            ->map(fn($date) => Carbon::parse($date)->startOfDay());

        return DB::transaction(function () use ($dates) {

            foreach ($dates as $date) {

                Log::info('📅 Supply generation started', [
                    'date' => $date->toDateString(),
                ]);

                // 🔹 GET ALL TEMPLATE SUPPLIES
                $initialSupplies = Supply::where('is_assigned_only', 1)
                    ->with('items')
                    ->get();

                if ($initialSupplies->isEmpty()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'No initial supplies found',
                    ], 422);
                }

                Log::info('♻️ Initial supplies found', [
                    'count' => $initialSupplies->count(),
                ]);

                foreach ($initialSupplies as $templateSupply) {

                    // 🔎 CHECK EXISTING SUPPLY
                    $existingSupply = Supply::where(
                        'employee_id',
                        $templateSupply->employee_id
                    )
                        ->whereDate('supply_date', $date)
                        ->where('is_assigned_only', 0)
                        ->first();

                    $totalCow = 0;
                    $totalBuffalo = 0;
                    $totalQty = 0;
                    $totalAmount = 0;

                    if ($existingSupply) {

                        Log::info('♻️ Updating existing supply', [
                            'supply_id' => $existingSupply->id,
                            'employee_id' => $templateSupply->employee_id,
                            'date' => $date->toDateString(),
                        ]);

                        // 🧹 REMOVE OLD ITEMS
                        $existingSupply->items()->delete();

                        foreach ($templateSupply->items as $item) {

                            $cowQty = (float) $item->cow_milk_quantity;
                            $cowRate = (float) $item->cow_price_per_liter;
                            $cowAmt = $cowQty * $cowRate;

                            $bufQty = (float) $item->buffalo_milk_quantity;
                            $bufRate = (float) $item->buffalo_price_per_liter;
                            $bufAmt = $bufQty * $bufRate;

                            SupplyItem::create([
                                'supply_id' => $existingSupply->id,
                                'employee_id' => $item->employee_id,
                                'customer_id' => $item->customer_id,

                                'cow_milk_quantity' => $cowQty,
                                'cow_price_per_liter' => $cowRate,
                                'cow_amount' => $cowAmt,

                                'buffalo_milk_quantity' => $bufQty,
                                'buffalo_price_per_liter' => $bufRate,
                                'buffalo_amount' => $bufAmt,

                                'total_quantity' => $cowQty + $bufQty,
                                'total_amount' => $cowAmt + $bufAmt,

                                'is_delivered' => 0,
                            ]);

                            $totalCow += $cowQty;
                            $totalBuffalo += $bufQty;
                            $totalQty += ($cowQty + $bufQty);
                            $totalAmount += ($cowAmt + $bufAmt);
                        }

                        $existingSupply->update([
                            'total_cow_milk_quantity' => $totalCow,
                            'total_buffalo_milk_quantity' => $totalBuffalo,
                            'total_quantity' => $totalQty,
                            'total_amount' => $totalAmount,
                        ]);

                    } else {

                        // 🆕 CREATE NEW SUPPLY
                        $newSupply = Supply::create([
                            'employee_id' => $templateSupply->employee_id,
                            'supply_date' => $date,
                            'is_assigned_only' => 0,
                        ]);

                        foreach ($templateSupply->items as $item) {

                            $cowQty = (float) $item->cow_milk_quantity;
                            $cowRate = (float) $item->cow_price_per_liter;
                            $cowAmt = $cowQty * $cowRate;

                            $bufQty = (float) $item->buffalo_milk_quantity;
                            $bufRate = (float) $item->buffalo_price_per_liter;
                            $bufAmt = $bufQty * $bufRate;

                            SupplyItem::create([
                                'supply_id' => $newSupply->id,
                                'employee_id' => $item->employee_id,
                                'customer_id' => $item->customer_id,

                                'cow_milk_quantity' => $cowQty,
                                'cow_price_per_liter' => $cowRate,
                                'cow_amount' => $cowAmt,

                                'buffalo_milk_quantity' => $bufQty,
                                'buffalo_price_per_liter' => $bufRate,
                                'buffalo_amount' => $bufAmt,

                                'total_quantity' => $cowQty + $bufQty,
                                'total_amount' => $cowAmt + $bufAmt,

                                'is_delivered' => 0,
                            ]);

                            $totalCow += $cowQty;
                            $totalBuffalo += $bufQty;
                            $totalQty += ($cowQty + $bufQty);
                            $totalAmount += ($cowAmt + $bufAmt);
                        }

                        $newSupply->update([
                            'total_cow_milk_quantity' => $totalCow,
                            'total_buffalo_milk_quantity' => $totalBuffalo,
                            'total_quantity' => $totalQty,
                            'total_amount' => $totalAmount,
                        ]);

                        Log::info('✅ Supply cloned', [
                            'template_supply_id' => $templateSupply->id,
                            'new_supply_id' => $newSupply->id,
                            'date' => $date->toDateString(),
                        ]);
                    }
                }
            }

            // 🔒 LOCK SETTINGS
            DB::table('settings')->update([
                'today_supply_generated' => true,
                'supply_date' => $dates->last(),
                'updated_at' => now(),
            ]);

            Log::info('🔒 Supply generation completed', [
                'dates' => $dates->map(
                    fn($d) => $d->toDateString()
                ),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Supply generated successfully',
                'dates' => $dates->map(
                    fn($d) => $d->toDateString()
                ),
                'total_dates' => $dates->count(),
            ], 200);
        });

    } catch (\Throwable $e) {

        Log::critical('🔥 Supply generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to generate supply',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function getSupplyItemsByEmployee(Request $request, $employeeId)
    {
        try {
            $query = SupplyItem::with(['customer', 'supply'])
                ->where('employee_id', $employeeId)
                ->where('is_delivered', 0);

            // 🔹 PREDEFINED FILTERS
            if ($request->filled('filter')) {
                $filter = $request->filter;

                if ($filter === 'today') {
                    $query->whereHas('supply', function ($q) {
                        $q->whereDate('supply_date', Carbon::today());
                    });
                }

                if ($filter === 'week') {
                    $query->whereHas('supply', function ($q) {
                        $q->whereBetween(
                            'supply_date',
                            [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]
                        );
                    });
                }

                if ($filter === 'month') {
                    $query->whereHas('supply', function ($q) {
                        $q->whereMonth('supply_date', Carbon::now()->month)
                            ->whereYear('supply_date', Carbon::now()->year);
                    });
                }
            }

            // 🔹 CUSTOM DATE RANGE
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to = Carbon::parse($request->to_date)->endOfDay();

                $query->whereHas('supply', function ($q) use ($from, $to) {
                    $q->whereBetween('supply_date', [$from, $to]);
                });
            }

            $items = $query->latest()->get();

            return response()->json([
                'status' => true,
                'count' => $items->count(),
                'data' => $items,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('❌ Supply item filter failed', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch supply items',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $supply = Supply::findOrFail($id);
        $supply->items()->delete();
        $supply->delete();

        return response()->json([
            'message' => 'Supply deleted successfully',
        ]);
    }
}
