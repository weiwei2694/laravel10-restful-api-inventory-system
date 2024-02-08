<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $orderItems = OrderItem::paginate(10);

        return response()
            ->json([
                "message" => "Get order items successfully.",
                "data" => $orderItems
            ])
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        DB::beginTransaction();

        request()->validate([
            "quantity" => "required|integer|min:1",
            "product_id" => "required|exists:products,id",
            "order_id" => "required|exists:orders,id"
        ]);

        try {
            $product = Product::find(request()->input('product_id'));

            if (request()->input('quantity') > $product->quantity_in_stock) {
                return response()
                    ->json([
                        "message" => "Insufficient stock",
                        "errors" => [
                            "quantity" => ["Insufficient stock"]
                        ]
                    ])
                    ->setStatusCode(400);
            }

            $orderItem = new OrderItem();
            $orderItem->quantity = request()->input('quantity');
            $orderItem->unit_price = $product->price;
            $orderItem->product_id = request()->input('product_id');
            $orderItem->order_id = request()->input('order_id');
            $orderItem->save();

            $product->quantity_in_stock -= $orderItem->quantity;
            $product->save();

            $order = Order::find($orderItem->order_id);
            $order->total_price += $product->price * $orderItem->quantity;
            $order->save();

            DB::commit();

            return response()
                ->json([
                    "message" => "Order item created successfully.",
                    "data" => $orderItem
                ])
                ->setStatusCode(201);
        } catch (\Exception $exception) {
            DB::rollBack();

            $errorMessage = 'An error occurred while processing your request.';

            if ($exception->getMessage()) {
                $errorMessage = $exception->getMessage();
            }

            return response()
                ->json([
                    'message' => $errorMessage
                ])
                ->setStatusCode(500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $orderItem = OrderItem::find($id);

        if (!$orderItem) {
            return response()
                ->json(['message' => 'Order item not found'])
                ->setStatusCode(404);
        }

        return response()
            ->json([
                "message" => "Get order item successfully.",
                "data" => $orderItem
            ])
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id): JsonResponse
    {
        DB::beginTransaction();

        request()->validate([
            "quantity" => "required|integer|min:1",
        ]);

        try {
            $orderItem = OrderItem::find($id);

            if (!$orderItem) {
                return response()
                    ->json(['message' => 'Order item not found'])
                    ->setStatusCode(404);
            }

            $order = Order::find($orderItem->order_id);
            $product = Product::find($orderItem->product_id);

            $newQuantity = request()->input('quantity');
            $previousQuantity = $orderItem->quantity;
            $quantityDifference = $newQuantity - $previousQuantity;

            if ($newQuantity > ($product->quantity_in_stock + $previousQuantity)) {
                return redirect()
                    ->json([
                        "message" => "The quantity is greater than the quantity in stock.",
                        "errors" => [
                            "quantity" => ["The quantity is greater than the quantity in stock."]
                        ]
                    ])
                    ->setStatusCode();
            }

            $product->quantity_in_stock -= $quantityDifference;
            $product->save();

            $totalPriceCurrentOrderItem = $orderItem->unit_price * $previousQuantity;
            $totalPriceProduct = $product->price * $newQuantity;
            $totalPrice = $order->total_price - $totalPriceCurrentOrderItem;
            $order->total_price = $totalPrice + $totalPriceProduct;
            $order->save();

            $orderItem->quantity = $newQuantity;
            $orderItem->save();

            DB::commit();

            return response()
                ->json([
                    "message" => "Order item updated successfully.",
                    "data" => $orderItem
                ])
                ->setStatusCode(200);
        } catch (\Exception $exception) {
            DB::rollBack();

            $errorMessage = 'An error occurred while processing your request.';

            if ($exception->getMessage()) {
                $errorMessage = $exception->getMessage();
            }

            return response()
                ->json([
                    'message' => $errorMessage
                ])
                ->setStatusCode(500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $orderItem = OrderItem::find($id);

            if (!$orderItem) {
                return response()
                    ->json(['message' => 'Order item not found'])
                    ->setStatusCode(404);
            }

            $totalPriceOrderItem = $orderItem->quantity * $orderItem->unit_price;

            $order = Order::find($orderItem->order_id);
            $order->total_price = $order->total_price - $totalPriceOrderItem;
            $order->save();

            $product = Product::find($orderItem->product_id);
            $product->quantity_in_stock = $orderItem->quantity + $product->quantity_in_stock;
            $product->save();

            $orderItem->delete();

            DB::commit();

            return response()
                ->json([])
                ->setStatusCode(204);
        } catch (\Exception $exception) {
            DB::rollBack();

            $errorMessage = 'An error occurred while processing your request.';

            if ($exception->getMessage()) {
                $errorMessage = $exception->getMessage();
            }

            return response()
                ->json([
                    'message' => $errorMessage
                ])
                ->setStatusCode(500);
        }
    }
}
