# GLS CRM — Laravel Project Structure (New, Standalone Project)

Companion to `gls-crm-schema.md`. This document maps the 15-table `gls_full_v4` schema onto an actual Laravel 11 folder/file structure — models, migrations, controllers, Livewire components, routes — for a **brand-new, standalone Laravel project**, separate from the existing GLS website/backoffice repo. Nothing here assumes or reuses any existing code; this is a from-scratch build plan.

**Auth requirement locked in for this project:** staff-only login (no student/parent portal in this phase). Creating an `employees` record **auto-generates its login credentials** (username + password) — this is a specific, non-default Laravel behavior and is designed explicitly below, not just "add Breeze and move on."

---

## 1. Project Setup

```bash
composer create-project laravel/laravel gls-crm
cd gls-crm
composer require livewire/livewire
composer require spatie/laravel-activitylog
composer require spatie/laravel-permission
php artisan livewire:publish --config
```

- **`spatie/laravel-activitylog`** — powers `audit_logs` (see §6).
- **`spatie/laravel-permission`** — even though this project has only one broad "staff" concept, `employees.categorie` (Enseignant / Directeur / Assistante administrative...) should drive real permission gates in the UI (a teacher shouldn't see `caisse_transfers`, for instance). Using a real permission package now avoids hand-rolled `if ($employee->categorie === 'Directeur')` checks scattered across every controller.

---

## 2. `database/migrations/` — One Migration per Schema Table

Laravel migrations run in filename-timestamp order, so foreign-key dependencies must be created in the right sequence. Below is the exact creation order this schema requires (a table can only reference a table that already exists):

```
database/migrations/
├── xxxx_01_create_etablissements_table.php
├── xxxx_02_create_annees_scolaires_table.php
├── xxxx_03_create_users_table.php                  (Laravel default, kept as-is)
├── xxxx_04_create_salles_table.php                 → FK: etablissement_id
├── xxxx_05_create_employees_table.php              → FK: etablissement_id, user_id
├── xxxx_06_create_students_table.php                → FK: etablissement_id
├── xxxx_07_create_groups_table.php                  → FK: enseignant_id, salle_id, etablissement_id, annee_scolaire_id
├── xxxx_08_create_groups_historique_table.php       → FK: group_id, enseignant_id, etablissement_id, annee_scolaire_id, archived_by
├── xxxx_09_create_inscriptions_table.php            → FK: student_id, group_id, etablissement_id, annee_scolaire_id, created_by
├── xxxx_10_create_inscription_fees_table.php        → FK: inscription_id
├── xxxx_11_create_caisses_table.php                 → FK: etablissement_id, responsable_employee_id
├── xxxx_12_create_encaissements_table.php           → FK: student_id, inscription_fee_id, caisse_id, agent_id
├── xxxx_13_create_types_depenses_table.php
├── xxxx_14_create_depenses_table.php                → FK: type_depense_id, caisse_id, agent_id
├── xxxx_15_create_remboursements_table.php          → FK: beneficiaire_id (students), caisse_id, agent_id
├── xxxx_16_create_caisse_transfers_table.php        → FK: caisse_source_id, caisse_destination_id, requested_by, validated_by
└── (audit_logs is created automatically by spatie/laravel-activitylog's own migration — see §6)
```

**Why this exact order matters:** `employees.etablissement_id` needs `etablissements` to exist first; `employees.user_id` needs the default `users` table to exist first; `groups.enseignant_id` needs `employees` to exist first; and so on down the chain. Getting this wrong produces a "foreign key constraint fails" error on `migrate`.

### Example migration — `xxxx_05_create_employees_table.php`

```php
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('reference', 20)->unique();
    $table->string('nom', 100);
    $table->string('prenom', 100);
    $table->string('categorie', 30); // Enseignant / Directeur / Assistante administrative / ...
    $table->string('statut', 20)->default('Actif');
    $table->string('telephone', 20)->nullable();
    $table->string('whatsapp', 20)->nullable();
    $table->string('email', 255)->nullable();
    $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

Every other table follows the same pattern: `foreignId(...)->constrained(...)`. Use `->nullOnDelete()` on optional relationships (matches the "Yes" nullable columns in `gls-crm-schema.md`) and `->cascadeOnDelete()` only where the schema doc explicitly says a child record has no meaning without its parent (e.g. `inscription_fees.inscription_id`, `groups_historique.group_id`).

---

## 3. `app/Models/` — One Model per Table

```
app/Models/
├── User.php                    (Laravel default — see §5 for the auto-credential logic)
├── Etablissement.php
├── AnneeScolaire.php
├── Salle.php
├── Employee.php                → the auto-credential creation logic lives here (see §5)
├── Student.php
├── Group.php
├── GroupHistorique.php
├── Inscription.php
├── InscriptionFee.php
├── Caisse.php
├── Encaissement.php
├── TypeDepense.php
├── Depense.php
├── Remboursement.php
└── CaisseTransfer.php
```

### Example — `app/Models/Group.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    // groups.statut lifecycle — enforced here, not at the database level (see gls-crm-schema.md §6)
    public const STATUT_PRE_INSCRIPTION = 'Pré-inscription';
    public const STATUT_EN_FORMATION = 'En formation';
    public const STATUT_FIN_FORMATION = 'Fin de formation';

    public const STATUTS = [
        self::STATUT_PRE_INSCRIPTION,
        self::STATUT_EN_FORMATION,
        self::STATUT_FIN_FORMATION,
    ];

    // niveaux — plain VARCHAR, validated against this fixed list (see gls-crm-schema.md §5)
    public const NIVEAUX = [
        'A1.1', 'A1.2',
        'A2.1', 'A2.2', 'A2.3',
        'B1.1', 'B1.2', 'B1.3',
        'B2.1', 'B2.2', 'B2.3',
    ];

    protected $fillable = [
        'nom', 'niveau', 'enseignant_id', 'salle_id', 'etablissement_id',
        'annee_scolaire_id', 'capacite_max', 'statut',
        'date_debut_formation', 'date_fin_formation',
    ];

    protected $casts = [
        'date_debut_formation' => 'date',
        'date_fin_formation' => 'date',
    ];

    public function enseignant() { return $this->belongsTo(Employee::class, 'enseignant_id'); }
    public function salle() { return $this->belongsTo(Salle::class); }
    public function etablissement() { return $this->belongsTo(Etablissement::class); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id'); }
    public function inscriptions() { return $this->hasMany(Inscription::class); }
    public function historique() { return $this->hasOne(GroupHistorique::class); }

    /**
     * Marks the group finished and archives a snapshot in one transaction.
     * This is the ONLY correct way to transition a group to Fin de formation —
     * never set ->statut directly without also calling this, or groups_historique
     * silently falls out of sync (see gls-crm-schema.md §7).
     */
    public function archiverCommeTermine(Employee $archivedBy): void
    {
        \DB::transaction(function () use ($archivedBy) {
            $this->update([
                'statut' => self::STATUT_FIN_FORMATION,
                'date_fin_formation' => now(),
            ]);

            $this->historique()->create([
                'nom' => $this->nom,
                'niveau' => $this->niveau,
                'enseignant_id' => $this->enseignant_id,
                'etablissement_id' => $this->etablissement_id,
                'annee_scolaire_id' => $this->annee_scolaire_id,
                'nombre_etudiants_final' => $this->inscriptions()->count(),
                'date_debut_formation' => $this->date_debut_formation,
                'date_fin_formation' => $this->date_fin_formation,
                'archived_at' => now(),
                'archived_by' => $archivedBy->id,
            ]);
        });
    }
}
```

**This method is the single most important piece of business logic in the whole project** — it's the code-level enforcement of the rule you specified: a group must never be deleted, and `groups_historique` must never fall out of sync with a group that actually finished. Every place in the UI that lets a group be marked "Fin de formation" must call `archiverCommeTermine()`, never a raw `->update(['statut' => ...])`.

---

## 4. `app/Http/Controllers/` and `app/Livewire/`

Per the stack recommendation in `gls-crm-schema.md` (Blade + Livewire, no separate API/SPA), most CRUD screens are Livewire full-page components, not traditional controller+view pairs. Structure:

```
app/Http/Controllers/
├── Auth/
│   └── LoginController.php          (staff login only — no registration route exposed publicly)
└── DashboardController.php          (simple landing page after login)

app/Livewire/
├── Etablissements/
│   ├── EtablissementIndex.php
│   └── EtablissementForm.php
├── Employees/
│   ├── EmployeeIndex.php
│   ├── EmployeeForm.php             → triggers auto-credential creation (see §5)
│   └── EmployeeShow.php
├── Students/
│   ├── StudentIndex.php
│   ├── StudentForm.php
│   └── StudentShow.php              → shows inscriptions, encaissements, remboursements for this student
├── Groups/
│   ├── GroupIndex.php               → tabbed by statut (Pré-inscription / En formation / Historique)
│   ├── GroupForm.php
│   └── GroupHistoriqueIndex.php     → read-only archive list
├── Inscriptions/
│   ├── InscriptionIndex.php
│   ├── InscriptionForm.php          → assigns student + group, generates inscription_fees
│   └── InscriptionShow.php
├── Caisse/
│   ├── CaisseIndex.php              → per-caisse balance dashboard
│   ├── EncaissementForm.php         → payment collection UI, allocates against inscription_fees
│   ├── DepenseForm.php
│   ├── RemboursementForm.php
│   └── CaisseTransferForm.php       → the request/validate two-step flow (see §7)
└── Audit/
    └── AuditLogIndex.php            → CEO-facing fraud/traceability view (see §6)
```

**Why Livewire components instead of traditional Controller + resource routes:** every screen in this schema is data-entry-heavy with live filtering/search (matching what the original WimSchool screenshots showed — filter bars, inline modals, live tables). Livewire gives that interactivity without a separate JS framework or API layer. `routes/web.php` stays thin — mostly `Route::get('/students', StudentIndex::class)` style single-line registrations.

---

## 5. Auto-Generated Employee Credentials — The Specific Requirement

You asked: creating an `employees` record should **automatically create its login (username + password)**, not require a manual separate step. This is implemented as a Laravel **model event/observer**, not inline in the Livewire form (so it fires no matter where an employee gets created — including via a future import feature).

```
app/Observers/EmployeeObserver.php
app/Services/EmployeeCredentialService.php
```

### `app/Services/EmployeeCredentialService.php`

```php
<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;

class EmployeeCredentialService
{
    /**
     * Generates a unique username from the employee's name (e.g. "j.dupont"),
     * a random temporary password, creates the User row, links it back to
     * the Employee, and returns the plaintext password ONCE so it can be
     * shown to the admin creating the account or sent to the employee.
     * The plaintext password is never stored or logged anywhere after this call.
     */
    public function createCredentialsFor(Employee $employee): string
    {
        $username = $this->generateUniqueUsername($employee);
        $plainPassword = Str::password(12);

        $user = User::create([
            'name' => "{$employee->prenom} {$employee->nom}",
            'email' => $employee->email ?? "{$username}@gls-crm.local", // placeholder if no real email yet
            'username' => $username,
            'password' => bcrypt($plainPassword),
            'must_change_password' => true, // force reset on first login — see §5 note below
        ]);

        $employee->update(['user_id' => $user->id]);

        return $plainPassword;
    }

    private function generateUniqueUsername(Employee $employee): string
    {
        $base = Str::lower(Str::substr($employee->prenom, 0, 1) . '.' . Str::slug($employee->nom));
        $username = $base;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        return $username;
    }
}
```

### `app/Observers/EmployeeObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\EmployeeCredentialService;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        // Only auto-create credentials if this employee doesn't already have one
        // (protects against double-firing if user_id was set manually on creation).
        if (is_null($employee->user_id)) {
            $plainPassword = app(EmployeeCredentialService::class)->createCredentialsFor($employee);

            // Flash the one-time password to the session so the creating admin's
            // UI can display it once. It is NEVER persisted anywhere in plaintext.
            session()->flash('new_employee_password', $plainPassword);
        }
    }
}
```

Registered in `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    Employee::observe(EmployeeObserver::class);
}
```

**Why an Observer, not logic inside the Livewire form:** an observer fires on `Employee::create()` no matter what triggers it — the manual "Ajouter un employé" form, a future bulk-import feature, a seeder, an API endpoint you build later. Putting this logic inside `EmployeeForm.php` would mean re-implementing it (or forgetting to) everywhere else an Employee gets created.

**Required addition to the `users` migration:** the default Laravel `users` table needs two extra columns for this to work — `username` (string, unique) and `must_change_password` (boolean, default true). Add these to Laravel's stock `create_users_table` migration:

```php
$table->string('username')->unique()->nullable();
$table->boolean('must_change_password')->default(true);
```

**`must_change_password` is a deliberate security choice, not decoration:** since the system generates the password rather than the employee choosing it, force a password-reset flow on first login (`app/Http/Middleware/ForcePasswordChange.php`, applied to all authenticated routes except the reset-password route itself). Skipping this means every employee's real password is a random string an admin saw once on screen — acceptable briefly, not as a permanent state.

**Login is by username, not email**, since `email` on `Employee` is nullable (per the schema — not every staff member necessarily has one on file) but `username` is always auto-generated and guaranteed unique. Update `config/auth.php` and the login form/`LoginController` accordingly (`Auth::attempt(['username' => ..., 'password' => ...])`).

---

## 6. Audit Logging — Wiring `spatie/laravel-activitylog`

```
config/activitylog.php                  (published by the package)
database/migrations/xxxx_create_activity_log_table.php   (published by the package — this IS your audit_logs table, renamed)
```

The package's own migration creates an `activity_log` table shaped almost exactly like the `audit_logs` table designed in `gls-crm-schema.md`. Rather than building a custom table, **use the package's table directly** — publish its migration and rename it to `audit_logs` for consistency with the schema doc, or just keep the package's default name and treat `audit_logs` in the schema doc as documentation of its shape rather than a literal migration you write yourself.

Add the `LogsActivity` trait to every model that needs fraud/traceability, per the schema doc's list:

```php
// app/Models/Encaissement.php, Depense.php, Remboursement.php, CaisseTransfer.php, Inscription.php, Student.php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Encaissement extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['montant', 'methode', 'caisse_id', 'inscription_fee_id']) // only log fields that matter for fraud review
            ->logOnlyDirty()
            ->useLogName('encaissement');
    }
}
```

**The one manual step this doesn't automate:** the security hardening step from `gls-crm-schema.md` (production DB user should have INSERT-only permission on the audit table, no UPDATE/DELETE) is a **deployment configuration task**, done in your hosting provider's MySQL user management — not something Laravel migrations or this package can enforce. Document it as a deploy checklist item, not code.

---

## 7. `caisse_transfers` — Request/Validate Two-Step Flow

Since this table specifically requires two different people (`requested_by` ≠ `validated_by`), the Livewire component needs two distinct actions, not one form:

```
app/Livewire/Caisse/CaisseTransferForm.php   → the "request a transfer" action (any authorized employee)
app/Livewire/Caisse/CaisseTransferValidate.php   → the "approve a pending transfer" action (director/supervisor only, gated via spatie/laravel-permission)
```

```php
// CaisseTransferForm.php — request step
public function requestTransfer(): void
{
    $this->validate([...]);

    CaisseTransfer::create([
        'reference' => $this->generateReference(),
        'caisse_source_id' => $this->caisseSourceId,
        'caisse_destination_id' => $this->caisseDestinationId,
        'montant' => $this->montant,
        'date_transfert' => now(),
        'solde_source_avant' => Caisse::find($this->caisseSourceId)->solde,
        'solde_dest_avant' => Caisse::find($this->caisseDestinationId)->solde,
        'statut' => 'En attente',
        'requested_by' => auth()->user()->employee->id,
    ]);
    // solde is NOT touched yet — only on validation, see below
}
```

```php
// CaisseTransferValidate.php — approval step, only fires balance changes
public function validateTransfer(CaisseTransfer $transfer): void
{
    $this->authorize('validate-caisse-transfer'); // spatie/laravel-permission gate

    \DB::transaction(function () use ($transfer) {
        $source = Caisse::find($transfer->caisse_source_id);
        $destination = Caisse::find($transfer->caisse_destination_id);

        $source->decrement('solde', $transfer->montant);
        $destination->increment('solde', $transfer->montant);

        $transfer->update([
            'statut' => 'Validé',
            'validated_by' => auth()->user()->employee->id,
            'solde_source_apres' => $source->fresh()->solde,
            'solde_dest_apres' => $destination->fresh()->solde,
        ]);
    });
}
```

**Why `solde` only changes at validation, not at request time:** this is the actual fraud-prevention mechanism — a requested-but-unapproved transfer must not silently move real money. If a director never approves it, the `Caisse.solde` values must reflect that nothing happened. This detail wasn't explicit in the schema doc's column list but follows directly from the "two different people" requirement — flagging it here so it isn't missed during implementation.

---

## 8. `routes/web.php` — Minimal, Livewire-Driven

```php
Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/etablissements', Livewire\Etablissements\EtablissementIndex::class);
    Route::get('/employees', Livewire\Employees\EmployeeIndex::class);
    Route::get('/students', Livewire\Students\StudentIndex::class);
    Route::get('/groups', Livewire\Groups\GroupIndex::class);
    Route::get('/groups/historique', Livewire\Groups\GroupHistoriqueIndex::class);
    Route::get('/inscriptions', Livewire\Inscriptions\InscriptionIndex::class);
    Route::get('/caisses', Livewire\Caisse\CaisseIndex::class);
    Route::get('/audit-logs', Livewire\Audit\AuditLogIndex::class)
        ->middleware('can:view-audit-logs'); // Directeur category only
});

