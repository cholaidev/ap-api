<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index()
    {
        return response()->json(Delivery::with(['customer', 'product', 'staff'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'staff_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.1',
            'unit_price' => 'required|numeric|min:0',
            'date' => 'required|date',
            'status' => 'nullable|string|in:pending,delivered',
        ]);

        $data['total'] = $data['quantity'] * $data['unit_price'];
        $delivery = Delivery::create($data);
        return response()->json(['message' => 'Delivery added', 'data' => $delivery]);
    }

    public function show($id)
    {
        return response()->json(Delivery::with(['customer', 'product', 'staff'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);
        $data = $request->validate([
            'quantity' => 'sometimes|numeric|min:0.1',
            'unit_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:pending,delivered',
        ]);

        if (isset($data['quantity']) || isset($data['unit_price'])) {
            $quantity = $data['quantity'] ?? $delivery->quantity;
            $unit_price = $data['unit_price'] ?? $delivery->unit_price;
            $data['total'] = $quantity * $unit_price;
        }

        $delivery->update($data);
        return response()->json(['message' => 'Delivery updated', 'data' => $delivery]);
    }

    public function destroy($id)
    {
        Delivery::findOrFail($id)->delete();
        return response()->json(['message' => 'Delivery deleted']);
    }
}
