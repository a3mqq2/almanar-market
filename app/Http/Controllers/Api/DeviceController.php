<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string|size:64',
            'device_name' => 'required|string|max:255',
            'app_version' => 'nullable|string|max:20',
        ]);

        $licenseKey = $request->input('license_key');

        if (!$this->validateLicenseKey($licenseKey)) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح الترخيص غير صالح',
            ], 401);
        }

        $existingCount = DeviceRegistration::where('license_key', $licenseKey)
            ->where('status', 'active')
            ->count();

        $maxDevices = $this->getMaxDevicesForLicense($licenseKey);

        if ($existingCount >= $maxDevices) {
            return response()->json([
                'success' => false,
                'message' => 'تم الوصول للحد الأقصى من الأجهزة المسموح بها',
            ], 403);
        }

        $deviceId = DeviceRegistration::generateDeviceId();

        return response()->json([
            'success' => true,
            'device_id' => $deviceId,
            'message' => 'تم تسجيل الجهاز بنجاح. الرجاء تفعيل الجهاز.',
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|size:36',
            'license_key' => 'required|string|size:64',
            'device_name' => 'required|string|max:255',
            'username' => 'required|string',
            'password' => 'required|string',
            'app_version' => 'nullable|string|max:20',
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات المستخدم غير صحيحة',
            ], 401);
        }

        $existing = DeviceRegistration::where('device_id', $request->input('device_id'))->first();

        if ($existing) {
            $token = $existing->regenerateToken();
            $existing->update([
                'user_id' => $user->id,
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
                'app_version' => $request->input('app_version'),
            ]);

            return response()->json([
                'success' => true,
                'api_token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ]);
        }

        $device = DeviceRegistration::create([
            'device_id' => $request->input('device_id'),
            'device_name' => $request->input('device_name'),
            'license_key' => $request->input('license_key'),
            'api_token' => DeviceRegistration::generateToken(),
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'app_version' => $request->input('app_version'),
            'status' => 'active',
            'activated_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'api_token' => $device->api_token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->device_id,
                'name' => $device->device_name,
                'status' => $device->status,
                'last_sync' => $device->last_sync_at?->toIso8601String(),
                'activated_at' => $device->activated_at?->toIso8601String(),
            ],
            'user' => [
                'id' => $device->user->id,
                'name' => $device->user->name,
                'role' => $device->user->role,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $device->updateLastSeen($request->ip());

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    protected function validateLicenseKey(string $key): bool
    {
        $validPrefixes = ['MARKET-', 'POS-', 'LICENSE-'];

        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function getMaxDevicesForLicense(string $key): int
    {
        if (str_starts_with($key, 'MARKET-ENTERPRISE-')) {
            return 10;
        }

        if (str_starts_with($key, 'MARKET-PRO-')) {
            return 5;
        }

        return 1;
    }
}
