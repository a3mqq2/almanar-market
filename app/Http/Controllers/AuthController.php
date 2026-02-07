<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $credentials['login'])->first();

        if (!$user) {
            UserActivityLog::log('login_failed', "محاولة دخول فاشلة: {$credentials['login']}", null, [
                'login_field' => $loginField,
                'login_value' => $credentials['login'],
            ]);

            return back()->withErrors([
                'login' => 'بيانات الدخول غير صحيحة',
            ])->onlyInput('login');
        }

        if (!$user->isActive()) {
            UserActivityLog::log('login_failed', "محاولة دخول لحساب غير مفعل: {$user->name}", $user->id);

            return back()->withErrors([
                'login' => 'هذا الحساب غير مفعل',
            ])->onlyInput('login');
        }

        if (Auth::attempt([$loginField => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            UserActivityLog::log('login', "تسجيل دخول ناجح", $user->id);

            return $this->redirectBasedOnRole($user);
        }

        UserActivityLog::log('login_failed', "كلمة مرور خاطئة للمستخدم: {$user->name}", $user->id);

        return back()->withErrors([
            'login' => 'بيانات الدخول غير صحيحة',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $openShift = Shift::getOpenShift($user->id);

            if ($openShift && !$user->isManager()) {
                return redirect()->route('pos.screen')
                    ->with('error', 'يجب إغلاق الوردية قبل تسجيل الخروج');
            }

            if ($openShift && $user->isManager()) {
                $openShift->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'force_closed' => true,
                    'force_closed_by' => $user->id,
                    'force_close_reason' => 'تسجيل خروج المدير',
                ]);

                foreach ($openShift->shiftCashboxes as $sc) {
                    $sc->update([
                        'closing_balance' => $sc->expected_balance,
                        'difference' => 0,
                    ]);
                }
            }

            UserActivityLog::log('logout', "تسجيل خروج", $user->id);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function redirectBasedOnRole(User $user)
    {
        if ($user->isPriceChecker()) {
            return redirect()->route('price-checker');
        }

        if ($user->isCashier()) {
            return redirect()->route('pos.screen');
        }

        return redirect()->route('dashboard');
    }
}
