<?php

namespace Cyberma\LayerFrame\Contracts\Repositories;

use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Illuminate\Support\Collection;


interface IRepository
{
    /**
     * @param int $id
     * @param array $attributes
     * @return array|null
     */
    public function getByIdRaw(int $id, array $attributes = []): ?array;

    /**
     * @param int $id
     * @param array $attributes
     * @return IModel|null
     */
    public function getById(int $id, array $attributes = []): ?IModel;

    /**
     * @param string $attribute
     * @param string|int $value
     * @param array $attributes
     * @return array|null
     */
    public function getSingleRaw(string $attribute, string|int $value, array $attributes = []): ?array;

    /**
     * @param string $attribute
     * @param string|int $value
     * @param array $attributes
     * @return IModel|null
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
     * @return int -number of affected rows
     */
    public function deleteById(int $id, bool $permanentDelete = false): int;

    /**
     * @param array $primaryKeyAttributes
     * @param bool $permanentDelete
     * @return int -number of affected rows
     */
    public function deleteByPrimaryKey(array $primaryKeyAttributes, bool $permanentDelete = false): int;

    /**
     * @param array $conditions
     *  Format1: [  ['attribute', 'operator', 'value'], ['attribute', 'operator', 'value',] ]
     *  Short format for a single criterion ['attribute', 'optional operator', 'value', ]
     *  Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null', 'in', 'between'
     *  'date=', 'date>', 'date>=', 'date<=', 'date<', 'in'
     * @param int $limit
     * @param bool $permanentDelete
     *
     * @return int - number of affected rows
     */
    public function deleteByConditions(array $conditions, int $limit = 100, bool $permanentDelete = false) : int;

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
     * @param array $conditions
     * @param array $attributes
     * Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value'] ]
     * Short format for a single criterion ['column', 'optional operator', 'value']
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<'*, 'between', 'in'
     * @param array|int[] $pagination
     * @param array|string[] $orderBy
     * @param string $collectionKeyParameter - is used, it will make this attribute a key for the collection. E.g. collection of users with key being the ID
     * @return Collection
     */
    public function getRaw(array $conditions = [],
                        array $attributes = [],
                        array $pagination = [/*'page' => 1, 'perPage' => 20*/],
                        array $orderBy = [/*'attribute' => 'id', 'order' => 'desc'*/],
                        ?string $collectionKeyParameter = null): Collection;

    /**
     *
     * @param array $conditions
     * @param array $attributes
     * Format1: [  ['column', 'operator', 'value'], ['column', 'operator', 'value'] ]
     * Short format for a single criterion ['column', 'optional operator', 'value']
     * Available operators '=' - default - no need to use, '<=', '>=', 'like', 'like%', '%like%', '%like', 'null', 'not null'
     * 'date=', 'date>', 'date>=', 'date<=', 'date<'*, 'between', 'in'
     * @param array|int[] $pagination
     * @param array|string[] $orderBy
     * @param string $collectionKeyParameter - is used, it will make this attribute a key for the collection. E.g. collection of users with key being the ID
     * @return Collection
     */
     public function get(array $conditions = [],
                        array $attributes = [],
                        array $pagination = [/*'page' => 1, 'perPage' => 20*/],
                        array $orderBy = [/*'attribute' => 'id', 'order' => 'desc'*/],
                        ?string $collectionKeyParameter = null): Collection;

    /**
     * @param array $conditions
     * @return int
     */
    public function getCount(array $conditions = []): int;

    /**
     * @param array $conditions
     * @param array $attributes
     * @return array|null
     */
    public function getFirstRaw(array $conditions = [], array $attributes = []): ?array;

    /**
     * @param array $conditions
     * @param array $attributes
     * @return IModel|null
     */
    public function getFirst(array $conditions = [], array $attributes = []): ?IModel;


    /**
     * @param string $keywords
     * @param array $searchedAttributes
     * @param array $attributes
     * @param array $pagination
     * @param array $orderBy
     * @param string|null $collectionKeyParameter
     * @return Collection
     */
    public function searchInAttributesRaw(string $keywords, array $searchedAttributes, array $attributes = [],
                                       array  $pagination = [], array $orderBy = [], ?string $collectionKeyParameter = null): Collection;

    /**
     * @param string $keywords
     * @param array $searchedAttributes
     * @param array $attributes
     * @param array $pagination
     * @param array $orderBy
     * @param string|null $collectionKeyParameter
     * @return Collection
     */
    public function searchInAttributes(string $keywords, array $searchedAttributes, array $attributes = [],
                                       array  $pagination = [], array $orderBy = [], ?string $collectionKeyParameter = null): Collection;


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

    /**
     * Set context data for model creation
     * @param array $contextData
     * @return void
     */
    public function setContextData(array $contextData): void;

    /**
     * Set context data and return the repository instance for method chaining
     * @param array $contextData
     * @return static
     */
    public function withContext(array $contextData): static;

    /**
     * Set a callable resolver for context data based on model attributes
     * @param callable $resolver
     * @return void
     */
    public function setContextResolver(callable $resolver): void;

    /**
     * Set a callable resolver for context data and return the repository instance for method chaining
     * @param callable $resolver
     * @return static
     */
    public function withContextResolver(callable $resolver): static;
}
