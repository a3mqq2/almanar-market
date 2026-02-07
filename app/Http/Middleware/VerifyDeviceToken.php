<?php

namespace App\Http\Middleware;

use App\Models\DeviceRegistration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التوثيق مطلوب',
            ], 401);
        }

        $device = DeviceRegistration::where('api_token', $token)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التوثيق غير صالح',
            ], 401);
        }

        if ($device->isRevoked()) {
            return response()->json([
                'success' => false,
                'message' => 'تم إلغاء تسجيل الجهاز',
            ], 403);
        }

        if ($device->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'تم تعليق الجهاز مؤقتاً',
            ], 403);
        }

        $device->updateLastSeen($request->ip());

        $request->attributes->set('device', $device);
        $request->attributes->set('device_user', $device->user);

        return $next($request);
    }
}
