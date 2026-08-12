<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 300;

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);

        $emailThrottleKey = $this->emailThrottleKey($credentials['email']);
        $ipThrottleKey = $this->ipThrottleKey($request);

        if (
            RateLimiter::tooManyAttempts($emailThrottleKey, self::MAX_LOGIN_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipThrottleKey, self::MAX_LOGIN_ATTEMPTS)
        ) {
            throw ValidationException::withMessages([
                'email' => $this->lockoutMessage(max(
                    RateLimiter::availableIn($emailThrottleKey),
                    RateLimiter::availableIn($ipThrottleKey),
                )),
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {
            RateLimiter::clear($emailThrottleKey);
            RateLimiter::clear($ipThrottleKey);
            $request->session()->regenerate();
            $request->session()->forget(['impersonator_id', 'impersonator_name']);
            $request->user()?->update([
                'last_login_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($emailThrottleKey, self::LOGIN_LOCKOUT_SECONDS);
        RateLimiter::hit($ipThrottleKey, self::LOGIN_LOCKOUT_SECONDS);

        throw ValidationException::withMessages([
            'email' => 'Credenciales invalidas o usuario inactivo.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->forget(['impersonator_id', 'impersonator_name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function emailThrottleKey(string $email): string
    {
        return 'login:email:'.Str::lower(trim($email));
    }

    private function ipThrottleKey(Request $request): string
    {
        return 'login:ip:'.$request->ip();
    }

    private function lockoutMessage(int $seconds): string
    {
        if ($seconds < 60) {
            return 'Demasiados intentos fallidos. Intenta de nuevo en '.$seconds.' segundos.';
        }

        $minutes = (int) ceil($seconds / 60);

        return 'Demasiados intentos fallidos. Intenta de nuevo en '.$minutes.' minuto(s).';
    }
}
