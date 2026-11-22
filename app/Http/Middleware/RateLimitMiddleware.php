<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $key = 'api', int $max = 60, int $decay = 60)
    {
        $identifier = $key . ':' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($identifier, $max)) {
            $retryAfter = RateLimiter::availableIn($identifier);
            return response()->json(['message' => 'Too many requests'], 429)->header('Retry-After', $retryAfter);
        }
        RateLimiter::hit($identifier, $decay);
        return $next($request);
    }
}
