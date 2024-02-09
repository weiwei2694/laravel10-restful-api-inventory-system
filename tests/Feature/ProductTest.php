<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public string $accessTokenAdmin;
    public string $accessTokenUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class, ProductSeeder::class]);

        $res = $this->post('/api/v1/auth/login', ['email' => 'test@gmail.com', 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $this->accessTokenUser = "Bearer " . $res['data']['tokens']['access'];

        $res = $this->post('/api/v1/auth/login', ['email' => 'test2@gmail.com', 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $this->accessTokenAdmin = "Bearer " . $res['data']['tokens']['access'];
    }

    # index - 200
    public function testIndexSuccess_200()
    {
        $this->get('/api/v1/products', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "current_page",
                    "data" => [
                        [
                            "id",
                            "name",
                            "description",
                            "price",
                            "quantity_in_stock",
                            "category_id",
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
                "message" => "Get products successfully.",
                "data" => Product::paginate(10)->toArray()
            ]);
    }

    # index - 401
    public function testIndexAuthorization_401()
    {
        $this->get('/api/v1/products', ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # store - 201
    public function testStoreSuccess_201()
    {
        $category = Category::where('name', 'test')->first();

        $this->post('/api/v1/products/', [
            "name" => "test create product",
            "description" => "description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => $category->id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(201)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "name",
                    "description",
                    "price",
                    "quantity_in_stock",
                    "category_id",
                    "user_id",
                    "updated_at",
                    "created_at",
                    "id"
                ]
            ])
            ->assertJson([
                "message" => "Product created successfully.",
                "data" => [
                    "name" => "test create product",
                    "description" => "description product",
                    "price" => 100,
                    "quantity_in_stock" => 10,
                    "category_id" => $category->id,
                    "user_id" => User::where('role', 2)->first()->id,
                    "id" => Product::where('name', 'test')->first()->id + 1
                ]
            ]);
    }

    # store - 401
    public function testStoreAuthorization_401()
    {
        $this->post('/api/v1/products', [
            "name" => "test create product",
            "description" => "description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => Category::where('name', 'test')->first()->id
        ], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # store - 422
    public function testStoreValidationError_422()
    {
        $this->post('/api/v1/products', [
            "name" => "",
            "description" => "",
            "price" => "",
            "quantity_in_stock" => "",
            "category_id" => ""
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "name",
                    "description",
                    "price",
                    "quantity_in_stock",
                    "category_id",
                ]
            ])
            ->assertJson([
                "message" => "The name field is required. (and 4 more errors)",
                "errors" => [
                    "name" => [
                        "The name field is required."
                    ],
                    "description" => [
                        "The description field is required."
                    ],
                    "price" => [
                        "The price field is required."
                    ],
                    "quantity_in_stock" => [
                        "The quantity in stock field is required."
                    ],
                    "category_id" => [
                        "The category id field is required."
                    ]
                ]
            ]);
    }

    # show - 200
    public function testShowSuccess_200()
    {
        $product = Product::where('name', 'test')->first();

        $this->get('/api/v1/products/' . $product->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "description",
                    "price",
                    "quantity_in_stock",
                    "category_id",
                    "user_id",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Get product successfully.",
                "data" => $product->toArray()
            ]);
    }

    # show - 401
    public function testShowAuthorization_401()
    {
        $product = Product::where('name', 'test')->first();

        $this->get('/api/v1/products/' . $product->id, ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Unauthenticated."
            ]);
    }

    # show - 404
    public function testShowNotFound_404()
    {
        $product = Product::where('name', 'test')->first();

        $this->get('/api/v1/products/' . $product->id + 10, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Product not found"
            ]);
    }

    # update - 200
    public function testUpdateSuccess_200()
    {
        $product = Product::where('name', 'test')->first();

        $this->put('/api/v1/products/' . $product->id, [
            "name" => "update product",
            "description" => "update description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => $product->category_id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "description",
                    "price",
                    "quantity_in_stock",
                    "category_id",
                    "user_id",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Product updated successfully.",
                "data" => [
                    "name" => "update product",
                    "description" => "update description product",
                    "price" => 100,
                    "quantity_in_stock" => 10,
                    "category_id" => $product->category_id,
                    "user_id" => $product->user_id,
                ]
            ]);
    }

    # update - 422
    public function testUpdateValidationError_422()
    {
        $product = Product::where('name', 'test')->first();

        $this->put('/api/v1/products/' . $product->id, [
            "name" => "",
            "description" => "",
            "price" => "",
            "quantity_in_stock" => "",
            "category_id" => ""
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "name",
                    "description",
                    "price",
                    "quantity_in_stock",
                    "category_id"
                ]
            ])
            ->assertJson([
                "message" => "The name field is required. (and 4 more errors)",
                "errors" => [
                    "name" => [
                        "The name field is required."
                    ],
                    "description" => [
                        "The description field is required."
                    ],
                    "price" => [
                        "The price field is required."
                    ],
                    "quantity_in_stock" => [
                        "The quantity in stock field is required."
                    ],
                    "category_id" => [
                        "The category id field is required."
                    ]
                ]
            ]);
    }

    # update - 401
    public function testUpdateAuthorization_401()
    {
        $product = Product::where('name', 'test')->first();

        $this->put('/api/v1/products/' . $product->id, [
            "name" => "update product",
            "description" => "update description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => $product->category_id
        ], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # update - 404
    public function testUpdateNotFound_401()
    {
        $product = Product::where('name', 'test')->first();

        $this->put('/api/v1/products/' . $product->id + 10, [
            "name" => "update product",
            "description" => "update description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => $product->category_id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Product not found"]);
    }

    # update - 403
    public function testUpdateCannotUpdateAnotherUserProduct_403()
    {
        $product = Product::where('name', 'test')->first();

        $this->put('/api/v1/products/' . $product->id, [
            "name" => "update product",
            "description" => "update description product",
            "price" => 100,
            "quantity_in_stock" => 10,
            "category_id" => $product->category_id
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 204
    public function testDeleteSuccess_204()
    {
        $product = Product::where('name', 'test')->first();

        $this->delete('/api/v1/products/' . $product->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(204);
    }

    # delete - 401
    public function testDeleteAuthorization_401()
    {
        $product = Product::where('name', 'test')->first();

        $this->delete('/api/v1/products/' . $product->id, [], ['Accept' => 'application/json', 'Authorization' => "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # delete - 403
    public function testDeleteCannotDeleteAnotherUserProduct_403()
    {
        $product = Product::where('name', 'test')->first();

        $this->delete('/api/v1/products/' . $product->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 404
    public function testDeleteNotFound_404()
    {
        $product = Product::where('name', 'test')->first();

        $this->delete('/api/v1/products/' . $product->id + 10, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Product not found"]);
    }
}
