<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    /** bcrypt hash of a random string, used only to equalise timing for unknown usernames. */
    private const DUMMY_HASH = '$2y$12$TIsbi.VknYQiz1osXOzMX.B1HEp/pyFY8vd24nJ9cqUFGvs00V/5y';

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:'.Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'username' => "Too many sign-in attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('username');
        }

        $admin = AdminUser::where('username', $credentials['username'])
            ->where('status', 'active')
            ->first();

        // Always run a hash check so response time does not reveal whether the username exists.
        $passwordValid = Hash::check($credentials['password'], $admin?->password ?? self::DUMMY_HASH);

        if (! $admin || ! $passwordValid) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()->withErrors(['username' => 'Username or password is incorrect.'])->onlyInput('username');
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('web')->login($admin, $request->boolean('remember'));
        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
