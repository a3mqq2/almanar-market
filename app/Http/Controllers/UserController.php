<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getUsersData($request);
        }

        $stats = [
            'total' => User::count(),
            'active' => User::active()->count(),
            'managers' => User::managers()->count(),
            'cashiers' => User::cashiers()->count(),
        ];

        $cashboxes = Cashbox::active()->orderBy('name')->get();

        return view('users.index', compact('stats', 'cashboxes'));
    }

    protected function getUsersData(Request $request)
    {
        $query = User::with('cashboxes:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status == 'active');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'username', 'role', 'status', 'created_at', 'last_login_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'role_arabic' => $user->role_arabic,
                'status' => $user->status,
                'status_arabic' => $user->status_arabic,
                'cashboxes' => $user->cashboxes->map(fn($cb) => ['id' => $cb->id, 'name' => $cb->name]),
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i'),
                'created_at' => $user->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load('cashboxes');

        $recentActivity = UserActivityLog::forUser($user->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        $stats = [
            'total_shifts' => $user->shifts()->count(),
            'total_sales' => $user->shifts()->withSum('sales', 'total')->get()->sum('sales_sum_total') ?? 0,
            'login_count' => UserActivityLog::forUser($user->id)->action('login')->count(),
        ];

        $allCashboxes = Cashbox::active()->orderBy('name')->get();

        return view('users.show', compact('user', 'recentActivity', 'stats', 'allCashboxes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|alpha_dash',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role' => 'required|in:manager,cashier,price_checker',
            'status' => 'boolean',
            'cashbox_ids' => 'nullable|array',
            'cashbox_ids.*' => 'exists:cashboxes,id',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'role' => $validated['role'],
                'status' => $validated['status'] ?? true,
            ]);

            if (!empty($validated['cashbox_ids'])) {
                $user->cashboxes()->sync($validated['cashbox_ids']);
            }

            UserActivityLog::log('user_created', "تم إنشاء مستخدم: {$user->name}", auth()->id(), [
                'created_user_id' => $user->id,
                'role' => $user->role,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المستخدم بنجاح',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id . '|alpha_dash',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:manager,cashier,price_checker',
            'status' => 'boolean',
        ]);

        $oldRole = $user->role;
        $oldStatus = $user->status;

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'] ?? $user->status,
        ]);

        if ($oldRole != $user->role) {
            UserActivityLog::log('role_changed', "تم تغيير صلاحية المستخدم {$user->name} من {$oldRole} إلى {$user->role}", auth()->id(), [
                'target_user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $user->role,
            ]);
        }

        if ($oldStatus != $user->status) {
            UserActivityLog::log('status_changed', "تم تغيير حالة المستخدم {$user->name}", auth()->id(), [
                'target_user_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => $user->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'user' => $user->fresh(),
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        if ($user->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تغيير حالة حسابك',
            ], 422);
        }

        $oldStatus = $user->status;
        $user->update(['status' => $validated['status']]);

        UserActivityLog::log('status_changed', "تم " . ($user->status ? 'تفعيل' : 'إيقاف') . " حساب المستخدم {$user->name}", auth()->id(), [
            'target_user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $user->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => $user->status ? 'تم تفعيل الحساب' : 'تم إيقاف الحساب',
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', Password::min(6), 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        UserActivityLog::log('password_changed', "تم تغيير كلمة مرور المستخدم {$user->name}", auth()->id(), [
            'target_user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    public function assignCashboxes(Request $request, User $user)
    {
        $validated = $request->validate([
            'cashbox_ids' => 'nullable|array',
            'cashbox_ids.*' => 'exists:cashboxes,id',
        ]);

        $oldCashboxes = $user->cashboxes->pluck('id')->toArray();
        $newCashboxes = $validated['cashbox_ids'] ?? [];

        $user->cashboxes()->sync($newCashboxes);

        $added = array_diff($newCashboxes, $oldCashboxes);
        $removed = array_diff($oldCashboxes, $newCashboxes);

        if (!empty($added)) {
            UserActivityLog::log('cashbox_assigned', "تم تعيين خزائن للمستخدم {$user->name}", auth()->id(), [
                'target_user_id' => $user->id,
                'added_cashboxes' => $added,
            ]);
        }

        if (!empty($removed)) {
            UserActivityLog::log('cashbox_removed', "تم إزالة خزائن من المستخدم {$user->name}", auth()->id(), [
                'target_user_id' => $user->id,
                'removed_cashboxes' => $removed,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الخزائن بنجاح',
            'cashboxes' => $user->fresh()->cashboxes,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك',
            ], 422);
        }

        if ($user->shifts()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستخدم لوجود ورديات مرتبطة به',
            ], 422);
        }

        $userName = $user->name;
        $user->delete();

        UserActivityLog::log('user_deleted', "تم حذف المستخدم {$userName}", auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }

    public function checkUsername(Request $request)
    {
        $username = $request->get('username');
        $excludeId = $request->get('exclude_id');

        $query = User::where('username', $username);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }

    public function getActivityLog(Request $request, User $user)
    {
        $logs = UserActivityLog::forUser($user->id)
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $logs->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_arabic' => $log->action_arabic,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
