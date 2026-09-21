<?php

declare(strict_types=1);

namespace App\Support\Database;

use App\Support\Audit\AuditValueResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * « Gestion de la base de données » — the button-driven table browser behind
 * /backoffice/database-management (DatabaseManagementController).
 *
 * Reads the live PostgreSQL catalogue (tables, columns, primary keys,
 * foreign keys) and exposes generic browse / insert / update / delete /
 * truncate / export on ANY table, so the maintainer never has to type SQL
 * to inspect or repair a row. Everything runs through the query builder with
 * quoted identifiers; table and column names are validated against the
 * catalogue first, so a name that is not in the schema never reaches SQL.
 *
 * ⚠ Reserved to the maintenance IDENTITY (`HiddenAccount::EMAIL`), never a
 * permission — see DatabaseManagementController. This class trusts its
 * caller for authorization and only enforces STRUCTURAL rules:
 *
 *  - `activity_log` is READ-ONLY here. The audit journal is append-only at
 *    model level (App\Models\Activity throws on update/delete) and this tool
 *    bypasses Eloquent, so it must refuse on its own or it becomes the one
 *    door through which the trail can be rewritten (CLAUDE.md §11).
 *  - `migrations` is read-only too: editing it by hand desynchronises the
 *    migrator from the real schema.
 *  - A table without a primary key can be browsed but not edited row by row
 *    (there is no way to name ONE row).
 *
 * ⚠ Writes here bypass every Domain action and Eloquent observer — no
 * `caisses.solde` adjustment, no `Auditable` diff, no cascade. That is the
 * point of a repair tool, and why every write is journaled by the controller
 * under the `database` log name with the full before/after row.
 */
final class DatabaseBrowser
{
    /** Tables the tool may show but never write to. */
    public const READ_ONLY_TABLES = ['activity_log', 'migrations'];

    /** Column types rendered/edited as a multi-line text box. */
    private const TEXT_TYPES = ['text', 'json', 'jsonb'];

