<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = Product::paginate(10);

        return response()
            ->json([
                "message" => "Get products successfully.",
                "data" => $products
            ])
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        request()->validate([
            "name" => "required|max:100",
            "description" => "required",
            "price" => "required|numeric|min:1",
            "quantity_in_stock" => "required|integer",
            "category_id" => "required|exists:categories,id"
        ]);

        $product = new Product();
        $product->name = request()->input('name');
        $product->description = request()->input('description');
        $product->price = request()->input('price');
        $product->quantity_in_stock = request()->input('quantity_in_stock');
        $product->category_id = request()->input('category_id');
        $product->user_id = auth()->id();
        $product->save();

        return response()
            ->json([
                "message" => "Product created successfully.",
                "data" => $product
            ])
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()
                ->json(['message' => 'Product not found'])
                ->setStatusCode(404);
        }

        return response()
            ->json([
                "message" => "Get product successfully.",
                "data" => $product
            ])
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()
                ->json(['message' => 'Product not found'])
                ->setStatusCode(404);
        }

        if ($product->user_id !== auth()->id()) {
            return response()
                ->json(['message' => 'Forbidden'])
                ->setStatusCode(403);
        }

        request()->validate([
            "name" => "required|max:100",
            "description" => "required",
            "price" => "required|numeric|min:1",
            "quantity_in_stock" => "required|integer",
            "category_id" => "required|exists:categories,id"
        ]);

        $product->name = request()->input('name');
        $product->description = request()->input('description');
        $product->price = request()->input('price');
        $product->quantity_in_stock = request()->input('quantity_in_stock');
        $product->category_id = request()->input('category_id');
        $product->save();

        return response()
            ->json([
                "message" => "Product updated successfully.",
                "data" => $product
            ])
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()
                ->json(['message' => 'Product not found'])
                ->setStatusCode(404);
        }

        if ($product->user_id !== auth()->id()) {
            return response()
                ->json(['message' => 'Forbidden'])
                ->setStatusCode(403);
        }

        $product->delete();

        return response()
            ->json([])
            ->setStatusCode(204);
    }
}
