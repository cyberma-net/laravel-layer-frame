<?php
/**
 *
 * Date: 21.02.2021
 */

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

    /**
     * @var IModelMap
     */
    protected IModelMap $modelMap;


    public function __construct(IModelMap $modelMap)
    {
        $this->modelMap = $modelMap;
    }

    /**
     * @param array $columnsNames
     * @return Builder
     */
    public function table(array $columnsNames = []): Builder
    {
        if($columnsNames === []) {
            $columnsNames = $this->modelMap->getAllColumns();
        }

        $query = DB::table($this->modelMap->getTable())->select($columnsNames);

        if ($this->modelMap->hasSoftDeletes()) {
            $query->whereNull($this->modelMap->getTable() . '.deleted_at');
        }

        return $query;
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
     * @param array $columnNames
     * @return Builder
     */
    public function queryByConditions(array $conditions, array $columnNames = []) : Builder
    {
        $query = $this->table($columnNames);

        if(count($conditions) > 0 && is_string(array_keys($conditions)[0])) {
            $incomingConditions = $conditions;
            $conditions = [];
            $conditions[] = $incomingConditions;
        }

        foreach($conditions as $criterium) {
            $operator = count($criterium) === 3 ? $criterium[1] : '=';
            $value = count($criterium) === 3 ? $criterium[2] : $criterium[1] ;
            $query = $this->prepareQueryWhere($query, $criterium[0], $value, $operator);
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
    public function prepareQueryWhere (Builder &$query, string $column, $value, string $operator = '=') : Builder
    {
        $namespacedColumn = $this->modelMap->getTable() . '.' . $column;

        switch (strtolower($operator)) {

            case '%like%' :
                $query->where($namespacedColumn, 'like',  '%' . $value . '%'); break;
            case 'like' :
                $query->where($namespacedColumn, 'like', $value); break;
            case 'like%' :
                $query->where($namespacedColumn, 'like',  $value . '%'); break;
            case '%like' :
                $query->where($namespacedColumn, 'like',  '%' . $value); break;
            case 'in' :
                $query->whereIn($namespacedColumn, $value); break;
            case 'notIn' :
                $query->whereNotIn($namespacedColumn, $value); break;
            case 'between' :
                $query->whereBetween($namespacedColumn, $value); break;
            case 'null' :
                $query->whereNull($namespacedColumn); break;
            case 'not null' :
                $query->whereNotNull($namespacedColumn); break;

            case 'date=' :
            case 'date>' :
            case 'date>=' :
            case 'date<' :
            case 'date<=' :

                $operator = str_replace('date', '', $operator);
                $query->where($namespacedColumn, $operator, $value);
                break;
            default :
                $query->where($namespacedColumn, $operator, $value);
        }

        return $query;
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

        // add new DB entry or update existing one, if the primary key (ID) exists
        if(!$this->modelMap->isPrimaryAutoIncerement()) {
            return $this->storeByCompositePrimaryKey($columns);
        }

        $primaryKeyColumn = $this->modelMap->getPrimaryKeyColumns()[0];
        if(!array_key_exists($primaryKeyColumn, $columns)) {
            $columns[$primaryKeyColumn] = $this->insert($columns, $this->modelMap->getTable());
        }
        else {
            $this->updateByPrimaryKey($columns, $primaryKeyColumn);
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
        $query = $this->table($primaryKeyColumns);

        foreach($primaryKeyColumns as $key) {
            $query->where($key, $columns[$key]);
        }

        $dbItem = $query->first();
        try {
            if(empty($dbItem)) { // add new item
                $this->insert($columns, $this->modelMap->getTable());

                return $columns;
            }
            else {
                 $query->limit(1)->update($columns);

                 return $columns;
            }
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
        if(count($this->modelMap->getPrimaryKeyColumns()) > 1) {
            throw new CodeException('Method storeMultiple not implemented for composite primary keys.', 'lf2105');
        }

        foreach($columnsSet as $index => $columns) {
            if($this->modelMap->hasTimeStamps()) {
                $columns = $this->addTimeStamps($columns);
            }

            $primaryKeyColumn = $this->modelMap->getPrimaryKeyColumns()[0];
            if(!array_key_exists($primaryKeyColumn, $columns)) {
                $columnsSet[$index][$primaryKeyColumn] = $this->insert($columns, $this->modelMap->getTable());
            }
            else {
                $this->updateByPrimaryKey($columns, $primaryKeyColumn);
            }
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
        if(count($columns) == 0 || !array_key_exists($primaryKeyColumn, $columns))
            return true;

        try {
            return DB::table($this->modelMap->getTable())->where($primaryKeyColumn, $columns[$primaryKeyColumn])->limit(1)->update($columns);
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
        $columns['updated_at'] = Carbon::now()->toDateTimeString();

        // add created_at for a new DB entry
        if(!array_key_exists($this->modelMap->getPrimaryKeyColumns()[0], $columns)) {
            $columns['created_at'] = $columns['updated_at'];
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
            return DB::table($table)->insertGetId($columns);
        }
        catch(QueryException $e) {
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
            return true;

        if($this->modelMap->hasTimeStamps()) {
            $columns['updated_at'] = Carbon::now()->toDateTimeString();
        }

        try {
            $query = DB::table($this->modelMap->getTable());

            foreach($conditions as $condition) {
                $operator = count($condition) === 3 ? $condition[2] : '=';
                $query = $this->prepareQueryWhere($query, $condition[0], $condition[1], $operator);
            }

            return $query->update($columns);   //returns number of affected rows
        }
        catch (QueryException $e) {
            $this->processSQLerrors($e);
        }
    }

    /**
     * @param array $selectedColumns
     * @return int
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

        $selectedColumns['updated_at'] = Carbon::now()->toDateTimeString();

        $updatingStatus = $this->updateByPrimaryKey($selectedColumns, $primaryKey);

        return $updatingStatus;  //number of affected rows
    }

    /**
     * @param array $selectedColumns
     * @param array $conditions
     * @return int
     * @throws CodeException
     * @throws Exception
     */
    public function patchByConditions(array $selectedColumns, array $conditions) : int
    {
        $selectedColumns['updated_at'] = Carbon::now()->toDateTimeString();

        $updatingStatus = $this->update($selectedColumns, $conditions);

        return $updatingStatus;
    }

    /**
     * @param int $id
     * @param bool $permanentDelete
     * @return int
     */
    public function deleteById (int $id, bool $permanentDelete = false) : int
    {
        $table = $this->modelMap->getTable();

        if ($this->modelMap->hasSoftDeletes() && !$permanentDelete) {

            $updatedColumns[$table. '.deleted_at'] = Carbon::now()->toDateTimeString();

            $affectedRows = DB::table($table)->where($table . '.id', $id)->whereNull('deleted_at')
                ->limit(1)->update(
                    $updatedColumns
                );
        }
        else {  //permament delete
            $affectedRows =  DB::table($this->modelMap->getTable())->where($table. '.id', $id)->limit(1)->delete();
        }

        return $affectedRows;
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