    /**
     * Columns whose allowed values are a CLOSED set, as `table.column` =>
     * the model constant that defines it — so the edit modal offers them as
     * a dropdown instead of asking the maintainer to spell « Payé
     * partiellement » or « Fin de formation » exactly right.
     *
     * ⚠ Keyed by TABLE AND COLUMN, never by column name alone. `statut`
     * exists on eighteen tables with sets that are NOT interchangeable —
     * Actif/Inactif on `banques`, Active/Inactive on `caisses` and `salles`,
     * En attente/Approuvée/Refusée/Annulée on `depenses`. A map keyed on
     * `statut` would offer a repair tool the wrong vocabulary on most of the
     * tables it covers, which is worse than a free-text box: the value would
     * look validated while being wrong.
     *
     * The VALUES are read from the model constant at request time, never
     * copied here (CLAUDE.md §11: these columns are plain VARCHARs validated
     * against model constants, and those constants are the authority). A
     * value added to `Depense::STATUTS` therefore appears in this dropdown
     * with no change to this file.
     *
     * Deliberately ABSENT — a repair tool must be able to write what the
     * model does not list:
     *  - `inscriptions.statut`, whose constant is narrower than what is
     *    stored (`Expirée` / `Archivée` legacy rows exist, and repairing one
     *    is exactly why this screen is open);
     *  - `import_batches.status`, which has no enumerating constant;
     *  - every behavioural SUBSET (`Caisse::TYPES_ESPECES`,
     *    `Group::STATUTS_HISTORIQUE`, …) — those are `whereIn` filters, not
     *    the column's value domain.
     * The dropdown keeps an unlisted stored value as an option (see the page
     * component), so a column that IS mapped still never blanks a row it
     * does not recognise.
     *
     * @var array<string, array<string, array{class-string, string}>>
     */
    private const VALUE_SETS = [
        'banques' => ['statut' => [\App\Models\Banque::class, 'STATUTS']],
        'caisses' => [
            'type' => [\App\Models\Caisse::class, 'TYPES'],
            'statut' => [\App\Models\Caisse::class, 'STATUTS'],
        ],
        'caisse_transfers' => ['statut' => [\App\Models\CaisseTransfer::class, 'STATUTS']],
        'cheques' => [
            'source' => [\App\Models\Cheque::class, 'SOURCES'],
            'type' => [\App\Models\Cheque::class, 'TYPES'],
            'statut' => [\App\Models\Cheque::class, 'STATUTS'],
        ],
        'depenses' => [
            'statut' => [\App\Models\Depense::class, 'STATUTS'],
            'methode_paiement' => [\App\Models\Depense::class, 'METHODES'],
        ],
        'employees' => [
            'categorie' => [\App\Models\Employee::class, 'CATEGORIES'],
            'statut' => [\App\Models\Employee::class, 'STATUTS'],
            'sexe' => [\App\Models\Employee::class, 'SEXES'],
        ],
        'encaissements' => ['methode' => [\App\Models\Encaissement::class, 'METHODES']],
        'frais' => ['statut' => [\App\Models\Frais::class, 'STATUTS']],
        'groups' => [
            'statut' => [\App\Models\Group::class, 'STATUTS'],
            'niveau' => [\App\Models\Group::class, 'NIVEAUX'],
        ],
        'group_enseignants' => ['statut' => [\App\Models\GroupEnseignant::class, 'STATUTS']],
        'group_frais' => ['classification' => [\App\Models\Group::class, 'NIVEAUX']],
        'import_batches' => ['module' => [\App\Models\ImportBatch::class, 'MODULES']],
        'import_rows' => ['status' => [\App\Models\ImportRow::class, 'STATUTS']],
        'inscription_fees' => ['statut' => [\App\Models\InscriptionFee::class, 'STATUTS']],
        'motifs_annulation' => [
            'statut' => [\App\Models\MotifAnnulation::class, 'STATUTS'],
            'portee' => [\App\Models\MotifAnnulation::class, 'PORTEES'],
        ],
        'presences' => ['statut' => [\App\Models\Presence::class, 'STATUTS']],
        'salles' => ['statut' => [\App\Models\Salle::class, 'STATUTS']],
        'seances' => ['statut' => [\App\Models\Seance::class, 'STATUTS']],
        'stock_articles' => ['statut' => [\App\Models\StockArticle::class, 'STATUTS']],
        'stock_mouvements' => ['type' => [\App\Models\StockMouvement::class, 'TYPES']],
        'stock_types' => ['statut' => [\App\Models\StockType::class, 'STATUTS']],
        'students' => [
            'niveau' => [\App\Models\Student::class, 'NIVEAUX'],
            'domaine' => [\App\Models\Student::class, 'DOMAINES'],
            'examen_type' => [\App\Models\Student::class, 'EXAMEN_TYPES'],
            'sexe' => [\App\Models\Student::class, 'SEXES'],
            'parent_sexe' => [\App\Models\Student::class, 'SEXES'],
            'parent_relation' => [\App\Models\Student::class, 'PARENT_RELATIONS'],
        ],
        'types_depenses' => ['statut' => [\App\Models\TypeDepense::class, 'STATUTS']],
    ];

    /**
     * Resolves a foreign-key id to the name it points at, shared with the
     * audit journal so both screens read an id the same way.
     */
    private ?AuditValueResolver $resolver = null;

    /**
     * Per-request memo of the catalogue reads.
     *
     * `columns()` alone costs four introspection queries (tables, indexes,
     * foreign keys, columns) and a single page calls it from `rows()`,
     * `show()` and `foreignKeyLabels()`. The schema cannot change under a
     * request, so reading it once per table is correct as well as cheaper.
     *
     * @var array<string, list<array<string, mixed>>>
     */
    private array $columnCache = [];

    /** @var array<string, list<string>> */
    private array $primaryKeyCache = [];

    /** @var list<string>|null */
    private ?array $tableNameCache = null;

