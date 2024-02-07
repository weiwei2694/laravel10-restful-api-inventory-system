<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $users = User::all();

        return response()
            ->json([
                'message' => 'Get users successfully.',
                'data' => $users
            ])
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        request()->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::create(request()->only(['name', 'email', 'password']));

        return response()
            ->json([
                'message' => 'User created successfully.',
                'data' => $user
            ])
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()
                ->json(['message' => 'User not found'])
                ->setStatusCode(404);
        }

        return response()
            ->json([
                'message' => 'Get user successfully.',
                'data' => $user
            ])
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()
                ->json(['message' => 'User not found'])
                ->setStatusCode(404);
        }

        if ($user->role === Role::ADMIN && auth()->id() !== $user->id) {
            return response()
                ->json(['message' => 'Forbidden'])
                ->setStatusCode(403);
        }

        request()->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $user->name = request()->input('name');
        $user->email = request()->input('email');
        if (request()->input('password')) {
            $user->password = bcrypt(request()->input('password'));
        }
        $user->save();

        return response()
            ->json([
                'message' => 'User updated successfully.',
                'data' => new UserResource($user)
            ])
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()
                ->json(['message' => 'User not found'])
                ->setStatusCode(404);
        }

        if ($user->role === Role::ADMIN || auth()->id() === $user->id) {
            return response()
                ->json(['message' => 'Forbidden'])
                ->setStatusCode(403);
        }

        $user->delete();

        return response()
            ->json([])
            ->setStatusCode(204);
    }
}
