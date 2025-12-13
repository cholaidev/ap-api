<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\SupplyItem;
use Illuminate\Http\Request;
use DB;

class SupplyController extends Controller
{
    /**
     * Supply List
     */
    public function index()
    {
        return Supply::with(['employee', 'items.customer'])
            ->latest()
            ->get();
    }

    /**
     * Create Supply
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'supply_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.milk' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.price_per_liter' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            $supply = Supply::create([
                'employee_id' => $data['employee_id'],
                'supply_date' => $data['supply_date'] ?? now(),
            ]);

            $totalQty = 0;
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 0;
                $price = $item['price_per_liter'] ?? 0;
                $amount = $qty * $price;

                SupplyItem::create([
                    'supply_id' => $supply->id,
                    'customer_id' => $item['customer_id'],
                    'milk' => $item['milk'] ?? null,
                    'quantity' => $qty,
                    'price_per_liter' => $price,
                    'amount' => $amount,
                ]);

                $totalQty += $qty;
                $totalAmount += $amount;
            }

            $supply->update([
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
            ]);

            return response()->json([
                'message' => 'Supply created successfully',
                'supply' => $supply->load('items.customer'),
            ], 201);
        });
    }

    /**
     * Supply Details
     */
    public function show(Supply $supply)
    {
        return $supply->load(['employee', 'items.customer']);
    }

    /**
     * Update Supply (edit customers, quantities, employee)
     */
    public function update(Request $request, Supply $supply)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'is_supplied' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.milk' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.price_per_liter' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($supply, $data) {

            $supply->items()->delete();

            $totalQty = 0;
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 0;
                $price = $item['price_per_liter'] ?? 0;
                $amount = $qty * $price;

                SupplyItem::create([
                    'supply_id' => $supply->id,
                    'customer_id' => $item['customer_id'],
                    'milk' => $item['milk'],
                    'quantity' => $qty,
                    'price_per_liter' => $price,
                    'amount' => $amount,
                ]);

                $totalQty += $qty;
                $totalAmount += $amount;
            }

            $supply->update([
                'employee_id' => $data['employee_id'],
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
                'is_supplied' => $data['is_supplied'] ?? false,
            ]);

            return response()->json([
                'message' => 'Supply updated successfully',
            ]);
        });
    }

    /**
     * Delete Supply
     */
    public function destroy(Supply $supply)
    {
        $supply->delete();

        return response()->json([
            'message' => 'Supply deleted successfully',
        ]);
    }
}
