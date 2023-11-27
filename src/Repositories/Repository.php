<?php
/**

 *
 
 * Date: 21.02.2022
 */

namespace Cyberma\LayerFrame\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Contracts\Repositories\IRepository;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Illuminate\Support\Collection;


class Repository implements IRepository
{
    /**
     * @var IDBStorage
     */
    protected IDBStorage $dbStorage;
    /**
     * @var IDBMapper
     */
    protected IDBMapper $dbMapper;
    /**
     * @var IModelMap
     */
    protected IModelMap $modelMap;


    public function __construct(IDBStorage $dbStorage, IDBMapper $dbMapper, IModelMap $modelMap)
    {
        $this->dbStorage = $dbStorage;
        $this->dbMapper = $dbMapper;
        $this->modelMap = $modelMap;
    }

    /**
     * @param int $id
     * @return IModel
     */
    public function getById(int $id, array $attributes = []): ?IModel
    {
        $columnNames = $this->dbMapper->mapAttributesNamesToColumns($attributes);
        $dbRow = $this->dbStorage->getById($id, $columnNames);

        return $this->dbMapper->demapSingle($dbRow);
    }

    /**
     *
     * @param array $attributes
     * @param array $conditions
     * Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value'] ]
     * Short format for a single cirterium ['column', 'optional operator', 'value']
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<'*, 'between', 'in'
     * @param array|int[] $pagination
     * @param array|string[] $orderBy
     * @return Collection
     */
    public function get(array $attributes = [],
                        array $conditions = [],
                        array $pagination = [/*'page' => 1, 'perPage' => 20*/],
                        array $orderBy = [/*'attribute' => 'id', 'order' => 'desc'*/]): Collection
    {
        $columnNames = $this->dbMapper->mapAttributesNamesToColumns($attributes);
        $conditions = $this->dbMapper->mapConditionsColumnNames($conditions);
        $mappedOrderBy = $this->dbMapper->mapOrderBy($orderBy);

        $dbRows = $this->dbStorage->getByConditions($columnNames, $conditions, $pagination, $mappedOrderBy);

        return $this->dbMapper->demap($dbRows);
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function getCount(array $conditions = []): int
    {
        $conditions = $this->dbMapper->mapConditionsColumnNames($conditions);

        return $this->dbStorage->countByConditions($conditions);
    }

    /**
     * @param array $attributes
     * @param array $conditions
     * @return IModel|null
     */
    public function getFirst(array $attributes = [], array $conditions = []): ?IModel
    {
        $columnNames = $this->dbMapper->mapAttributesNamesToColumns($attributes);
        $conditions = $this->dbMapper->mapConditionsColumnNames($conditions);

        $dbRows = $this->dbStorage->getByConditions($columnNames, $conditions, ['perPage' => 1]);

        if(empty($dbRows) || $dbRows->isEmpty()) {
            return null;
        }

        return $this->dbMapper->demapSingle($dbRows[0]);
    }

    /**
     * @param string $keywords
     * @param array $searchedAttributes
     * @param array $attributes
     * @param array $pagination
     * @param array $orderBy
     * @return Collection
     */
    public function searchInAttributes(string $keywords, array $searchedAttributes, array $attributes = [],
                                       array  $pagination = [], array $orderBy = []): Collection
    {
        $columnNames = $this->dbMapper->mapAttributesNamesToColumns($attributes);
        $searchedColumns = $this->dbMapper->mapAttributesNamesToColumns($searchedAttributes, [], false);
        $keywordsAsArray = explode(' ', $keywords);

        $dbRows = $this->dbStorage->searchInColumns($keywordsAsArray, $searchedColumns, $columnNames, $pagination, $orderBy);

        return $this->dbMapper->demap($dbRows);
    }

    /**
     * @param string $attribute
     * @param string|int $value
     * @param array $attributes
     * @return IModel
     */
    public function getSingle(string $attribute, string|int $value, array $attributes = []): ?IModel
    {
        $columnNames = $this->dbMapper->mapAttributesNamesToColumns($attributes);
        $whereColumn = $this->dbMapper->mapAttributeNameToColumn($attribute);

        $dbRow = $this->dbStorage->getSingle($whereColumn, $value, $columnNames);

        return $this->dbMapper->demapSingle($dbRow);
    }

    /**
     * @param IModel $model
     * @return IModel
     */
    public function store(IModel $model): IModel
    {
        $columns = $this->dbMapper->map($model);

        $columns = $this->dbStorage->store($columns);

        // set primary key, usually ID
        foreach($this->modelMap->getPrimaryKey() as $key) {
            $model->{$key} = $columns[$this->dbMapper->mapAttributeNameToColumn($key)];
        }
        return $model;
    }

    /**
     * @param int $id
     * @param bool $permanentDelete
     * @return int -number of affected rows
     */
    public function deleteById(int $id, bool $permanentDelete = false): int
    {
        return $this->dbStorage->deleteById($id, $permanentDelete);
    }

    /**
     * @param IModel $model
     * @param bool $permanentDelete
     * @return int -number of affected rows
     */
    public function delete(IModel $model, bool $permanentDelete = false): int
    {
        return $this->deleteById($model->id, $permanentDelete);
    }

    /**
     * @param IModel $model
     * @param array $selectedAttributes
     * @return int -number of affected rows
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     * @throws \Cyberma\LayerFrame\Exceptions\Exception
     */
    public function patchById(IModel $model, array $selectedAttributes): int
    {
        $primaryKey = $this->modelMap->getPrimaryKey();

        foreach($primaryKey as $key) {
            if (!in_array($key, $selectedAttributes)) {
                $selectedAttributes[] = $key;
            }

            if (empty($model->$key)) {
                throw new CodeException(_('The model you would like to patch is missing the primary key, or the key is empty.'), 'lf2107',                    [
                        'model' => get_class($model),
                        'selectedAttributes' => $selectedAttributes
                    ]);
            }
        }

        $columns = $this->dbMapper->map($model, $selectedAttributes);

        return $this->dbStorage->patchById($columns);
    }

    /**
     * @param IModel $model
     * @param array $selectedAttributes
     * @param array $conditions  [ ['attribute1', 'value1', 'operator' ], ['attribute2', 'value2', 'oeprator' ]   ] - operator is optional, = is default
     * @return int
     * @throws CodeException
     * @throws \Cyberma\LayerFrame\Exceptions\Exception
     */
    public function patchByConditions(IModel $model, array $selectedAttributes, array $conditions): int
    {
        $columns = $this->dbMapper->map($model, $selectedAttributes);
        $conditions = $this->dbMapper->mapConditionsColumnNames($conditions);

        return $this->dbStorage->update($columns, $conditions);
    }


    public function beginTransaction()
    {
        $this->dbStorage->beginTransaction();
    }


    public function commitTransaction()
    {
        $this->dbStorage->commitTransaction();
    }


    public function rollbackTransaction()
    {
        $this->dbStorage->rollbackTransaction();
    }
}
