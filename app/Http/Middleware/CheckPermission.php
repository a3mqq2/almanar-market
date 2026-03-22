<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->isActive()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'حسابك غير مفعل');
        }

        if (!$user->isManager()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذه الصفحة',
                ], 403);
            }

            if ($user->isPriceChecker()) {
                return redirect()->route('price-checker');
            }

            return redirect()->route('pos.screen')->with('error', 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول لهذه الصفحة',
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        return $next($request);
    }
}
