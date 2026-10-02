<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // `login` accepts either an email address or a username, since
        // employees may have no email on file (their username is always
        // auto-generated — see EmployeeCredentialService).
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = (string) $this->string('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $login,
            'password' => $this->string('password')->value(),
        ];

        // Deactivated accounts can never sign in, even with valid credentials.
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => $this->isInactiveWithValidPassword($field, $login)
                    ? __('Your account is not active. Please contact the administration.')
                    : __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * A deactivated account with the RIGHT password is told why it is refused,
     * instead of the generic "wrong credentials" — which made the user retry
     * or reset a password that was never the problem. Only once the password
     * is proven, so a wrong guess learns nothing about the account's state.
     */
    private function isInactiveWithValidPassword(string $field, string $login): bool
    {
        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials([$field => $login]);

        return $user !== null
            && ! $user->is_active
            && $provider->validateCredentials($user, ['password' => $this->string('password')->value()]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->value()).'|'.$this->ip());
    }
}
