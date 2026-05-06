<?php

namespace Cyberma\LayerFrame\Contracts\DBStorage;

use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;
use Cyberma\LayerFrame\DBStorage\Collections\CollectionQuery;
use Cyberma\LayerFrame\DBStorage\Columns\ColumnQuery;
use Cyberma\LayerFrame\DBStorage\Mutations\MutationQuery;
use Cyberma\LayerFrame\DBStorage\Streams\StreamQuery;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use stdClass;


interface IDBStorage
{
    /**
     * @param callable $customTableFunction
     * @return void
     */
    public function useTable(callable $customTableFunction): void;

    /**
     * @param array $columnsNames
     * @return Builder
     */
    public function table(array $columnsNames = []): Builder;

    /**
     * @param array $columns
     * @return array
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function store(array $columns): array;

    /**
     * @param Collection $columnsSet
     * @return Collection
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function storeMultiple(Collection $columnsSet) : Collection;


    /**
     * @param Builder $query
     * @param array $pagination
     * @return Builder
     */
    public function addPagination(Builder $query, array $pagination): Builder;

    /**
     * @param Builder $query
     * @param array $orderBy
     * @return Builder
     */
    public function addOrderBy(Builder $query, array $orderBy): Builder;

    /**
     * @param array $keywords
     * @param array $searchedColumns
     * @param array $columnsNames
     * @param array $pagination
     * @param array $orderBy
     * @return Collection
     */
    public function searchInColumns(array $keywords, array $searchedColumns, array $columnsNames = [],
                                       array $pagination = [], array $orderBy = []): Collection;

    /**
     * @param array $conditions
     * @param string[] $columnNames
     * @return Builder
     */
    public function queryByConditions(array $conditions, array $columnNames = []) : Builder;


    /**
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     * @param string $operator
     * @return Builder
     */
    public function prepareQueryWhere (Builder &$query, string $column, $value, string $operator = '=') : Builder;

    /**
     * @param Builder $query
     * @param int $limit
     * @return Collection
     */
    public function get(Builder $query, int $limit = 100): Collection;

    /**
     * @param Builder $query
     * @return stdClass|null
     */
    public function first(Builder $query): ?StdClass;

    /**
     * @param int $id
     * @param array $columnsNames
     * @return stdClass|null
     */
    public function getById(int $id, array $columnsNames = []): ?StdClass;

    /**
     * @param array $primaryKeyColumns
     * @param array $columnsNames
     * @return stdClass|null
     */
    public function getByPrimaryKey(array $primaryKeyColumns, array $columnsNames = []): ?StdClass;

    /**
     * @param string $column
     * @param string|int $value
     * @param array $columnsNames
     * @return stdClass|null
     */
    public function getSingle(string $column, string|int $value, array $columnsNames = []): ?StdClass;

    /**
     * @param array $selectedColumns
     * @return int
     * @throws CodeException
     * @throws Exception
     */
    public function patchById(array $selectedColumns) : int;

    /**
     * @param array $selectedColumns
     * @param array $conditions
     * @return int
     * @throws CodeException
     * @throws Exception
     */
    public function patchByConditions(array $selectedColumns, array $conditions) : int;

    /**
     * @param array $columns
     * @param array $conditions
     * @return int
     * @throws Exception
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function update (array $columns, array $conditions) : int;

    /**
     * @param int $id
     * @param bool $permanentDelete
     * @return int
     */
    public function deleteById (int $id, bool $permanentDelete = false) : int;

    /**
     * @param array $primaryKeyColumns - array of primary keys, if not all provided, the delete will fail
     * @param bool $permanentDelete
     * @return int - number of affected rows
     */
    public function deleteByPrimaryKey(array $primaryKeyColumns, bool $permanentDelete = false) : int;

    /**
     * @param array $conditions
     *  Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value',] ]
     *  Short format for a single criterion ['column', 'optional operator', 'value', ]
     *  Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     *  'date=', 'date>', 'date>=', 'date<=', 'date<', 'in'
     * @param int $limit
     * @param bool $permanentDelete
     *
     * @return int - number of affected rows
     */
    public function deleteByConditions(array $conditions, int $limit = PHP_INT_MAX, bool $permanentDelete = false) : int;

    /**
     * @param array $columnsNames
     * @param array $conditions
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     * @param array|string[] $orderBy
     * @param array|int[] $pagination
     * @return Collection
     */
    public function getByConditions(array $columnsNames = [],
                array $conditions = [/*'column' => 'value', 'column' => 'value',*/],
                array $pagination = [/*'page' => 1, 'perPage' => 20*/],
                array $orderBy = [/*'column' => 'id', 'order' => 'desc'*/]): Collection;

    /**
     * @param array $columnsNames
     * @param array $conditions
     *  Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value',] ]
     *  Short format for a single criterion ['column', 'optional operator', 'value', ]
     *  Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     *  'date=', 'date>', 'date>=', 'date<=', 'date<', 'in'
     * @return int
     */
    public function countByConditions(array $conditions = []): int;

    /**
     * @param array $conditions
     * @return bool
     */
    public function existsByConditions(array $conditions = []): bool;

    /**
     * Execute scalar/aggregate operation against filtered conditions.
     *
     * @param array $conditions
     * @param string|\\Cyberma\\LayerFrame\\DBStorage\\Aggregates\\AggregateType $operation
     * @param string|null $column
     * @param array $options
     * @return int|float|string|bool|null
     */
    public function executeScalarByConditions(
        array $conditions,
        string|\Cyberma\LayerFrame\DBStorage\Aggregates\AggregateType $operation,
        ?string $column = null,
        array $options = []
    ): int|float|string|bool|null;

    /**
     * Execute scalar/aggregate operation against filtered query.
     *
     * @param ScalarQuery $query
     * @param array $conditions
     * @return int|float|string|bool|null
     */
    public function scalar(ScalarQuery $query, array $conditions = []): int|float|string|bool|null;

    /**
     * Execute collection query (pluck-style retrieval).
     *
     * @param CollectionQuery $query
     * @param array $conditions
     * @return array
     */
    public function collection(CollectionQuery $query, array $conditions = []): array;

    /**
     * Backward-compatible alias for collection pluck operations.
     *
     * @param ColumnQuery $query
     * @param array $conditions
     * @return array
     */
    public function columnCollection(ColumnQuery $query, array $conditions = []): array;

    /**
     * Execute generic mutation query.
     *
     * @param MutationQuery $query
     * @param array $conditions
     * @return int
     */
    public function mutate(MutationQuery $query, array $conditions = []): int;

    /**
     * Execute stream query with low memory footprint.
     *
     * @param StreamQuery $query
     * @param array $conditions
     * @return \Generator
     */
    public function stream(StreamQuery $query, array $conditions = []): \Generator;

    /**
     * @param array $condition
     * @return array
     */
    public function normalizeSingleCondition(array $condition): array;

    /**
     * @param array $conditions
     * @return array|array[]
     */
    public function normalizeConditions(array $conditions): array;


    /**
     * Begin a database transaction
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction
     * @return void
     */
    public function commitTransaction();

    /**
     * Rollback the active database transaction
     * @return void
     */
    public function rollbackTransaction();

}
