<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class]);
    }

    # 200
    public function testLoginSuccess_200()
    {
        $user = User::where('role', 1)->first();

        $this->post(
            '/api/v1/auth/login',
            ['email' => 'test@gmail.com', 'password' => 'password'],
            ['Accept' => 'application/json']
        )->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                    'tokens' => [
                        'access'
                    ]
                ]
            ])->assertJson([
                'message' => 'Login successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ]
                ]
            ]);
    }

    # 422
    public function testLoginValidationError_422()
    {
        $this->post(
            '/api/v1/auth/login',
            ['email' => '', 'password' => ''],
            ['Accept' => 'application/json']
        )->assertStatus(422)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "email",
                    "password"
                ]
            ])
            ->assertJson([
                "message" => "The email field is required. (and 1 more error)",
                "errors" => [
                    "email" => [
                        "The email field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ]
                ]
            ]);
    }

    # 401
    public function testLoginCredentialsFailed_401()
    {
        $this->post(
            '/api/v1/auth/login',
            ['email' => 'wrongemail@gmail.com', 'password' => 'password'],
            ['Accept' => 'application/json']
        )->assertStatus(401)
            ->assertJsonStructure([
                "message",
                "errors" => [
                    "email",
                ]
            ])
            ->assertJson([
                "message" => "Unauthorized",
                "errors" => [
                    "email" => [
                        "Invalid email or password"
                    ],
                ]
            ]);
    }

    # 204
    public function testLogoutSuccess_200()
    {
        $res = $this->post('/api/v1/auth/login', ['email' => 'test@gmail.com', 'password' => 'password'], ['Accept' => 'application/json'])
            ->assertStatus(200);
        $res = $res->decodeResponseJson();
        $accessToken = "Bearer " . $res['data']['tokens']['access'];

        $this->post('/api/v1/auth/logout', [], ['Accept' => 'application/json', 'Authorization' => $accessToken])
            ->assertStatus(204);
    }

    public function testLogoutAuthorization_401()
    {
        $accessToken = "Bearer " . "invalid token";

        $this->post('/api/v1/auth/logout', [], ['Accept' => 'application/json', 'Authorization' => $accessToken])
            ->assertStatus(401)
            ->assertJsonStructure(["message"])
            ->assertJson([
                "message" => "Unauthenticated."
            ]);
    }
}