    /**
     * Every table of the connected database with its exact row count.
     *
     * `rows` is null and `accessible` false when the application role may
     * not read the table — production 09/09/2026: `tmp_caisse_snapshot_0901`,
     * a repair snapshot created by the `postgres` superuser, threw
     * « permission denied » on the count and took the whole index down with
     * it. One odd table must never do that: the maintainer needs this
     * screen most precisely when something is wrong. The fix is on the
     * server side (`ALTER TABLE … OWNER TO gls_crm_app`, or drop it) — the
     * page only reports it.
     *
     * @return list<array{name: string, rows: int|null, columns: int, readOnly: bool, editable: bool, accessible: bool}>
     */
    public function tables(): array
    {
        $tables = [];

        foreach ($this->tableNames() as $name) {
            try {
                $rows = DB::table($name)->count();
            } catch (\Throwable) {
                $rows = null;
            }

            $tables[] = [
                'name' => $name,
                'rows' => $rows,
                'columns' => count(Schema::getColumns($name)),
                'readOnly' => $this->isReadOnly($name),
                'editable' => $rows !== null && ! $this->isReadOnly($name) && $this->primaryKey($name) !== [],
                'accessible' => $rows !== null,
            ];
        }

        usort($tables, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $tables;
    }

    public function exists(string $table): bool
    {
        return preg_match('/^[a-z0-9_]+$/', $table) === 1
            && in_array($table, $this->tableNames(), true);
    }

    /**
     * Bare table names of the connection's OWN schema(s) — `search_path`,
     * i.e. `public` (config/database.php).
     *
     * ⚠ Without the schema argument, Laravel's PostgreSQL grammar lists
     * every non-system schema of the database. A table living in another
     * schema then comes back under its bare name, which the query builder
     * resolves through `search_path` and cannot find — « relation does not
     * exist », a 500 on the index for a table the tool could never have
     * shown anyway. The local database has a single schema so this never
     * surfaced there; the server's may not.
     *
     * @return list<string>
     */
    private function tableNames(): array
    {
        return $this->tableNameCache ??= Schema::getTableListing(
            Schema::getCurrentSchemaListing(),
            schemaQualified: false,
        );
    }

    /**
     * Throws when the table is not in the catalogue — the single funnel every
     * public method goes through, so a forged name never reaches SQL.
     */
    public function assertExists(string $table): void
    {
        if (! $this->exists($table)) {
            throw new InvalidArgumentException("Unknown table [{$table}].");
        }
    }

    public function isReadOnly(string $table): bool
    {
        return in_array($table, self::READ_ONLY_TABLES, true);
    }

    /**
     * Column metadata as the page needs it.
     *
     * @return list<array{
     *     name: string, type: string, nullable: bool, default: string|null,
     *     autoIncrement: bool, primary: bool, input: string,
     *     references: array{table: string, column: string}|null,
     *     values: list<string>|null
     * }>
     */
    public function columns(string $table): array
    {
        if (isset($this->columnCache[$table])) {
            return $this->columnCache[$table];
        }

        $this->assertExists($table);

        $primary = $this->primaryKey($table);
        $foreign = [];

        foreach (Schema::getForeignKeys($table) as $fk) {
            if (count($fk['columns']) === 1) {
                $foreign[$fk['columns'][0]] = [
                    'table' => $fk['foreign_table'],
                    'column' => $fk['foreign_columns'][0],
                ];
            }
        }

        $columns = [];

        foreach (Schema::getColumns($table) as $column) {
            $columns[] = [
                'name' => $column['name'],
                'type' => $column['type'],
                'nullable' => (bool) $column['nullable'],
                'default' => $column['default'] !== null ? (string) $column['default'] : null,
                'autoIncrement' => (bool) $column['auto_increment']
                    || str_starts_with((string) $column['default'], 'nextval('),
                'primary' => in_array($column['name'], $primary, true),
                'input' => $this->inputKind($column['type_name'], $column['type']),
                'references' => $foreign[$column['name']] ?? null,
                'values' => $this->valueSetOf($table, $column['name']),
            ];
        }

        return $this->columnCache[$table] = $columns;
    }

    /**
     * Primary-key columns (composite keys supported) — empty when the table
     * has none, in which case rows cannot be edited or deleted.
     *
     * @return list<string>
     */
    public function primaryKey(string $table): array
    {
        if (isset($this->primaryKeyCache[$table])) {
            return $this->primaryKeyCache[$table];
        }

        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary']) {
                return $this->primaryKeyCache[$table] = array_values($index['columns']);
            }
        }

