<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::where('name', 'test')->first();
        $order = Order::where('customer_name', 'test')->first();

        OrderItem::create([
            'quantity' => 1,
            'unit_price' => $product->price,
            'order_id' => $order->id,
            'product_id' => $order->id,
        ]);

        $product->quantity_in_stock -= 1;
        $product->save();

        $order->total_price += $product->price * 1;
        $order->save();
    }
}
