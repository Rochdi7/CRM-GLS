<?php

declare(strict_types=1);

namespace App\Support\Database;

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
     * Every table of the connected database with its exact row count.
     *
     * @return list<array{name: string, rows: int, columns: int, readOnly: bool, editable: bool}>
     */
    public function tables(): array
    {
        $tables = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $name) {
            $columns = Schema::getColumns($name);

            $tables[] = [
                'name' => $name,
                'rows' => DB::table($name)->count(),
                'columns' => count($columns),
                'readOnly' => $this->isReadOnly($name),
                'editable' => ! $this->isReadOnly($name) && $this->primaryKey($name) !== [],
            ];
        }

        usort($tables, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $tables;
    }

    public function exists(string $table): bool
    {
        return preg_match('/^[a-z0-9_]+$/', $table) === 1
            && in_array($table, Schema::getTableListing(schemaQualified: false), true);
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
     *     references: array{table: string, column: string}|null
     * }>
     */
    public function columns(string $table): array
    {
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
            ];
        }

        return $columns;
    }

    /**
     * Primary-key columns (composite keys supported) — empty when the table
     * has none, in which case rows cannot be edited or deleted.
     *
     * @return list<string>
     */
    public function primaryKey(string $table): array
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary']) {
                return array_values($index['columns']);
            }
        }

        return [];
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
