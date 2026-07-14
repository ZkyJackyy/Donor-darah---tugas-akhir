<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks both guards' actual authentication state instead of relying
     * on whichever guard a preceding auth middleware happened to activate,
     * or on header presence alone (a stray Authorization header on an
     * otherwise session-based request shouldn't be able to flip which
     * guard is consulted). This keeps behavior correct even if route
     * middleware ordering changes in the future.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();

        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
            }
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
