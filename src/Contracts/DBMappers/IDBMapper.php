<?php

namespace Cyberma\LayerFrame\Contracts\DBMappers;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Illuminate\Support\Collection;


interface IDBMapper
{
    /**
     * @param IModel $model
     * @param array $attributes
     * @param array $except
     * @return array
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function map(IModel $model, array $attributes = [], array $except = []): array;

    /**
     * @param Collection|array $rows
     * @param string|null $collectionKeyAttribute
     * @return Collection
     */
    public function demap (Collection|array $rows, ?string $collectionKeyAttribute = 'id') : Collection;

    /**
     * @param array $attributes
     * @return array
     */
    public function getColumnNames(array $attributes = []): array;

    /**
     * @param array $attributes
     * @param array $include
     * @param bool $applyAliases
     * @return array
     */
    public function mapAttributesNamesToColumns(array $attributes = [], array $include = [], bool $applyAliases = true): array;

    /**
     * @param array $columns
     * @param array $specificJsons
     * @return array
     */
    public function encodeJsons (array $columns, array $specificJsons = []) : array;

    /**
     * @param \stdClass $columns
     * @return \stdClass
     */
    public function decodeJsons (\stdClass &$columns) : \stdClass;

    /**
     * @param \stdClass $row
     * @return array|null
     */
    public function demapSingle(?\stdClass $row): ?array;

    /**
     * @param array $attributesWithValues
     * @return array
     */
    public function mapAttributesToColumns(array $attributesWithValues = []): array;
    /**
     * @param array $conditions
     * @return array
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function mapConditionsColumnNames(array $conditions): array;

    /**
     * @param \stdClass $row
     * @param array $reverseAliases
     * @return array
     */
    public function mapColumnsToAttributes (\stdClass $row, array $reverseAliases = []) : array;

    /**
     * @param array $orderBy
     * @return array
     */
    public function mapOrderBy(array $orderBy = []): array;

    /**
     * @param string $attributeName
     * @return string
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function mapAttributeNameToColumn(string $attributeName): string;
}
