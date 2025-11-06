<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string|in:liter,kg,packet,unit',
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
        ]);

        $product = Product::create($data);
        return response()->json(['message' => 'Product created', 'data' => $product]);
    }

    public function show($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->only('name', 'type', 'cost_price', 'selling_price'));
        return response()->json(['message' => 'Product updated', 'data' => $product]);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
