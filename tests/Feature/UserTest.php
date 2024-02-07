<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public string $accessTokenAdmin;
    public string $accessTokenUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class]);

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
        $this->get('/api/v1/users', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    [
                        'id',
                        'name',
                        'email',
                        'role',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson([
                'message' => 'Get users successfully.',
                'data' => User::all()->toArray()
            ]);
    }

    # index - 401
    public function testIndexAuthorization_401()
    {
        $this->get('/api/v1/users', ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure([
                'message'
            ])
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    # index - 403
    public function testIndexAuthorization_403()
    {
        $this->get('/api/v1/users', ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => 'Forbidden']);
    }

    # store - 201
    public function testStoreSuccess_200()
    {
        $admin = User::where('role', 2)->first();

        $this->post('/api/v1/users', [
            'name' => 'test create',
            'email' => 'testcreate@gmail.com',
            'password' => 'password'
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'name',
                    'email',
                    'updated_at',
                    'created_at',
                    'id',
                ]
            ])
            ->assertJson([
                'message' => 'User created successfully.',
                'data' => [
                    'name' => 'test create',
                    'email' => 'testcreate@gmail.com',
                    'id' => $admin->id + 1
                ]
            ]);
    }

    # store - 403
    public function testStoreAuthorization_403()
    {
        $this->post('/api/v1/users', [
            'name' => 'test create',
            'email' => 'testcreate@gmail.com',
            'password' => 'password'
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(['message'])
            ->assertJson(['message' => 'Forbidden']);
    }

    # store - 422
    public function testStoreValidationError_422()
    {
        $this->post('/api/v1/users', [
            'name' => '',
            'email' => '',
            'password' => ''
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'email',
                    'password'
                ]
            ])
            ->assertJson([
                'message' => 'The name field is required. (and 2 more errors)',
                'errors' => [
                    'name' => ['The name field is required.'],
                    'email' => ['The email field is required.'],
                    'password' => ['The password field is required.']
                ]
            ]);
    }

    # store - 401
    public function testStoreAuthorization_401()
    {
        $this->post('/api/v1/users', [
            'name' => 'test create',
            'email' => 'testcreate@gmail.com',
            'password' => 'password'
        ], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(['message'])
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    # show - 200
    public function testShowSuccess_200()
    {
        $user = User::where('role', 1)->first();

        $this->get('/api/v1/users/' . $user->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "role",
                    "email_verified_at",
                    "created_at",
                    "updated_at"
                ]
            ])
            ->assertJson([
                "message" => "Get user successfully.",
                "data" => $user->toArray()
            ]);
    }

    # show - 401
    public function testShowAuthorization_401()
    {
        $user = User::where('role', 1)->first();
        $this->get('/api/v1/users/' . $user->id, ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Unauthenticated."
            ]);
    }

    # show - 403
    public function testShowAuthorization_403()
    {
        $user = User::where('role', 1)->first();
        $this->get('/api/v1/users/' . $user->id, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Forbidden"
            ]);
    }

    # show - 404
    public function testShowNotFound_404()
    {
        $user = User::where('role', 1)->first();
        $this->get('/api/v1/users/' . $user->id + 10, ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "User not found"
            ]);
    }

    # update - 200
    public function testUpdateSuccess_200()
    {
        $user = User::where('role', 1)->first();

        $this->put('/api/v1/users/' . $user->id, [
            'name' => 'test update',
            'email' => 'testupdate@gmail.com',
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(200)
            ->assertJsonStructure([
                "message",
                "data" => [
                    "id",
                    "name",
                    "email",
                    "role"
                ]
            ])
            ->assertJson([
                "message" => "User updated successfully.",
                "data" => [
                    "id" => $user->id,
                    "name" => 'test update',
                    "email" => 'testupdate@gmail.com',
                    "role" => $user->role
                ]
            ]);
    }

    # update - 401
    public function testUpdateAuthorization_401()
    {
        $user = User::where('role', 1)->first();

        $this->put('/api/v1/users/' . $user->id, [
            'name' => 'test update',
            'email' => 'testupdate@gmail.com',
        ], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # update - 403
    public function testUpdateAuthorization_403()
    {
        $user = User::where('role', 1)->first();

        $this->put('/api/v1/users/' . $user->id, [
            'name' => 'test update',
            'email' => 'testupdate@gmail.com',
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # update - 404
    public function testUpdateNotFound_404()
    {
        $user = User::where('role', 1)->first();

        $this->put('/api/v1/users/' . $user->id + 10, [
            'name' => 'test update',
            'email' => 'testupdate@gmail.com',
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "User not found"]);
    }

    # update - 422
    public function testUpdateValidationError_422()
    {
        $user = User::where('role', 1)->first();

        $this->put('/api/v1/users/' . $user->id, [
            'name' => '',
            'email' => '',
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "name",
                    "email"
                ]
            ])
            ->assertJson([
                "message" => "The name field is required. (and 1 more error)",
                "errors" => [
                    "name" => ["The name field is required."],
                    "email" => ["The email field is required."]
                ]
            ]);
    }

    # update - 403
    public function testUpdateCannotUpdateUserAdmin_403()
    {
        $admin = User::factory()->create(['role' => 2]);

        $this->put('/api/v1/users/' . $admin->id, [
            'name' => 'test update',
            'email' => 'testupdate@gmail.com',
        ], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 204
    public function testDeleteSuccess_204()
    {
        $user = User::where('role', 1)->first();

        $this->delete('/api/v1/users/' . $user->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(204);
    }

    # delete - 403
    public function testDeleteCannotDeleteUserAdmin_403()
    {
        $admin = User::factory()->create(['role' => 2]);

        $this->delete('/api/v1/users/' . $admin->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 403
    public function testDeleteCannotDeleteSelfUser_403()
    {
        $admin = User::where('role', 2)->first();

        $this->delete('/api/v1/users/' . $admin->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 403
    public function testDeleteAuthorization_403()
    {
        $user = User::where('role', 1)->first();

        $this->delete('/api/v1/users/' . $user->id, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenUser])
            ->assertStatus(403)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Forbidden"]);
    }

    # delete - 401
    public function testDeleteAuthorization_401()
    {
        $user = User::where('role', 1)->first();

        $this->delete('/api/v1/users/' . $user->id, [], ['Accept' => 'application/json', 'Authorization' => 'invalid token'])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "Unauthenticated."]);
    }

    # delete - 404
    public function testDeleteNotFound_404()
    {
        $user = User::where('role', 1)->first();

        $this->delete('/api/v1/users/' . $user->id + 10, [], ['Accept' => 'application/json', 'Authorization' => $this->accessTokenAdmin])
            ->assertStatus(404)
            ->assertJsonStructure(["message"])
            ->assertJson(["message" => "User not found"]);
    }
}
