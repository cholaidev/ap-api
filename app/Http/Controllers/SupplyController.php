<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyItem; 
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class SupplyController extends Controller
{
    /**
     * Supply List
     */


public function index(Request $request)
{
    try {
        Log::info('📦 Supply list request received', [
            'from_date'        => $request->from_date,
            'to_date'          => $request->to_date,
            'is_assigned_only' => $request->is_assigned_only,
        ]);

        $query = Supply::with(['employee', 'items.customer']);

        /**
         * 🔹 FILTER BY ASSIGNED / TEMPLATE
         * is_assigned_only = 1 → TEMPLATE supplies
         * is_assigned_only = 0 → GENERATED supplies
         */
        if ($request->has('is_assigned_only')) {
            $query->where(
                'is_assigned_only',
                (int) $request->is_assigned_only
            );

            Log::info('🔐 is_assigned_only filter applied', [
                'value' => (int) $request->is_assigned_only,
            ]);
        }

        /**
         * 🔹 DATE RANGE FILTER
         */
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('supply_date', [$from, $to]);
        }

        $supplies = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Supplies fetched successfully',
            'data'    => $supplies,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('❌ Supply fetch failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch supplies',
        ], 500);
    }
}




    /**
     * Create Supply
     */
  public function store(Request $request)
{
    try {
        
        Log::info('Supply Store Input Data', $request->all());

      
        if ($request->has('employee.id')) {
            $request->merge([
                'employee_id' => $request->input('employee.id'),
            ]);
        }

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'supply_date' => 'nullable|date',
            'total_quantity' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
             'is_assigned_only' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.milk' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.price_per_liter' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            // 🔹 Create supply
            $supply = Supply::create([
                'employee_id'   => $data['employee_id'],
                'supply_date'   => $data['supply_date'] ?? now(),
                'total_quantity'=> $data['total_quantity'],
                'total_amount'  => $data['total_amount'],
                'is_assigned_only' => $data['is_assigned_only']
            ]);

            // 🔹 Create supply items
            foreach ($data['items'] as $item) {
                $qty    = $item['quantity'] ?? 0;
                $price = $item['price_per_liter'] ?? 0;

                SupplyItem::create([
                    'supply_id'       => $supply->id,
                    'employee_id'   => $data['employee_id'],
                    'customer_id'     => $item['customer_id'],
                    'milk'            => $item['milk'] ?? null,
                    'quantity'        => $qty,
                    'price_per_liter' => $price,
                    'amount'          => $qty * $price,
                ]);
            }

            Log::info('Supply Created Successfully', [
                'supply_id' => $supply->id,
            ]);

            return response()->json([
                'message' => 'Supply created successfully',
                'supply'  => $supply->load('items.customer'),
            ], 201);
        });

    } catch (\Illuminate\Validation\ValidationException $e) {

        Log::error('Supply Validation Error', [
            'errors' => $e->errors(),
            'input'  => $request->all(),
        ]);

        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        Log::critical('Supply Store Exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
            'input'   => $request->all(),
        ]);

        return response()->json([
            'message' => 'Something went wrong while creating supply',
        ], 500);
    }
}
    /**
     * Supply Details
     */
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

    /**
     * Update Supply (edit customers, quantities, employee)
     */
   

public function update(Request $request)
{
    try {

        Log::info('Supply Update Input Data', $request->all());

        // 🔹 Map employee.id → employee_id
        if ($request->has('employee.id')) {
            $request->merge([
                'employee_id' => $request->input('employee.id'),
            ]);
        }

        // 🔹 Validate input
        $data = $request->validate([
            'supply_id'   => 'required|exists:supplies,id',
            'employee_id' => 'required|exists:employees,id',
            'supply_date' => 'nullable|date',
            'is_assigned_only' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.milk' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.price_per_liter' => 'nullable|numeric|min:0',
        ]);
        Log::info('inputs data '.json_encode($data));
        // 🔹 Fetch supply from payload ID
        $supply = Supply::findOrFail($data['supply_id']);

        DB::beginTransaction();

        Log::info('Supply update started', [
            'supply_id' => $supply->id,
        ]);

        // 🔥 Delete old items
        $supply->items()->delete();

        $totalQty = 0;
        $totalAmount = 0;

        // 🔹 Recreate items
        foreach ($data['items'] as $item) {

            $qty   = $item['quantity'] ?? 0;
            $price = $item['price_per_liter'] ?? 0;
            $amount = $qty * $price;

            SupplyItem::create([
                'supply_id'       => $supply->id,
                'employee_id'    =>  $data['employee_id'],
                'customer_id'     => $item['customer_id'],
                'milk'            => $item['milk'] ?? null,
                'quantity'        => $qty,
                'price_per_liter' => $price,
                'amount'          => $amount,
            ]);

            $totalQty += $qty;
            $totalAmount += $amount;
        }

        // 🔄 Update supply master
        $supply->update([
            'employee_id'    => $data['employee_id'],
            'supply_date'    => $data['supply_date'] ?? $supply->supply_date,
            'total_quantity' => $totalQty,
            'total_amount'   => $totalAmount,
        ]);

        DB::commit();

        Log::info('Supply updated successfully', [
            'supply_id' => $supply->id,
        ]);

        return response()->json([
            'message' => 'Supply updated successfully',
            'supply'  => $supply->load('items.customer'),
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        DB::rollBack();

        Log::error('Supply Update Validation Error', [
            'errors' => $e->errors(),
            'input'  => $request->all(),
        ]);

        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::critical('Supply Update Exception', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'input'   => $request->all(),
        ]);

        return response()->json([
            'message' => 'Something went wrong while updating supply',
        ], 500);
    }
}
    /**
     * Delete Supply
     */
    public function destroy($id)
    {
        $supply = Supply::findOrFail($id);
        $supply->delete();

        return response()->json([
            'message' => 'Supply deleted successfully',
        ]);
    }

  public function markDelivered(Request $request)
{
    try {
        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'supply_ids'      => 'required|array|min:1',
            'supply_ids.*'    => 'exists:supplies,id',
        ]);

        Log::info('🚚 Mark delivery request', $data);

        $updated = SupplyItem::where('employee_id', $data['employee_id'])
            ->whereIn('supply_id', $data['supply_ids'])
            ->update([
                'is_delivered' => 1,
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Supply marked as delivered',
            'updated_items' => $updated,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('❌ Mark delivery failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to mark supply delivered',
        ], 500);
    }
}




