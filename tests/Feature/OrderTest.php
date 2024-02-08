<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\OrderItemSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseMigrations; # it is more recommended to use RefreshDatabase instead of DatabaseMigrations, but in my case, I had a problem in a particular case, which required me to use this

    public string $accessTokenAdmin;
    public string $accessTokenAdminV2;
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

        # Access Token Admin V2
        $admin = User::factory()->create(['role' => 2]);
        $res = $this->post('/api/v1/auth/login', ['email' => $admin->email, 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $this->accessTokenAdminV2 = "Bearer " . $res['data']['tokens']['access'];
    }

    # index - 200
    public function testIndexSuccess_200()
    {
        $this->get('/api/v1/orders', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "current_page",
                    "data" => [
                        [
                            "id",
                            "date",
                            "total_price",
                            "customer_name",
                            "customer_email",
                            "user_id",
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
                "message" => "Get orders successfully.",
                "data" => Order::paginate(10)->toArray()
            ]);
    }

    # index - 401
    public function testIndexAuthorization_401()
    {
        $this->get('/api/v1/orders', ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # index - 403
    public function testIndexAuthorization_403()
    {
        $this->get('/api/v1/orders', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # store - 201
    public function testStoreSuccess_201()
    {
        $this->post('/api/v1/orders', [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "string",
            "customer_email" => "string@gmail.com",
        ], ["Accept" => "application/json", "Authorization" => $this->accessTokenAdmin])
            ->assertStatus(201)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "date",
                    "customer_name",
                    "customer_email",
                    "user_id",
                    "created_at",
                    "updated_at",
                    "id"
                ]
            ])
            ->assertJson([
                "message" => "Order created successfully.",
                "data" => [
                    "date" => "2024-02-10T02:08:55",
                    "customer_name" => "string",
                    "customer_email" => "string@gmail.com",
                    "user_id" => User::where('role', 2)->first()->id,
                    "id" => Order::where('customer_name', 'test')->first()->id + 1
                ]
            ]);
    }

    # store - 422
    public function testStoreValidationError_422()
    {
        $this->post('/api/v1/orders', [
            "date" => "",
            "customer_name" => "",
            "customer_email" => "",
        ], ["Accept" => "application/json", "Authorization" => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "date",
                    "customer_name",
                    "customer_email"
                ]
            ])
            ->assertJson([
                "message" => "The date field is required. (and 2 more errors)",
                "errors" => [
                    "date" => ["The date field is required."],
                    "customer_name" => ["The customer name field is required."],
                    "customer_email" => ["The customer email field is required."],
                ]
            ]);
    }

    # store - 401
    public function testStoreAuthorization_401()
    {
        $this->post('/api/v1/orders', [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "test create customer name",
            "customer_email" => "testcustomergmail@gmail.com",
        ], ["Accept" => "application/json", "Authorization" => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # store - 403
    public function testStoreAuthorization_403()
    {
        $this->post('/api/v1/orders', [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "test create customer name",
            "customer_email" => "testcustomergmail@gmail.com",
        ], ["Accept" => "application/json", "Authorization" => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # show - 200
    public function testShowSuccess_200()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "date",
                    "total_price",
                    "customer_name",
                    "customer_email",
                    "user_id",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Get order successfully.",
                "data" => $order->toArray()
            ]);
    }

    # show - 401
    public function testShowAuthorization_401()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id, ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # show - 403
    public function testShowAuthorization_403()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # show - 404
    public function testShowNotFound_404()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id + 10, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order not found"]);
    }

    # update - 200
    public function testUpdateSuccess_200()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->put('/api/v1/orders/' . $order->id, [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "update test",
            "customer_email" => "updatetest@gmail.com",
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "date",
                    "customer_name",
                    "customer_email",
                    "user_id",
                    "created_at",
                    "updated_at",
                ]
            ])
            ->assertJson([
                "message" => "Order updated successfully.",
                "data" => [
                    "id" => $order->id,
                    "date" => "2024-02-10T02:08:55",
                    "customer_name" => "update test",
                    "customer_email" => "updatetest@gmail.com",
                    "user_id" => $order->user_id,
                ]
            ]);
    }

    # update - 422
    public function testUpdateValidationError_422()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->put('/api/v1/orders/' . $order->id, [
            "date" => "",
            "customer_name" => "",
            "customer_email" => "",
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "date",
                    "customer_name",
                    "customer_email",
                ]
            ])
            ->assertJson([
                "message" => "The date field is required. (and 2 more errors)",
                "errors" => [
                    "date" => [
                        "The date field is required."
                    ],
                    "customer_name" => [
                        "The customer name field is required."
                    ],
                    "customer_email" => [
                        "The customer email field is required."
                    ]
                ]
            ]);
    }

    # update - 401
    public function testUpdateAuthorization_401()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->put('/api/v1/orders/' . $order->id, [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "update test",
            "customer_email" => "updatetest@gmail.com",
        ], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # update - 403
    public function testUpdateAuthorization_403()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->put('/api/v1/orders/' . $order->id, [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "update test",
            "customer_email" => "updatetest@gmail.com",
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # update - 403
    public function testUpdateCannotUpdateAnotherUserOrder_403()
    {
        # this order, user_id points to the data $this->accessTokenAdmin
        $order = Order::where('customer_name', 'test')->first();

        # we authenticate using $this->accessTokenAdminV2
        $this->put('/api/v1/orders/' . $order->id, [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "update test",
            "customer_email" => "updatetest@gmail.com",
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdminV2])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # update - 403
    public function testUpdateNotFound_404()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->put('/api/v1/orders/' . $order->id + 10, [
            "date" => "2024-02-10T02:08:55",
            "customer_name" => "update test",
            "customer_email" => "updatetest@gmail.com",
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order not found"]);
    }

    # delete - 204
    public function testDeleteSuccess_204()
    {
        $order = Order::where('customer_name', 'test')->first();
        $this->delete('/api/v1/orders/' . $order->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(204);
    }

    # delete - 401
    public function testDeleteAuthorization_401()
    {
        $order = Order::where('customer_name', 'test')->first();
        $this->delete('/api/v1/orders/' . $order->id, [], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # delete - 403
    public function testDeleteAuthorization_403()
    {
        $order = Order::where('customer_name', 'test')->first();
        $this->delete('/api/v1/orders/' . $order->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 403
    public function testDeleteCannotDeleteAnotherUserOrder_403()
    {
        # this order, user_id points to the data $this->accessTokenAdmin
        $order = Order::where('customer_name', 'test')->first();

        # we authenticate using $this->accessTokenAdminV2
        $this->delete('/api/v1/orders/' . $order->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdminV2])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 404
    public function testDeleteNotFound_404()
    {
        $order = Order::where('customer_name', 'test')->first();
        $this->delete('/api/v1/orders/' . $order->id + 10, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order not found"]);
    }

    # orderItems - 200
    public function testOrderItemsSuccess_200()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id . '/order-items', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
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
                            "product" => [
                                "id",
                                "name",
                                "description",
                                "price",
                                "quantity_in_stock",
                                "category_id",
                                "user_id",
                                "created_at",
                                "updated_at"
                            ],
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
                "data" => OrderItem::with('product')
                    ->where('order_id', $order->id)
                    ->paginate(10)
                    ->toArray()
            ]);
    }

    # orderItems - 401
    public function testOrderItemsAuthorization_401()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id . '/order-items', ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # orderItems - 403
    public function testOrderItemsAuthorization_403()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id . '/order-items', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # orderItems - 404
    public function testOrderItemsAuthorization_404()
    {
        $order = Order::where('customer_name', 'test')->first();

        $this->get('/api/v1/orders/' . $order->id + 10 . '/order-items', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Order not found"]);
    }
}
