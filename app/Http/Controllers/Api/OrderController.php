<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $orders = Order::paginate(10);

        return response()
            ->json([
                "message" => "Get orders successfully.",
                "data" => $orders
            ])
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        request()->validate([
            "date" => "required|date",
            "customer_name" => "required",
            "customer_email" => "required",
        ]);

        $order = new Order();
        $order->date = request()->input('date');
        $order->customer_name = request()->input('customer_name');
        $order->customer_email = request()->input('customer_email');
        $order->user_id = auth()->id();
        $order->save();

        return response()
            ->json([
                "message" => "Order created successfully.",
                "data" => $order
            ])
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()
                ->json(['message' => 'Order not found'])
                ->setStatusCode(404);
        }

        return response()
            ->json([
                "message" => "Get order successfully.",
                "data" => $order
            ])
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()
                ->json(['message' => 'Order not found'])
                ->setStatusCode(404);
        }

        if ($order->user_id !== auth()->id()) {
            return response()
                ->json(["message" => "Forbidden"])
                ->setStatusCode(403);
        }

        request()->validate([
            "date" => "required|date",
            "customer_name" => "required",
            "customer_email" => "required",
        ]);

        $order->date = request()->input('date');
        $order->customer_name = request()->input('customer_name');
        $order->customer_email = request()->input('customer_email');
        $order->save();

        return response()
            ->json([
                "message" => "Order updated successfully.",
                "data" => $order
            ])
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()
                ->json(['message' => 'Order not found'])
                ->setStatusCode(404);
        }

        if ($order->user_id !== auth()->id()) {
            return response()
                ->json(["message" => "Forbidden"])
                ->setStatusCode(403);
        }

        $order->delete();

        return response()
            ->json([])
            ->setStatusCode(204);
    }

    public function orderItems(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()
                ->json(['message' => 'Order not found'])
                ->setStatusCode(404);
        }

        $orderItems = OrderItem::with('product')->where('order_id', $order->id)->paginate(10);

        return response()
            ->json([
                "message" => "Get order items successfully.",
                "data" => $orderItems
            ])
            ->setStatusCode(200);
    }
}