public function assignSupplyDate(Request $request)
{
    try {
        $data = $request->validate([
            'supply_date' => 'required|date',
        ]);

        $date = Carbon::parse($data['supply_date'])->startOfDay();

        Log::info('📅 Supply generation started', [
            'date' => $date->toDateString(),
        ]);

        return DB::transaction(function () use ($date) {

            // 🔒 STOP if already generated for this date
         

            // 🔹 GET ALL INITIAL (TEMPLATE) SUPPLIES
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

            // 🔁 CLONE EACH SUPPLY
            foreach ($initialSupplies as $templateSupply) {

                 $existingSupply = Supply::where('employee_id', $templateSupply->employee_id)
        ->whereDate('supply_date', $date)
        ->where('is_assigned_only', 0)
        ->first();

    if ($existingSupply) {
        // 🔄 UPDATE EXISTING SUPPLY
        Log::info('♻️ Updating existing supply', [
            'supply_id'  => $existingSupply->id,
            'employee_id'=> $templateSupply->employee_id,
            'date'       => $date->toDateString(),
        ]);

        // 🧹 Remove old items
        $existingSupply->items()->delete();

        // 🔁 Recreate items
        foreach ($templateSupply->items as $item) {
            SupplyItem::create([
                'supply_id'       => $existingSupply->id,
                'employee_id'     => $item->employee_id,
                'customer_id'     => $item->customer_id,
                'milk'            => $item->milk,
                'quantity'        => $item->quantity,
                'price_per_liter' => $item->price_per_liter,
                'amount'          => $item->amount,
                'is_delivered'    => 0,
            ]);
        }

        // 🔄 Update totals
        $existingSupply->update([
            'total_quantity' => $templateSupply->total_quantity,
            'total_amount'   => $templateSupply->total_amount,
        ]);

           }else{
                

                // 🆕 CREATE NEW SUPPLY
                $newSupply = Supply::create([
                    'employee_id'    => $templateSupply->employee_id,
                    'supply_date'    => $date,
                    'total_quantity' => $templateSupply->total_quantity,
                    'total_amount'   => $templateSupply->total_amount,
                    'is_assigned_only'    => 0,
                ]);

                // 🔁 CLONE SUPPLY ITEMS
                foreach ($templateSupply->items as $item) {
                    SupplyItem::create([
                        'supply_id'       => $newSupply->id,
                        'employee_id'     => $item->employee_id,
                        'customer_id'     => $item->customer_id,
                        'milk'            => $item->milk,
                        'quantity'        => $item->quantity,
                        'price_per_liter' => $item->price_per_liter,
                        'amount'          => $item->amount,
                        'is_delivered'    => 0,
                    ]);
                }

                Log::info('✅ Supply cloned', [
                    'template_supply_id' => $templateSupply->id,
                    'new_supply_id'      => $newSupply->id,
                ]);
            }
        }
            // 🔒 LOCK UI (SETTINGS)
            DB::table('settings')->update([
                'today_supply_generated' => true,
                'supply_date'            => $date,
                'updated_at'             => now(),
            ]);

            Log::info('🔒 Supply generation completed', [
                'date' => $date->toDateString(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Supply generated successfully',
                'date'    => $date->toDateString(),
                'supplies_created' => $initialSupplies->count(),
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
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereHas('supply', function ($q) use ($from, $to) {
                $q->whereBetween('supply_date', [$from, $to]);
            });
        }

        $items = $query->latest()->get();

        return response()->json([
            'status' => true,
            'count'  => $items->count(),
            'data'   => $items,
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



}
