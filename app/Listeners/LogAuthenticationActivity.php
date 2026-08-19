<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Records every authentication event into the audit journal
 * (log_name `authentication`).
 *
 * Why events instead of code in LoginController: the journal must answer
 * "who was signed in from where, and when" for EVERY entry path — the
 * backoffice form today, but also password resets, "remember me" rehydration
 * and anything added later. Hooking Laravel's own auth events means a new
 * sign-in path is covered the moment it exists, with no second place to
 * remember to update.
 *
 * ⚠ Registration is Laravel's automatic listener discovery: each `handleX()`
 * method is bound from its type-hinted event parameter. Do NOT also register
 * this class (Event::subscribe / a listen array) — that binds every handler a
 * second time and writes each auth event to the journal TWICE.
 *
 * Failed attempts matter as much as successful ones: a run of failures from
 * one IP just before a suspicious encaissement is exactly the pattern a fraud
 * investigation looks for, and it is invisible if only successes are logged.
 *
 * IP address, user agent and timestamp are stamped on every row by
 * App\Models\Activity — see that class for the forensic-context contract.
 */
final class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $this->record(
            event: 'login',
            description: 'Connexion réussie',
            user: $event->user,
            properties: ['guard' => $event->guard, 'remember' => $event->remember],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        $this->record(
            event: 'logout',
            description: 'Déconnexion',
            user: $event->user,
            properties: ['guard' => $event->guard],
        );
    }

    /**
     * A failed attempt has no authenticated causer — the submitted login is
     * kept in properties so the journal can still show WHICH account was
     * targeted, while the password itself is never touched.
     */
    public function handleFailed(Failed $event): void
    {
        $this->record(
            event: 'login_failed',
            description: 'Échec de connexion',
            user: $event->user,
            properties: [
                'guard' => $event->guard,
                'login' => $event->credentials['email']
                    ?? $event->credentials['username']
                    ?? null,
                'account_exists' => $event->user !== null,
            ],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->record(
            event: 'lockout',
            description: 'Compte temporairement bloqué (trop de tentatives)',
            user: null,
            properties: ['login' => $event->request->input('login')],
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->record(
            event: 'password_reset',
            description: 'Mot de passe réinitialisé',
            user: $event->user,
        );
    }

    /**
     * Write one `authentication` entry.
     *
     * Centralised because the null cases are the norm here, not the exception:
     * a failed login for an unknown username has no user at all, and a lockout
     * has neither user nor causer. `performedOn()` only accepts a real Model,
     * so it must be skipped rather than called with null - doing that at each
     * call site is what made the login page 500.
     *
     * `causedBy()` is likewise only called with a User: the auth events type
     * their subject as Authenticatable, which is not necessarily this app's
     * User model, and the journal's causer is always a User.
     *
     * @param  array<string, mixed>  $properties
     */
    private function record(
        string $event,
        string $description,
        ?Authenticatable $user,
        array $properties = [],
    ): void {
        $logger = activity('authentication')->event($event);

        if ($user instanceof User) {
            $logger->causedBy($user)->performedOn($user);
        }

        if ($properties !== []) {
            $logger->withProperties($properties);
        }

        $logger->log($description);
    }

}
