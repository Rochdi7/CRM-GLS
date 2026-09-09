<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'password', 'must_change_password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use Auditable;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * Mirrors the DB defaults (CLAUDE.md §11: a model with a DB-default
     * column mirrors it here, or an in-memory instance reads NULL — which
     * EnsureUserIsActive would take for "deactivated").
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The staff record this login belongs to (employees.user_id).
     * Users are only ever created via EmployeeCredentialService —
     * there is no public registration (structure doc §8).
     *
     * ⚠ `withoutGlobalScopes()` — this relation is a login's OWN IDENTITY,
     * not a listing. `Employee` carries `HiddenAccountScope`, so without
     * this a hidden account resolves NULL for itself: its centre resolution
     * (`CurrentContext`) would fall back to no primary centre and its
     * Profil page would render with every staff field blank (09/09/2026,
     * when the hidden list stopped implying the right to see it).
     *
     * This does NOT re-expose anybody: the relation is keyed on
     * `employees.user_id = $this->id`, so it only ever returns the row that
     * belongs to this login. Every LIST, dropdown and lookup keeps the
     * global scope and stays filtered (CLAUDE.md §11).
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class)->withoutGlobalScopes();
    }
}
