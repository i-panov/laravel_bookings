<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;

class AuthenticateWithApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            throw new AuthenticationException('Unauthenticated');
        }

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            throw new AuthenticationException('Invalid token');
        }

        Auth::setUser($user);

        return $next($request);
    }
}
