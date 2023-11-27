<?php
/**

 *
 
 * Date: 21.02.2021
 */

namespace Cyberma\LayerFrame\Contracts\Repositories;

use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Illuminate\Support\Collection;


interface IRepository
{
    /**
     * @param int $id
     * @return IModel
     */
    public function getById(int $id, array $attributes = []): ?IModel;

    /**
     * @param string $attribute
     * @param string|int $value
     * @param array $attributes
     * @return IModel
     */
    public function getSingle(string $attribute, string|int $value, array $attributes = []): ?IModel;

    /**
     * @param IModel $model
     * @return IModel
     */
    public function store(IModel $model): IModel;

    /**
     * @param int $id
     * @param bool $permanentDelete
     * @return int -number of affected arrays
     */
    public function deleteById(int $id, bool $permanentDelete = false): int;

    /**
     * @param IModel $model
     * @param bool $permanentDelete
     * @return int -number of affected rows
     */
    public function delete(IModel $model, bool $permanentDelete = false): int;

    /**
     * @param IModel $model
     * @param array $selectedAttributes
     * @return int -number of affected rows
     * @throws CodeException
     * @throws Exception
     */
    public function patchById(IModel $model, array $selectedAttributes): int;

    /**
     * @param IModel $model
     * @param array $selectedAttributes
     * @param array $conditions
     * @return int
     * @throws CodeException
     * @throws Exception
     */
    public function patchByConditions(IModel $model, array $selectedAttributes, array $conditions): int;

    /**
     *
     * @param array $attributes
     * @param array $conditions
     * Format1: [  ['attribute', 'value', 'operator'], ['attribute', 'value', 'operator'] ]
     * Short format for a single cirterium ['attribute', 'value', 'optional operator']
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<'*
     * @param array|string[] $orderBy
     * @param array|int[] $pagination
     * @return Collection
     */
     public function get(array $attributes = [],
                        array $conditions = [],
                        array $pagination = ['page' => 1, 'perPage' => 20],
                        array $orderBy = ['attribute' => 'id', 'order' => 'desc']): Collection;

    /**
     * @param array $attributes
     * @param array $conditions
     * @return int
     */
    public function getCount(array $conditions = []): int;

    /**
     * @param array $attributes
     * @param array $conditions
     * @return IModel
     */
    public function getFirst(array $attributes = [],
                             array $conditions = []): ?IModel;


    /**
     * @param string $keywords
     * @param array $searchedAttributes
     * @param array $attributes
     * @param array $pagination
     * @param array $orderBy
     * @return Collection
     */
    public function searchInAttributes(string $keywords, array $searchedAttributes, array $attributes = [],
                                       array $pagination = [], array $orderBy = []): Collection;


    public function beginTransaction();


    public function commitTransaction();


    public function rollbackTransaction();
}
