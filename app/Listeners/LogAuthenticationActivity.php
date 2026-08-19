<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

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
        $user = $event->user;

        activity('authentication')
            ->causedBy($user)
            ->performedOn($user instanceof User ? $user : null)
            ->event('login')
            ->withProperties(['guard' => $event->guard, 'remember' => $event->remember])
            ->log('Connexion réussie');
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user === null) {
            return;
        }

        activity('authentication')
            ->causedBy($user)
            ->performedOn($user instanceof User ? $user : null)
            ->event('logout')
            ->withProperties(['guard' => $event->guard])
            ->log('Déconnexion');
    }

    /**
     * A failed attempt has no authenticated causer — the submitted login is
     * kept in properties so the journal can still show WHICH account was
     * targeted, while the password itself is never touched.
     */
    public function handleFailed(Failed $event): void
    {
        activity('authentication')
            ->causedBy($event->user)
            ->performedOn($event->user instanceof User ? $event->user : null)
            ->event('login_failed')
            ->withProperties([
                'guard' => $event->guard,
                'login' => $event->credentials['email']
                    ?? $event->credentials['username']
                    ?? null,
                'account_exists' => $event->user !== null,
            ])
            ->log('Échec de connexion');
    }

    public function handleLockout(Lockout $event): void
    {
        activity('authentication')
            ->event('lockout')
            ->withProperties([
                'login' => $event->request->input('login'),
            ])
            ->log('Compte temporairement bloqué (trop de tentatives)');
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;

        activity('authentication')
            ->causedBy($user)
            ->performedOn($user instanceof User ? $user : null)
            ->event('password_reset')
            ->log('Mot de passe réinitialisé');
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
