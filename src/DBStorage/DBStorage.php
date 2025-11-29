<?php

namespace Cyberma\LayerFrame\DBStorage;

use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\DBStorage\Traits\DBErrors;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;


class DBStorage implements IDBStorage
{
    use DBErrors;

    protected IModelMap $modelMap;

    protected $customTableFunction = null;


    public function __construct(IModelMap $modelMap)
    {
        $this->modelMap = $modelMap;
    }

    public function useTable(callable $customTableFunction): void
    {
        $this->customTableFunction = $customTableFunction;
    }

    /**
     * @param array $columnsNames - use [null] to omit "SELECT", for example for DELETE command
     * @return Builder
     */
    public function table(array $columnsNames = []): Builder
    {
        if ($columnsNames === []) {
            $columnsNames = $this->modelMap->getAllColumns();
        }

        if(isset($this->customTableFunction)) {
            $query = ($this->customTableFunction)($columnsNames);
        }
        else {
            $query = $columnsNames === [null]
                ? DB::table($this->modelMap->getTable())
                : DB::table($this->modelMap->getTable())->select($columnsNames);
        }

        if ($this->modelMap->hasSoftDeletes()) {
            $query->whereNull($this->qualify('deleted_at'));
        }

        return $query;
    }

    protected function qualify(string $column): string
    {
        return $this->modelMap->getTable() . '.' . $column;
    }

    /**
     * @param array $columnsNames
     * @param array $conditions
     * Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value'] ]
     * Short format for a single cirterium ['column', 'optional operator', 'value']
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<'
     * @param array|int[] $pagination
     * @param array|string[] $orderBy
     * @return Collection
     */
    public function getByConditions(array $columnsNames = [],
                                    array $conditions = [],
                                    array $pagination = [/*'page' => 1, 'perPage' => 20*/],
                                    array $orderBy = [/*'column' => 'id', 'order' => 'desc'*/]): Collection
    {
        $query = $this->queryByConditions($conditions, $columnsNames);
        $query = $this->addPagination($query, $pagination);
        $query = $this->addOrderBy($query, $orderBy);

        return array_key_exists('perPage', $pagination) && $pagination['perPage'] === 1 ? $query->take(1)->get() : $query->get();
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function countByConditions(array $conditions = []): int
    {
        $query = $this->queryByConditions($conditions);

        return $query->count();
    }

    /**
     * @param array $orderBy
     * @return array
     */
    protected function normalizeOrderBy(array $orderBy) : array
    {
        $primaryKey = $this->modelMap->getPrimaryKeyColumns();

        return [
            'column' => array_key_exists('column', $orderBy) ? $orderBy['column'] : $primaryKey[0],
            'order' => array_key_exists('order', $orderBy) ? $orderBy['order'] : 'desc',
        ];
    }

    /**
     * @param array $conditions
     * @return array|array[]
     */
    public function normalizeConditions(array $conditions): array
    {
        // CASE A: Single condition format: ['col', 'value'] OR ['col', 'op', 'value']
        if (!empty($conditions)
            && isset($conditions[0])
            && !is_array($conditions[0])
        ) {
            return [$this->normalizeSingleCondition($conditions)];
        }

        // CASE B: Multi-condition format: [ ['col', ...], ['col', ...] ]
        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                throw new \InvalidArgumentException("Invalid condition format.");
            }
        }

