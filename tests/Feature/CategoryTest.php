<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public string $accessTokenAdmin;
    public string $accessTokenUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, CategorySeeder::class]);

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
        $this->get('/api/v1/categories', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    "current_page",
                    "data" => [
                        [
                            "id",
                            "name",
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
                'message' => 'Get categories successfully.',
                'data' => Category::paginate(10)->toArray()
            ]);
    }

    # index - 401
    public function testIndexAuthorization_401()
    {
        $this->get('/api/v1/categories', ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Unauthenticated."
            ]);
    }

    # store - 201
    public function testStoreSuccess_201()
    {
        $this->post('/api/v1/categories', ["name" => "test create"], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(201)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "name",
                    "updated_at",
                    "created_at",
                    "id"
                ]
            ])
            ->assertJson([
                "message" => "Category created successfully.",
                "data" => [
                    "name" => "test create",
                    "id" => Category::where('name', 'test')->first()->id + 1
                ]
            ]);
    }

    # store - 401
    public function testStoreAuthorization_401()
    {
        $this->post('/api/v1/categories', ['name' => 'test create'], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # store - 422
    public function testStoreValidationError_422()
    {
        $this->post('/api/v1/categories', ['name' => ''], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "name"
                ]
            ])
            ->assertJson([
                "message" => "The name field is required.",
                "errors" => [
                    "name" => ["The name field is required."]
                ]
            ]);
    }

    # show - 200
    public function testShowSuccess_200()
    {
        $category = Category::where('name', 'test')->first();

        $this->get('/api/v1/categories/' . $category->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Get category successfully.",
                "data" => [
                    "id" => $category->id,
                    "name" => $category->name
                ]
            ]);
    }

    # show - 401
    public function testShowAuthorization_401()
    {
        $category = Category::where('name', 'test')->first();

        $this->get('/api/v1/categories/' . $category->id, ["Accept" => "application/json", "Authorization" => "Bearer " . "invalid token"])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Unauthenticated."
            ]);
    }

    # show - 404
    public function testShowNotFound_404()
    {
        $category = Category::where('name', 'test')->first();

        $this->get('/api/v1/categories/' . $category->id + 10, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Category not found"]);
    }

    # update - 200
    public function testUpdateSuccess_200()
    {
        $category = Category::where('name', 'test')->first();

        $this->put('/api/v1/categories/' . $category->id, ['name' => 'test update'], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Category updated successfully.",
                "data" => [
                    "id" => $category->id,
                    "name" => "test update",
                ]
            ]);
    }

    # update - 422
    public function testUpdateValidationError_422()
    {
        $category = Category::where('name', 'test')->first();

        $this->put('/api/v1/categories/' . $category->id, ['name' => ''], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "name"
                ]
            ])
            ->assertJson([
                "message" => "The name field is required.",
                "errors" => [
                    "name" => ["The name field is required."]
                ]
            ]);
    }

    # update - 404
    public function testUpdateNotFound_404()
    {
        $category = Category::where('name', 'test')->first();

        $this->put('/api/v1/categories/' . $category->id + 10, ['name' => 'test update'], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Category not found"]);
    }

    # destory - 204
    public function testDestroySuccess_204()
    {
        $category = Category::where('name', 'test')->first();

        $this->delete('/api/v1/categories/' . $category->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(204);
    }

    # destroy - 401
    public function testDestroyAuthorization_401()
    {
        $category = Category::where('name', 'test')->first();

        $this->delete('/api/v1/categories/' . $category->id, [], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # destroy - 404
    public function testDestroyNotFound_404()
    {
        $category = Category::where('name', 'test')->first();

        $this->delete('/api/v1/categories/' . $category->id + 10, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Category not found"]);
    }
}
