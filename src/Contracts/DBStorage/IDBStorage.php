<?php
/**

 *
 
 * Date: 21.02.2021
 */

namespace Cyberma\LayerFrame\Contracts\DBStorage;

use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use stdClass;


interface IDBStorage
{
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
     * @param array $orderBy
     * @param array $pagination
     * @return Collection
     */
    public function searchInColumns(array $keywords, array $searchedColumns, $columnsNames = [],
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
     * @return stdClass
     */
    public function first(Builder $query): ?StdClass;

    /**
     * @param int $id
     * @param array $columnsNames
     * @return stdClass
     */
    public function getById(int $id, array $columnsNames = []): ?StdClass;

    /**
     * @param array $primaryKeyColumns
     * @param array $columnsNames
     * @return stdClass
     */
    public function getByPrimaryKey(array $primaryKeyColumns, array $columnsNames = []): ?StdClass;

    /**
     * @param string $column
     * @param string|int $value
     * @param array $columnsNames
     * @return stdClass
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
     * @param array $columnsNames
     * @param array $conditions
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     * @param array|string[] $orderBy
     * @param array|int[] $pagination
     * @return Collection
     */
    public function getByConditions(array $columnsNames = [],
                                  array $conditions = [],
                                  array $pagination = ['page' => 1, 'perPage' => 20],
                                  array $orderBy = ['attribute' => 'id', 'order' => 'asc']): Collection;

    /**
     * @param array $columnsNames
     * @param array $conditions
     * @return int
     */
    public function countByConditions(array $conditions = []): int;


    public function beginTransaction();


    public function commitTransaction();


    public function rollbackTransaction();
}
