<?php

namespace Cyberma\LayerFrame\Contracts\DBMappers;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Illuminate\Support\Collection;


interface IDBMapper
{
    /**
     * @param IModel $model
     * @param array $attributes
     * @return array
     */
    public function map(IModel $model, array $attributes = []): array;

    /**
     * @param Collection|array $rows
     * @param string|null $collectionKeyParameter
     * @return Collection
     */
    public function demap (Collection $rows, string $collectionKeyParameter = null) : Collection;

    /**
     * @param array $attributes
     * @return array
     */
    public function getColumnNames(array $attributes = []): array;

    /**
     * @param array $attributes
     * @param array $include
     * @return array
     */
    public function mapAttributesNamesToColumns(array $attributes = [], array $include = []): array;

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
     * @return IModel|null
     */
    public function demapSingle (\stdClass $row) : ?IModel;

    /**
     * @param array $attributes
     * @return array
     */
    public function mapAttributesToColumns(array $attributes = []): array;
    /**
     * @param array $conditions
     * @return array
     */
    public function mapConditionsColumnNames(array $conditions): array;

    /**
     * @param \stdClass $row
     * @param array $reverseAliases
     * @return array
     */
    public function mapColumnsToAttributes (\stdClass $row, array $reverseAliases = []) : array;

    /**
     * @param $orderBy
     * @return array
     */
    public function mapOrderBy($orderBy = []): array;

    /**
     * @param string $attributeName
     * @return string
     */
    public function mapAttributeNameToColumn(string $attributeName): string;

    /**
     * @param array $columns
     * @param array $attributes
     * @return array
     */
    public function removeHiddenColumns (array &$columns, array $attributes = []): array;
}
