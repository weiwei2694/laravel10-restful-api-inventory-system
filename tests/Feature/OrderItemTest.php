<?php

namespace Tests\Feature;

use App\Helpers\DateHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\OrderItemSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public string $accessTokenAdmin;
    public string $accessTokenUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class
        ]);

        # Access Token User
        $res = $this->post('/api/v1/auth/login', ['email' => 'test@gmail.com', 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $this->accessTokenUser = "Bearer " . $res['data']['tokens']['access'];

        # Access Token Admin
        $res = $this->post('/api/v1/auth/login', ['email' => 'test2@gmail.com', 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $this->accessTokenAdmin = "Bearer " . $res['data']['tokens']['access'];
    }

    # index - 200
    public function testIndexSuccess_200()
    {
        $this->get('/api/v1/order-items', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "current_page",
                    "data" => [
                        [
                            "id",
                            "quantity",
                            "unit_price",
                            "product_id",
                            "order_id",
                            "created_at",
                            "updated_at"
                        ]
                    ],
                    "first_page_url",
                    "from",
                    "last_page",
                    "last_page_url",
                    "links" => [
                        [
                            "url",
                            "label",
                            "active"
                        ]
                    ],
                    "next_page_url",
                    "path",
                    "per_page",
                    "prev_page_url",
                    "to",
                    "total"
                ]
            ])
            ->assertJson([
                "message" => "Get order items successfully.",
                "data" => OrderItem::paginate(10)->toArray()
            ]);
    }

    # index - 401
    public function testIndexAuthorization_401()
    {
        $this->get('/api/v1/order-items', ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # index - 403
    public function testIndexAuthorization_403()
    {
        $this->get('/api/v1/order-items', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # store - 201
    public function testStoreSuccess_201()
    {
        $product = Product::where('name', 'test')->first();
        $order = Order::where('customer_name', 'test')->first();

        $this->post('/api/v1/order-items', [
            "quantity" => 1,
            "product_id" => $product->id,
            "order_id" => $order->id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(201)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "quantity",
                    "unit_price",
                    "product_id",
                    "order_id",
                    "created_at",
                    "updated_at",
                    "id"
                ]
            ])
            ->assertJson([
                "message" => "Order item created successfully.",
                "data" => [
                    "quantity" => 1,
                    "unit_price" => $product->price,
                    "product_id" => $product->id,
                    "order_id" => $order->id
                ]
            ]);
    }

    # store - 422
    public function testStoreValidationError_422()
    {
        $this->post('/api/v1/order-items', [
            "quantity" => "",
            "product_id" => "",
            "order_id" => ""
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "quantity",
                    "product_id",
                    "order_id"
                ]
            ])
            ->assertJson([
                "message" => "The quantity field is required. (and 2 more errors)",
                "errors" => [
                    "quantity" => [
                        "The quantity field is required."
                    ],
                    "product_id" => [
                        "The product id field is required."
                    ],
                    "order_id" => [
                        "The order id field is required."
                    ]
                ]
            ]);
    }

    # store - 401
    public function testStoreAuthorization_401()
    {
        $product = Product::where('name', 'test')->first();
        $order = Order::where('customer_name', 'test')->first();

        $this->post('/api/v1/order-items', [
            "quantity" => 1,
            "product_id" => $product->id,
            "order_id" => $order->id
        ], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # store - 403
    public function testStoreAuthorization_403()
    {
        $product = Product::where('name', 'test')->first();
        $order = Order::where('customer_name', 'test')->first();

        $this->post('/api/v1/order-items', [
            "quantity" => 1,
            "product_id" => $product->id,
            "order_id" => $order->id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # show - 200
    public function testShowSuccess_200()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->get('/api/v1/order-items/' . $orderItem->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "quantity",
                    "unit_price",
                    "product_id",
                    "order_id",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Get order item successfully.",
                "data" => $orderItem->toArray()
            ]);
    }

    # show - 401
    public function testShowAuthorization_401()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->get('/api/v1/order-items/' . $orderItem->id, ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # show - 403
    public function testShowAuthorization_403()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->get('/api/v1/order-items/' . $orderItem->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # show - 404
    public function testShowNotFound_404()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->get('/api/v1/order-items/' . $orderItem->id + 10, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order item not found"]);
    }

    # update - 200
    public function testUpdateSuccess_200()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->put('/api/v1/order-items/' . $orderItem->id, [
            "quantity" => 2
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "quantity",
                    "unit_price",
                    "product_id",
                    "order_id",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Order item updated successfully.",
                "data" => [
                    "id" => $orderItem->id,
                    "quantity" => 2,
                    "unit_price" => $orderItem->unit_price,
                    "product_id" => $orderItem->product_id,
                    "order_id" => $orderItem->order_id,
                    "created_at" => DateHelper::formatDateTime($orderItem->created_at),
                    "updated_at" => DateHelper::formatDateTime($orderItem->updated_at),
                ]
            ]);
    }

    # update - 422
    public function testUpdateValidationError_422()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->put('/api/v1/order-items/' . $orderItem->id, [
            "quantity" => ""
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "quantity"
                ]
            ])
            ->assertJson([
                "message" => "The quantity field is required.",
                "errors" => [
                    "quantity" => ["The quantity field is required."]
                ]
            ]);
    }

    # update - 401
    public function testUpdateAuthorization_401()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->put('/api/v1/order-items/' . $orderItem->id, [
            "quantity" => 2
        ], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # update - 403
    public function testUpdateAuthorization_403()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->put('/api/v1/order-items/' . $orderItem->id, [
            "quantity" => ""
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # update - 404
    public function testUpdateNotFound_404()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->put('/api/v1/order-items/' . $orderItem->id + 10, [
            "quantity" => 2
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order item not found"]);
    }

    # destroy - 204
    public function testDestroySuccess_204()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->delete('/api/v1/order-items/' . $orderItem->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(204);
    }

    # destroy - 401
    public function testDestroyAuthorization_401()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->delete('/api/v1/order-items/' . $orderItem->id, [], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # destroy - 403
    public function testDestroyAuthorization_403()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->delete('/api/v1/order-items/' . $orderItem->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # destroy - 404
    public function testDestroyNotFound_404()
    {
        $orderItem = OrderItem::where('quantity', 1)->first();

        $this->delete('/api/v1/order-items/' . $orderItem->id + 10, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order item not found"]);
    }
}
