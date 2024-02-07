<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::where('name', 'test')->first();
        $user = User::where('role', 1)->first();

        Product::create([
            'name' => 'test',
            'description' => 'test',
            'price' => 10,
            'quantity_in_stock' => 10,
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);
    }
}