require __DIR__.'/auth.php'; // Breeze/Fortify-style auth routes, login only — no public registration
```

**No public registration route** — this is deliberate and directly follows from "staff only, credentials auto-created": there is no `/register` page. The only way a `users` row gets created is via `EmployeeCredentialService`, triggered by an existing authorized staff member creating a new `employees` record.

---

## 9. `database/factories/` and `database/seeders/`

```
database/factories/
├── EtablissementFactory.php
├── EmployeeFactory.php
├── StudentFactory.php
├── GroupFactory.php
└── ... (one per model, standard Laravel testing support)

database/seeders/
├── DatabaseSeeder.php
├── TypeDepenseSeeder.php    → seeds the fixed system types: "Paiement prof", "Salaire", "Transfert à une autre caisse", etc. (is_system = true)
└── AnneeScolaireSeeder.php  → seeds the current academic year as par_defaut = true
```

`TypeDepenseSeeder` matters specifically: the `is_system = true` rows (per `gls-crm-schema.md` §12) should be seeded once at install time, not created ad-hoc through the UI — the admin "add expense type" form should only ever create `is_system = false` rows.

---

## 10. Full Directory Tree (Summary)

```
gls-crm/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php
│   │   │   └── DashboardController.php
│   │   └── Middleware/ForcePasswordChange.php
│   ├── Livewire/
│   │   ├── Etablissements/
│   │   ├── Employees/
│   │   ├── Students/
│   │   ├── Groups/
│   │   ├── Inscriptions/
│   │   ├── Caisse/
│   │   └── Audit/
│   ├── Models/
│   │   ├── Etablissement.php
│   │   ├── AnneeScolaire.php
│   │   ├── Salle.php
│   │   ├── Employee.php
│   │   ├── Student.php
│   │   ├── Group.php
│   │   ├── GroupHistorique.php
│   │   ├── Inscription.php
│   │   ├── InscriptionFee.php
│   │   ├── Caisse.php
│   │   ├── Encaissement.php
│   │   ├── TypeDepense.php
│   │   ├── Depense.php
│   │   ├── Remboursement.php
│   │   └── CaisseTransfer.php
│   ├── Observers/EmployeeObserver.php
│   └── Services/EmployeeCredentialService.php
├── database/
│   ├── migrations/ (§2)
│   ├── factories/
│   └── seeders/
├── resources/views/livewire/ (Blade views paired 1:1 with each Livewire component above)
├── routes/
│   ├── web.php
│   └── auth.php
└── docs/
    ├── gls-crm-schema.md            ← the schema this structure implements
    └── gls-crm-laravel-structure.md  ← this file
```

---

*End of gls-crm-laravel-structure.md. Read alongside `gls-crm-schema.md` — that file explains what each table is and why; this file explains where the code that implements it lives.*
