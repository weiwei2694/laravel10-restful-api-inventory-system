<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $role = $user->role === Role::ADMIN
            ? 'Admin'
            : 'User';

        if ($role === 'User') {
            return response()
                ->json([
                    'message' => 'Forbidden',
                ])
                ->setStatusCode(403);
        }

        return $next($request);
    }
}
