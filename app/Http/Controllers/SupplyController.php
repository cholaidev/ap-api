<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyItem; 
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use DB;

class SupplyController extends Controller
{
    /**
     * Supply List
     */
    public function index()
{
    try {
        Log::info('Supply list fetched');

        $supplies = Supply::with(['employee', 'items.customer'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Supplies fetched successfully',
            'data' => $supplies,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Supply fetch failed', [
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

        $supply = Supply::with(['employee', 'items.customer'])
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
}