        return $this->primaryKeyCache[$table] = [];
    }

    /**
     * Server-side paginated rows, searched across every column (cast to text,
     * ILIKE — CLAUDE.md §17) and sorted on a validated column.
     *
     * @param  array{search?: string, sort?: string, direction?: string, perPage?: int|string}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function rows(string $table, array $filters): LengthAwarePaginator
    {
        $this->assertExists($table);

        $columns = array_column($this->columns($table), 'name');
        $primary = $this->primaryKey($table);

        $query = DB::table($table);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $grammar = $query->getGrammar();
            $like = '%'.$search.'%';

            $query->where(function (Builder $q) use ($columns, $grammar, $like): void {
                foreach ($columns as $column) {
                    $q->orWhereRaw('CAST('.$grammar->wrap($column).' AS TEXT) ILIKE ?', [$like]);
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? '');
        $direction = strtolower((string) ($filters['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, $columns, true)) {
            $query->orderBy($sort, $direction);
        } elseif ($primary !== []) {
            foreach ($primary as $column) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderBy($columns[0], $direction);
        }

        $perPage = max(10, min(200, (int) ($filters['perPage'] ?? 25)));

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->through(fn (object $row): array => $this->presentRow((array) $row, $primary));

        return $paginator;
    }

    /**
     * Human names behind every foreign-key value present in these rows —
     * `{"group_id": {"34": "GLS-A1-SOIR"}, …}`, batch-loaded (one query per
     * referenced table, never one per cell).
     *
     * ⚠ The NAME is a display aid; the id stays the stored truth and is
     * always shown next to it. Same rule, and the same resolver, as the
     * audit journal — a screen that showed only the name would hide which
     * row is actually referenced, and a rename would silently rewrite what
     * the user believes they saw (CLAUDE.md §11).
     *
     * @param  iterable<array{values: array<string, string|null>}>  $rows
     * @return array<string, array<string, string>>
     */
    public function foreignKeyLabels(string $table, iterable $rows): array
    {
        $foreignColumns = [];

        foreach ($this->columns($table) as $column) {
            if ($column['references'] !== null) {
                $foreignColumns[] = $column['name'];
            }
        }

        if ($foreignColumns === []) {
            return [];
        }

        $pairs = [];

        foreach ($rows as $row) {
            $values = is_array($row) ? ($row['values'] ?? $row) : (array) $row;

            foreach ($foreignColumns as $column) {
                $value = $values[$column] ?? null;

                if ($value !== null && $value !== '' && is_numeric($value)) {
                    $pairs[] = [$column, $value];
                }
            }
        }

        if ($pairs === []) {
            return [];
        }

        $resolver = $this->resolver ??= new AuditValueResolver;
        $resolver->warm($pairs);

        $labels = [];

        foreach ($pairs as [$column, $value]) {
            $name = $resolver->resolve($column, $value);

            if ($name !== null) {
                $labels[$column][(string) $value] = $name;
            }
        }

        return $labels;
    }

    /**
     * Ceiling on the options served for ONE foreign-key column. Past it the
     * field stays a plain id box rather than serving a half-list: a dropdown
     * that silently omits most rows is worse than no dropdown, because the
     * maintainer cannot tell the missing row from a row that does not exist.
     */
    public const FOREIGN_OPTIONS_CAP = 500;

    /**
     * The choosable rows behind every foreign-key column of a table —
     * `{"etablissement_id": {"options": [{"value": "3", "label": "GLS Rabat"}],
     * "truncated": false}, …}` — so the edit modal offers a NAME instead of
     * asking the maintainer to know an id by heart.
     *
     * The label comes from the same `AuditValueResolver` the audit journal
     * and `foreignKeyLabels()` use when the column is one it knows; otherwise
     * it is built from the referenced table's own first text-ish column, so
     * a pivot or a table the resolver never heard of still reads as a name.
     * The id stays the value that is stored and submitted, and is shown
     * inside the label (« GLS Rabat · #3 ») — a screen that showed only the
     * name would hide which row is referenced (CLAUDE.md §11).
     *
     * A referenced table the application role cannot read, or one larger
     * than the cap, yields no options and the field falls back to the id
     * box — never a broken page.
     *
     * @return array<string, array{options: list<array{value: string, label: string}>, truncated: bool}>
     */
    public function foreignOptions(string $table): array
    {
        $this->assertExists($table);

        $out = [];

        foreach ($this->columns($table) as $column) {
            $reference = $column['references'];

            if ($reference === null || isset($out[$column['name']])) {
                continue;
            }

            $options = $this->optionsOf($reference['table'], $reference['column'], $column['name']);

            if ($options === null) {
                continue;
            }

            $out[$column['name']] = $options;
        }

        return $out;
    }

    /**
     * Options for ONE referenced table, or null when it cannot be listed
     * (unreadable, no such table, or above the cap).
     *
     * @return array{options: list<array{value: string, label: string}>, truncated: bool}|null
     */
    private function optionsOf(string $referencedTable, string $referencedColumn, string $column): ?array
    {
        if (! $this->exists($referencedTable)) {
            return null;
        }

        try {
            $count = DB::table($referencedTable)->count();

            if ($count > self::FOREIGN_OPTIONS_CAP) {
                return null;
            }

            $labelColumns = $this->labelColumnsOf($referencedTable, $referencedColumn);

            $rows = DB::table($referencedTable)
                ->select(array_values(array_unique([$referencedColumn, ...$labelColumns])))
                ->orderBy($referencedColumn)
                ->get();
        } catch (\Throwable) {
            return null;
        }

        // The resolver names a known column far better than raw columns do
        // (it appends the student behind an inscription, for instance), so
        // warm it once for the whole list and prefer its answer per row.
        $resolver = $this->resolver ??= new AuditValueResolver;
        $ids = $rows->pluck($referencedColumn)->filter(fn ($v) => is_numeric($v))->all();
        $resolver->warm(array_map(fn ($id) => [$column, $id], $ids));

        $options = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $id = $row[$referencedColumn] ?? null;

            if ($id === null) {
                continue;
            }

            $name = $resolver->resolve($column, $id);

            if ($name === null) {
                $parts = [];

                foreach ($labelColumns as $labelColumn) {
                    $value = $row[$labelColumn] ?? null;

                    if (is_scalar($value) && trim((string) $value) !== '') {
                        $parts[] = trim((string) $value);
                    }
                }

                $name = $parts === [] ? '#'.$id : implode(' ', $parts);
            }

            $options[] = [
                'value' => (string) $id,
                'label' => $name.' · #'.$id,
            ];
        }

        usort($options, fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));

        return ['options' => $options, 'truncated' => false];
    }

    /**
     * The columns that read as a name on a referenced table, in the order a
     * human would say them — the same preference list `AuditValueResolver`
     * uses for a model, applied to a raw table.
     *
     * @return list<string>
     */
    private function labelColumnsOf(string $table, string $referencedColumn): array
    {
        $available = array_column($this->columns($table), 'name');

        foreach ([['prenom', 'nom'], ['nom_centre'], ['nom'], ['name'], ['libelle'], ['label'], ['reference'], ['titre']] as $candidate) {
            if (array_diff($candidate, $available) === []) {
                return array_values(array_diff($candidate, [$referencedColumn]));
            }
        }

        return [];
    }

    /**
     * The closed value set of a column, read from the model constant that
     * defines it, or null when the column accepts free text.
     *
     * @return list<string>|null
     */
    private function valueSetOf(string $table, string $column): ?array
    {
        $mapped = self::VALUE_SETS[$table][$column] ?? null;

        if ($mapped === null) {
            return null;
        }

        [$class, $constant] = $mapped;

        if (! defined($class.'::'.$constant)) {
            return null;
        }

        $values = constant($class.'::'.$constant);

        if (! is_array($values) || $values === []) {
            return null;
        }

        // An int-keyed label map (Creneau::JOURS) is not a VARCHAR domain;
        // only a plain list of string values is offered as options.
        $values = array_values($values);

        foreach ($values as $value) {
            if (! is_string($value)) {
                return null;
            }
        }

        return $values;
    }

    /**
     * One row, identified by its primary key values — the full untruncated
     * values, for the edit modal.
     *
     * @param  array<string, mixed>  $key
     * @return array<string, mixed>|null
     */
    public function find(string $table, array $key): ?array
    {
        $this->assertExists($table);

        $row = $this->whereKey($table, $key)->first();

        return $row === null ? null : $this->normalizeRow((array) $row);
    }

    /**
     * Inserts a row. Empty values on nullable / defaulted columns are dropped
     * so PostgreSQL applies the default (identity ids, timestamps, statut).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed> the inserted row as stored
     */
    public function insert(string $table, array $values): array
    {
        $this->assertWritable($table);

        $prepared = $this->prepare($table, $values, forInsert: true);
        $primary = $this->primaryKey($table);
        $columns = collect($this->columns($table))->keyBy('name');

        // Single auto-increment key left empty: let the sequence assign it
        // and read the row back by the id PostgreSQL returned.
        if (count($primary) === 1
            && ! array_key_exists($primary[0], $prepared)
            && ($columns[$primary[0]]['autoIncrement'] ?? false)) {
            $id = DB::table($table)->insertGetId($prepared, $primary[0]);

            return $this->find($table, [$primary[0] => $id]) ?? $this->normalizeRow($prepared);
        }

        DB::table($table)->insert($prepared);

        if ($primary !== [] && array_intersect_key($prepared, array_flip($primary)) !== []) {
            $key = array_intersect_key($prepared, array_flip($primary));

            if (count($key) === count($primary)) {
                return $this->find($table, $key) ?? $this->normalizeRow($prepared);
            }
        }

        return $this->normalizeRow($prepared);
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $values
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function update(string $table, array $key, array $values): array
    {
        $this->assertWritable($table);
        $this->assertHasPrimaryKey($table);

        $before = $this->find($table, $key);

        if ($before === null) {
            throw new InvalidArgumentException('Row not found.');
        }

        $prepared = $this->prepare($table, $values, forInsert: false);

        if ($prepared !== []) {
            $this->whereKey($table, $key)->update($prepared);
        }

        $after = $this->find($table, $this->keyAfterUpdate($table, $key, $prepared));

        return ['before' => $before, 'after' => $after ?? []];
    }

    /**
     * @param  array<string, mixed>  $key
     * @return array<string, mixed> the deleted row
     */
    public function delete(string $table, array $key): array
    {
        $this->assertWritable($table);
        $this->assertHasPrimaryKey($table);

        $before = $this->find($table, $key);

        if ($before === null) {
            throw new InvalidArgumentException('Row not found.');
        }

        $this->whereKey($table, $key)->delete();

        return $before;
    }

    /** Empties the table (DELETE, not TRUNCATE, so FK checks still apply). Returns the number of rows removed. */
    public function truncate(string $table): int
    {
        $this->assertWritable($table);

        return DB::table($table)->delete();
    }

    /**
     * Streams every row as CSV lines (header first).
     *
     * @return \Generator<int, array<int, string|null>>
     */
    public function exportRows(string $table): \Generator
    {
        $this->assertExists($table);

        $columns = array_column($this->columns($table), 'name');
        $primary = $this->primaryKey($table);

        yield $columns;

        $query = DB::table($table);

        foreach ($primary === [] ? [$columns[0]] : $primary as $column) {
            $query->orderBy($column);
        }

        foreach ($query->lazy(500) as $row) {
            $normalized = $this->normalizeRow((array) $row);

            yield array_map(
                fn (string $column) => $normalized[$column] === null ? null : (string) $normalized[$column],
                $columns,
            );
        }
    }

    // ── internals ──────────────────────────────────────────────────────

    private function assertWritable(string $table): void
    {
        $this->assertExists($table);

        if ($this->isReadOnly($table)) {
            throw new InvalidArgumentException(__('The table :table is read-only.', ['table' => $table]));
        }
    }

    private function assertHasPrimaryKey(string $table): void
    {
        if ($this->primaryKey($table) === []) {
            throw new InvalidArgumentException(__('This table has no primary key: rows cannot be edited one by one.'));
        }
    }

    /**
     * @param  array<string, mixed>  $key
     */
    private function whereKey(string $table, array $key): Builder
    {
        $primary = $this->primaryKey($table);

        if ($primary === []) {
            throw new InvalidArgumentException(__('This table has no primary key: rows cannot be edited one by one.'));
        }

        $query = DB::table($table);

        foreach ($primary as $column) {
            if (! array_key_exists($column, $key)) {
                throw new InvalidArgumentException("Missing key column [{$column}].");
            }

            $query->where($column, '=', $key[$column]);
        }

        return $query;
    }

    /**
     * The key to re-read the row with after an update that may have changed
     * a primary-key column itself.
     *
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $prepared
     * @return array<string, mixed>
     */
    private function keyAfterUpdate(string $table, array $key, array $prepared): array
    {
        foreach ($this->primaryKey($table) as $column) {
            if (array_key_exists($column, $prepared)) {
                $key[$column] = $prepared[$column];
            }
        }

        return $key;
    }

    /**
     * Coerces submitted strings into what PostgreSQL expects for each column
     * and drops what should not be written (unknown columns, auto-increment
     * ids left empty, empty values on defaulted columns at insert).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function prepare(string $table, array $values, bool $forInsert): array
    {
        $prepared = [];

        foreach ($this->columns($table) as $column) {
            $name = $column['name'];

            if (! array_key_exists($name, $values)) {
                continue;
            }

            $value = $values[$name];
            $isEmpty = $value === null || $value === '';

            if ($isEmpty) {
                if ($forInsert && ($column['autoIncrement'] || $column['default'] !== null)) {
                    continue; // let the database default apply
                }

                if ($column['nullable']) {
                    $prepared[$name] = null;

                    continue;
                }

                if ($column['autoIncrement']) {
                    continue;
                }

                // NOT NULL without default: pass it through and let PostgreSQL
                // refuse with its own message (surfaced verbatim to the modal).
                $prepared[$name] = $column['input'] === 'text' || $column['input'] === 'string' ? '' : null;

                continue;
            }

            $prepared[$name] = $this->coerce($column, $value);
        }

        return $prepared;
    }

    /**
     * @param  array{name: string, type: string, input: string}  $column
     */
    private function coerce(array $column, mixed $value): mixed
    {
        return match ($column['input']) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'integer' => is_numeric($value) ? (int) $value : $value,
            'decimal' => is_string($value) ? str_replace([' ', ','], ['', '.'], trim($value)) : $value,
            'json' => $this->coerceJson($value),
            default => is_string($value) ? $value : (is_scalar($value) ? (string) $value : json_encode($value)),
        };
    }

    private function coerceJson(mixed $value): string
    {
        if (! is_string($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        json_decode($value, true, 512);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(__('Invalid JSON: :error', ['error' => json_last_error_msg()]));
        }

        return $value;
    }

    /**
     * How the modal should render the field for this column type.
     */
    private function inputKind(string $typeName, string $type): string
    {
        return match (true) {
            $typeName === 'bool' => 'boolean',
            in_array($typeName, ['int2', 'int4', 'int8'], true) => 'integer',
            in_array($typeName, ['numeric', 'float4', 'float8'], true) => 'decimal',
            in_array($typeName, ['json', 'jsonb'], true) => 'json',
            $typeName === 'date' => 'date',
            str_starts_with($typeName, 'timestamp') => 'datetime',
            $typeName === 'time' => 'time',
            in_array($typeName, self::TEXT_TYPES, true) => 'text',
            str_starts_with($type, 'character varying') && $this->varcharLength($type) > 255 => 'text',
            default => 'string',
        };
    }

    private function varcharLength(string $type): int
    {
        return preg_match('/\((\d+)\)/', $type, $m) === 1 ? (int) $m[1] : 0;
    }

    /**
     * A row for the list: every value as a string (or null) under `values`
     * — the FULL value, so the edit modal needs no second request; the page
     * truncates for display — plus its primary-key values under `key` for
     * the row actions.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $primary
     * @return array{values: array<string, string|null>, key: array<string, string|null>}
     */
    private function presentRow(array $row, array $primary): array
    {
        $values = $this->normalizeRow($row);
        $key = [];

        foreach ($primary as $column) {
            $key[$column] = $values[$column] ?? null;
        }

        return ['values' => $values, 'key' => $key];
    }

    /**
     * PDO returns booleans and numerics as PHP types and json as strings;
     * make every value a JSON-friendly scalar or null.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $column => $value) {
            $row[$column] = match (true) {
                $value === null => null,
                is_bool($value) => $value ? 'true' : 'false',
                is_resource($value) => '[binary]',
                default => is_scalar($value) ? (string) $value : json_encode($value),
            };
        }

        return $row;
    }
}
