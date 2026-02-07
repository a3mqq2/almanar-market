<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حسابك غير مفعل',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'حسابك غير مفعل');
        }

        if (auth()->check() && auth()->user()->isPriceChecker()) {
            $allowedRoutes = ['price-checker', 'price-checker.lookup', 'logout', 'login'];
            $currentRoute = $request->route()?->getName();

            if (!in_array($currentRoute, $allowedRoutes)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بالوصول',
                    ], 403);
                }

                return redirect()->route('price-checker');
            }
        }

        return $next($request);
    }
}
