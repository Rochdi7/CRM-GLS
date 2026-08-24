<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cash register / till (gls-crm-schema.md §10).
 *
 * ⚠ `solde` is a stored, application-updated number — NOT computed from a
 * ledger (deliberate trade-off). Every encaissement, depense, remboursement
 * and validated caisse_transfer MUST adjust it inside the same DB transaction.
 */
class Caisse extends Model
{
    use Auditable;
    use HasFactory;

    public const TYPE_CAISSIERE = 'Caissière';
    public const TYPE_EXTERNE = 'Externe';
    public const TYPE_TPE = Encaissement::METHODE_TPE;
    public const TYPE_CHEQUE = Encaissement::METHODE_CHEQUE;
    public const TYPE_VIREMENT = Encaissement::METHODE_VIREMENT;

    /**
     * Kinds of account (every one is a real `caisses` row):
     *
     *  - "Caissière" — an employee's own PHYSICAL till (Espèces only),
     *    created by CaisseProvisioner (EmployeeObserver), never by hand.
     *  - "Externe"   — a safe or outside cash holder; the ONLY type a user
     *    can create, from the « Comptes de caisse » tab. Cash as well.
     *  - "TPE" / "Chèque" / "Virement" — one PAYMENT-METHOD ACCOUNT per
     *    centre (`etablissement_id` NOT NULL, no responsable), provisioned
     *    with the centre (EtablissementObserver / CaisseProvisioner) and
     *    self-healed by CaisseResolver. Money reaches them ONLY through a
     *    payment whose `methode` matches — never through a transfer.
     *
     * Every dirham lives in exactly ONE of these rows: a TPE payment credits
     * the centre's TPE account and nothing else (24/08/2026 refactor — before
     * it, every method credited the cashier's physical till and the three
     * method balances were derived on top of it, i.e. counted twice).
     */
    public const TYPES = [
        self::TYPE_CAISSIERE,
        self::TYPE_EXTERNE,
        self::TYPE_TPE,
        self::TYPE_CHEQUE,
        self::TYPE_VIREMENT,
    ];

    /** Accounts that hold physical cash — the only ones a till transfer may touch. */
    public const TYPES_ESPECES = [
        self::TYPE_CAISSIERE,
        self::TYPE_EXTERNE,
    ];

    /** One per centre; the account a non-cash `methode` routes to. Keyed by the method itself. */
    public const TYPES_METHODE = [
        self::TYPE_TPE,
        self::TYPE_CHEQUE,
        self::TYPE_VIREMENT,
    ];

    public const STATUT_ACTIVE = 'Active';
    public const STATUT_INACTIVE = 'Inactive';

    public const STATUTS = [
        self::STATUT_ACTIVE,
        self::STATUT_INACTIVE,
    ];

    /**
     * `solde` stays fillable for test fixtures only; production code never
     * passes it (StoreCaisseRequest/UpdateCaisseRequest have no rule for it,
     * CaisseController hardcodes 0) — the balance moves only through
     * CaisseLedger.
     */
    protected $fillable = [
        'nom', 'type', 'etablissement_id', 'responsable_employee_id', 'solde', 'statut',
    ];

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree.
     *
     * Without this, a create() that omits `statut` leaves the model holding
     * NULL while the database row holds the default. The next status change
     * then records « avant : (vide) » in the audit journal — a false statement
     * of history, since the record did have a status. See InscriptionFee,
     * where this actually produced wrong entries.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
        ];
    }

    /** Physical cash account (Caissière / Externe) — the only kind a transfer may move money between. */
    public function isEspeces(): bool
    {
        return in_array($this->type, self::TYPES_ESPECES, true);
    }

    /** Centre-level payment-method account (TPE / Chèque / Virement). */
    public function isCompteMethode(): bool
    {
        return in_array($this->type, self::TYPES_METHODE, true);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsable_employee_id');
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class);
    }

    public function transfersSortants(): HasMany
    {
        return $this->hasMany(CaisseTransfer::class, 'caisse_source_id');
    }

    public function transfersEntrants(): HasMany
    {
        return $this->hasMany(CaisseTransfer::class, 'caisse_destination_id');
    }
}
