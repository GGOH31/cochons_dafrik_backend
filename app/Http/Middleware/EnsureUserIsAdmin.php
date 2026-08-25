<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => "Accès réservé aux administrateurs.",
            ], 403);
        }

        return $next($request);
    }
}
