<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Auth\Concerns;

use App\Models\User;
use Illuminate\Validation\Validator;

/**
 * A deactivated account (`users.is_active = false`) can never sign in, so it
 * must not be able to reset its password either: otherwise the user receives
 * a link, sets a new password, and is then refused at login with a generic
 * "wrong credentials" message — with nothing telling them why. The refusal
 * NAMES the cause instead. Checked on both steps (asking for the link AND
 * using it), so a link emailed before the deactivation stops working too.
 *
 * The lookup matches the e-mail exactly, like the `users` password broker,
 * so it judges the same account the broker would act on.
 */
trait RefusesInactiveAccount
{
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $inactive = User::query()
                    ->where('email', (string) $this->input('email'))
                    ->where('is_active', false)
                    ->exists();

                if ($inactive) {
                    $validator->errors()->add(
                        'email',
                        __('Your account is not active. Please contact the administration.'),
                    );
                }
            },
        ];
    }
}
