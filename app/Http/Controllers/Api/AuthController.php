<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login()
    {
        request()->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', request()->input('email'))->first();
        if (!$user || !Hash::check(request()->input('password'), $user->password)) {
            return response()
                ->json([
                    'message' => 'Unauthorized',
                    'errors' => [
                        'email' => ['Invalid email or password']
                    ]
                ])
                ->setStatusCode(401);
        }

        return response()
            ->json([
                'message' => 'Login successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'tokens' => [
                        'access' => $user->createToken('token')->plainTextToken,
                    ]
                ]
            ])
            ->setStatusCode(200);
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()
            ->json([])
            ->setStatusCode(204);
    }
}