        return array_map(
            fn($c) => $this->normalizeSingleCondition($c),
            $conditions
        );
    }

    /**
     * @param array $condition
     * @return array
     */
    public function normalizeSingleCondition(array $condition): array
    {
        // ['column', 'value'] → turn into ['column', '=', 'value']
        if (count($condition) === 2) {
            return [$condition[0], '=', $condition[1]];
        }

        // assume correct: ['column', 'operator', 'value']
        if (count($condition) === 3) {
            return $condition;
        }

        throw new \InvalidArgumentException("Invalid condition format.");
    }

    /**
     * @param array $pagination
     * @return array
     */
    protected function normalizePagination(array $pagination) : array
    {
        return [
            'page' => array_key_exists('page', $pagination) ? $pagination['page'] : 1,
            'perPage' => array_key_exists('perPage', $pagination) ? $pagination['perPage'] : 20,
        ];
    }

    /**
     * @param array $conditions
     * Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value',] ]
     * Short format for a single cirterium ['column', 'optional operator', 'value', ]
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<', 'in'
     * @param array $columnNames - use [null] to omit SELECT, e.g. for DELETE
     * @return Builder
     */
    public function queryByConditions(array $conditions, array $columnNames = []) : Builder
    {
        $query = $this->table($columnNames);
        $normalized = $this->normalizeConditions($conditions);

        foreach($normalized as [$column, $operator, $value]) {
            $this->prepareQueryWhere($query, $column, $value, $operator);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     * @param string $operator
     * @return Builder
     */
    public function prepareQueryWhere(Builder &$query, string $column, $value, string $operator = '=') : Builder
    {
        $column = $this->qualify($column);
        // Normalize operator (case-insensitive, trim spaces)
        $op = strtolower(trim($operator));
        // Normalize synonyms
        $op = match ($op) {
            'notin' => 'not in',
            'not_in' => 'not in',
            'is null' => 'null',
            'is not null' => 'not null',
            default => $op,
        };

        switch ($op) {

            /* LIKE OPERATORS */
            case '%like%':
                return $query->where($column, 'like', '%' . $value . '%');

            case 'like':
                return $query->where($column, 'like', $value);

            case 'like%':
                return $query->where($column, 'like', $value . '%');

            case '%like':
                return $query->where($column, 'like', '%' . $value);


            /* IN OPERATORS */
            case 'in':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("IN operator requires array value.");
                }
                return $query->whereIn($column, $value);

            case 'not in':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("NOT IN operator requires array value.");
                }
                return $query->whereNotIn($column, $value);


            /* BETWEEN */
            case 'between':
                if (!is_array($value) || count($value) !== 2) {
                    throw new \InvalidArgumentException("BETWEEN operator requires [min, max].");
                }
                return $query->whereBetween($column, $value);


            /* NULL CHECKS */
            case 'null':
                return $query->whereNull($column);

            case 'not null':
                return $query->whereNotNull($column);


            /* DATE OPERATORS */
            case 'date=':
            case 'date>':
            case 'date>=':
            case 'date<':
            case 'date<=':
                $pure = substr($op, 4); // remove "date"
                return $query->where($column, $pure, $value);


            /* DEFAULT SIMPLE OPERATOR (=, <, >, <=, >=, !=, etc.) */
            default:
                return $query->where($column, $op, $value);
        }
    }

    /**
     * @param Builder $query
     * @param int $limit
     * @return Collection
     */
    public function get(Builder $query, int $limit = 100): Collection
    {
        return $query->limit($limit)->get();
    }

    /**
     * @param Builder $query
     * @return stdClass
     */
    public function first(Builder $query): ?StdClass
    {
        return $query->first();
    }

    /**
     * @param Builder $query
     * @param array $pagination
     * @return Builder
     */
    public function addPagination(Builder $query, array $pagination): Builder
    {
        $pagination = $this->normalizePagination($pagination);

        return $query->skip(($pagination['page'] - 1) * $pagination['perPage'])->take($pagination['perPage']);
    }

    /**
     * @param Builder $query
     * @param array $orderBy
     * @return Builder
     */
    public function addOrderBy(Builder $query, array $orderBy): Builder
    {
        $orderBy = $this->normalizeOrderBy($orderBy);

        return $query->orderBy($orderBy['column'], $orderBy['order']);
    }

    /**
     * @param int $id
     * @param array $columnsNames
     * @return stdClass
     */
    public function getById(int $id, array $columnsNames = []): ?StdClass
    {
        $primaryKey = $this->modelMap->getPrimaryKeyColumns()[0];

        return $this->table($columnsNames)->where($primaryKey, $id)->first();
    }

    /**
     * @param array $primaryKeyColumns
     * @param array $columnsNames
     * @return stdClass
     */
    public function getByPrimaryKey(array $primaryKeyColumns, array $columnsNames = []): ?StdClass
    {
        $query = $this->table($columnsNames);
        foreach($this->modelMap->getPrimaryKeyColumns() as $keyColumn) {
            $query->where($keyColumn, $primaryKeyColumns[$keyColumn]);
        }

        return $query->first();
    }

    /**
     * @param string $column
     * @param string|int $value
     * @param array $columnsNames
     * @return stdClass
     */
    public function getSingle(string $column, string|int $value, array $columnsNames = []): ?StdClass
    {
        return $this->table($columnsNames)->where($column, $value)->first();
    }

    /**
     * @param array $keywords
     * @param array $searchedColumns
     * @param array $columnsNames
     * @param array $pagination
     * @param array $orderBy
     * @return Collection
     */
    public function searchInColumns(array $keywords, array $searchedColumns, $columnsNames = [],
                                    array $pagination = [], array $orderBy = []): Collection
    {
        $query = $this->table($columnsNames);
        $query = $this->addOrderBy($query, $orderBy);
        $query = $this->addPagination($query, $pagination);

        foreach($searchedColumns as $column) {
            $query = $query->orWhere(function($query) use($column, $keywords){
                foreach($keywords as $keyword) {
                    $query = $query->where($column, 'like', '%' . $keyword . '%');
                }
                return $query;
            });
        }

        return $query->get();
    }

    protected function hasPrimaryKeySet(array $columns, string $primaryKey): bool
    {
        return isset($columns[$primaryKey]) && !empty($columns[$primaryKey]);
    }

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function store(array $columns): array
    {
        if($this->modelMap->hasTimeStamps()) {
            $columns = $this->addTimeStamps($columns);
        }

        // Composite primary keys;
        if(!$this->modelMap->isPrimaryAutoIncrement()) {
            return $this->storeByCompositePrimaryKey($columns);
        }

        //Add new DB entry or update existing one, if the primary key (ID) exists
        $primaryKey = $this->modelMap->getPrimaryKeyColumns()[0];
        $table = $this->modelMap->getTable();

        //If Primary Key is missing → INSERT NEW
        if (!$this->hasPrimaryKeySet($columns, $primaryKey)) {
            $columns[$primaryKey] = $this->insert($columns, $table);
            
            return $columns;
        }

        // If PK exists → CHECK RECORD EXISTENCE
        $affected = $this->updateByPrimaryKey($columns, $primaryKey);

        if ($affected === 0) {
            // UPDATE failed → INSERT instead
            $columns[$primaryKey] = $this->insert($columns, $table);
        }

        return $columns;
    }

    /**
     * If the primary key has more variables, check the existance first, then add or update
     *
     * @param array $columns
     * @return array
     */
    protected function storeByCompositePrimaryKey(array $columns): array
    {
        $primaryKeyColumns = $this->modelMap->getPrimaryKeyColumns();
        $table = $this->modelMap->getTable();

        if($this->modelMap->hasTimeStamps()) {
            $columns = $this->addTimeStamps($columns);
        }

        // Build base query for matching PK
        $query = DB::table($table);
        foreach ($primaryKeyColumns as $key) {
            if (!array_key_exists($key, $columns)) {
                throw new CodeException(
                    "Missing composite primary key column: {$key}",
                    'lf2119',
                    ['columns' => $columns]
                );
            }
            $query->where($key, $columns[$key]);
        }

        try {
            // Attempt UPDATE first
            $affected = $query->limit(1)->update($columns);

            if ($affected === 0) {
                // If no rows updated → INSERT
                DB::table($table)->insert($columns);
            }

            return $columns;
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param Collection $columnsSet
     * @return Collection
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function storeMultiple(Collection $columnsSet) : Collection
    {
        $primaryKeys = $this->modelMap->getPrimaryKeyColumns();
        if (count($primaryKeys) > 1) {
            throw new CodeException(
                'storeMultiple is not implemented for composite primary keys.',
                'lf2105'
            );
        }

        $primaryKey = $primaryKeys[0];
        $table = $this->modelMap->getTable();

        foreach ($columnsSet as $index => $columns) {

            // 1. timestamps
            if ($this->modelMap->hasTimeStamps()) {
                $columns = $this->addTimeStamps($columns);
            }

            // 2. Try UPDATE if PK exists
            if (array_key_exists($primaryKey, $columns)) {

                $affected = $this->updateByPrimaryKey($columns, $primaryKey);

                if ($affected === 0) {
                    // 3. No row updated → INSERT new row
                    $columns[$primaryKey] = $this->insert($columns, $table);
                }
            }
            else {
                // 4. No PK → INSERT new row
                $columns[$primaryKey] = $this->insert($columns, $table);
            }

            // 5. Save final result back into the collection
            $columnsSet[$index] = $columns;
        }

        return $columnsSet;
    }

    /**
     * @param array $columns
     * @param string $primaryKeyColumn
     * @return bool
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    protected function updateByPrimaryKey(array $columns, string $primaryKeyColumn) : bool
    {
        // Basic validation: we MUST have the primary key
        if (!array_key_exists($primaryKeyColumn, $columns)) {
            throw new CodeException(
                "Missing primary key '{$primaryKeyColumn}' in updateByPrimaryKey",
                'lf2107',
                ['columns' => $columns]
            );
        }

        // No columns to update → return 0 affected rows
        if (count($columns) === 1) { // only Primary key present
            return 0;
        }

        $table = $this->modelMap->getTable();
        $pkValue = $columns[$primaryKeyColumn];

        try {
            // 3. Perform the update
            $affected = DB::table($table)
                ->where($primaryKeyColumn, $pkValue)
                ->limit(1)
                ->update($columns);

            // Always return int, never bool/null
            return (int)$affected;
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param IModelMap $modelMap
     * @param array $columns
     * @return array
     */
    protected function addTimeStamps(array $columns): array
    {
        $now = Carbon::now()->toDateTimeString();
        $primaryKey = $this->modelMap->getPrimaryKeyColumns()[0];

        // Always update updated_at
        $columns['updated_at'] = $now;

        // created_at only when PK missing → new record
        if (!array_key_exists($primaryKey, $columns)) {
            $columns['created_at'] = $now;
        }

        return $columns;
    }

    /**
     * @param array $columns
     * @param string $table
     * @return int
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    protected function insert(array $columns, string $table): int
    {
        try {
            $id = DB::table($table)->insertGetId($columns);

            // insertGetId MUST return scalar (int or string)
            if (!is_scalar($id)) {
                throw new CodeException(
                    'Database did not return a valid primary key after insert.',
                    'lf2130',
                    ['returned_id' => $id, 'table' => $table]
                );
            }

            // convert to integer explicitly
            $id = (int)$id;

            if ($id <= 0) {
                throw new CodeException(
                    'Insert returned an invalid primary key (<= 0).',
                    'lf2131',
                    ['returned_id' => $id, 'table' => $table]
                );
            }

            return $id;
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }


    /**
     * @param array $columns
     * @param array $conditions
     * @return int
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function update (array $columns, array $conditions) : int
    {
        if(count($columns) === 0 || empty($conditions))
            return 0;

        if($this->modelMap->hasTimeStamps()) {
            $columns['updated_at'] = Carbon::now()->toDateTimeString();
        }

        // Normalize all conditions into [column, operator, value]
        $normalized = $this->normalizeConditions($conditions);

        try {
            $query = DB::table($this->modelMap->getTable());

            foreach($normalized as [$column, $operator, $value]) {
                $this->prepareQueryWhere($query, $column, $value, $operator);
            }

            return $query->update($columns);   //returns number of affected rows
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param array $selectedColumns
     * @return int - number of affected rows
     * @throws CodeException
     * @throws Exception
     */
    public function patchById(array $selectedColumns) : int
    {
        $primaryKey = $this->modelMap->getPrimaryKeyColumns()[0];
        if(!array_key_exists($primaryKey, $selectedColumns)) {
            throw new CodeException(_('The model you would like to patch is missing the primary key.'), 'lf2106',
                [
                    'selectedColumns' => $selectedColumns
                ]);
        }

        if($this->modelMap->hasTimeStamps()) {
            $selectedColumns['updated_at'] = Carbon::now()->toDateTimeString();
        }

        $updatingStatus = $this->updateByPrimaryKey($selectedColumns, $primaryKey);

        return $updatingStatus;
    }

    /**
     * @param array $selectedColumns
     * @param array $conditions
     * @return int - number of affected rows
     * @throws CodeException
     * @throws Exception
     */
    public function patchByConditions(array $selectedColumns, array $conditions) : int
    {
        if($this->modelMap->hasTimeStamps()) {
            $selectedColumns['updated_at'] = Carbon::now()->toDateTimeString();
        }

        $updatingStatus = $this->update($selectedColumns, $conditions);

        return $updatingStatus;
    }

    /**
     * @param int $id
     * @param bool $permanentDelete
     * @return int
     */
    public function deleteById(int $id, bool $permanentDelete = false): int
    {
        $table = $this->modelMap->getTable();
        $primaryKey = $this->modelMap->getPrimaryKeyColumns()[0];

        try {
            if ($this->modelMap->hasSoftDeletes() && !$permanentDelete) {
                // Correct column name
                $updatedColumns = [
                    'deleted_at' => Carbon::now()->toDateTimeString()
                ];

                // Soft delete — must use unqualified column names
                return DB::table($table)
                    ->where($primaryKey, $id)
                    ->whereNull('deleted_at')
                    ->limit(1)
                    ->update($updatedColumns);
            }

            // Hard delete
            return DB::table($table)
                ->where($primaryKey, $id)
                ->limit(1)
                ->delete();
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param array $primaryKeyColumns - array of primary keys, if not all provided, the delete will fail
     * @param bool $permanentDelete
     * @return int - number of affected rows
     */
    public function deleteByPrimaryKey(array $primaryKeyColumns, bool $permanentDelete = false): int
    {
        $table = $this->modelMap->getTable();
        $pkList = $this->modelMap->getPrimaryKeyColumns();

        // Build query with all PK conditions
        $query = DB::table($table);

        foreach ($pkList as $key) {
            if (!array_key_exists($key, $primaryKeyColumns)) {
                throw new CodeException(
                    "Missing primary key part: {$key}",
                    'lf2119',
                    ['given' => $primaryKeyColumns]
                );
            }
            $query->where($key, $primaryKeyColumns[$key]);
        }

        try {
            // Soft delete
            if ($this->modelMap->hasSoftDeletes() && !$permanentDelete) {

                $updatedColumns = [
                    'deleted_at' => Carbon::now()->toDateTimeString()
                ];

                return $query
                    ->whereNull('deleted_at')
                    ->limit(1)
                    ->update($updatedColumns);
            }

            // Hard delete
            return $query->limit(1)->delete();
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param array $conditions
     *  Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value',] ]
     *  Short format for a single cirterium ['column', 'optional operator', 'value', ]
     *  Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     *  'date=', 'date>', 'date>=', 'date<=', 'date<', 'in'
     * @param int $limit
     * @param bool $permanentDelete
     *
     * @return int - number of affected rows
     */
    public function deleteByConditions(array $conditions, int $limit = PHP_INT_MAX, bool $permanentDelete = false): int
    {
        $table = $this->modelMap->getTable();
        $normalized = $this->normalizeConditions($conditions);

        try {
            // Build base query without SELECT clause
            $query = ($permanentDelete)
                ? DB::table($table)
                : $this->table([null]); // no SELECT

            // Apply all conditions
            foreach ($normalized as [$column, $operator, $value]) {
                $this->prepareQueryWhere($query, $column, $value, $operator);
            }

            // SOFT DELETE
            if ($this->modelMap->hasSoftDeletes() && !$permanentDelete) {

                $updatedColumns = [
                    'deleted_at' => Carbon::now()->toDateTimeString()
                ];

                return $query
                    ->whereNull('deleted_at')
                    ->limit($limit)
                    ->update($updatedColumns);
            }

            // HARD DELETE
            return $query->limit($limit)->delete();
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    public function beginTransaction()
    {
        DB::beginTransaction();
    }


    public function commitTransaction()
    {
        DB::commit();
    }


    public function rollbackTransaction()
    {
        DB::rollBack();
    }

    /**
     * @return array
     */
    protected function getCommonDBExceptions()
    {
        return [
            1049 => function() {
                return [
                    [' ', 'Unknown database.', 'lf2104', ['identifier' => 'NULL']]
                ];
            },
        ];
    }
}
