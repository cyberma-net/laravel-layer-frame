<?php

namespace Cyberma\LayerFrame\Contracts\ModelMaps;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Illuminate\Support\Collection;

interface IModelMap
{
    /**
     * @param IModel $model
     * @param array $whichAttributes
     * @param array $except
     * @return array
     */
    public function exportDirtyAttributes(IModel $model, array $whichAttributes = [], array $except = []): array;

    /**
     * @param Collection $models
     * @param \stdClass $row
     * @param string|null $collectionKeyParameter
     * @return IModel
     */
    public function doCustomDemapping(IModel $model, \stdClass $row, ?string $collectionKeyParameter = null) : IModel;

    /**
     * @param string $code
     * @param string $message
     * @return array|bool
     */
    public function getDBException(string $code, string $message): array|bool;

    /**
     * Can demap any object in a custom way. This method is called from DBMapper during demap, if the attribute value is an object
     *
     * @param $attr
     * @param $value
     * @return array
     */
    public function demapAttribute(string $attr, $value): array;

    /**
     * @return bool
     */
    public function hasMandatoryAttributes(): bool;

    /**
     * @param string $attribute
     * @return bool
     */
    public function isAttributeSearchable(string $attribute): bool;

    /**
     * @return array
     */
    public function getColumnAliasMap(): array;

    /**
     * @return array
     */
    public function getJsons(): array;

    /**
     * @return array
     */
    public function getJsonsForceObject(): array;

    /**
     * @return array
     */
    public function getAttributeMap(array $attributes = []): array;

    /**
     * @return array
     */
    public function getFullAttributeMap() : array;

    /**
     * @return array
     */
    public function getAttributeNames(array $includeHiddenAttributes = []): array;

    /**
     * @return array
     */
    public function getAllColumns(): array;

    /**
     * @return array
     */
    public function getPrimaryKey(): array;

    /**
     * @return bool
     */
    public function isPrimaryAutoIncrement(): bool;

    /**
     * @return array
     */
    public function getPrimaryKeyColumns(): array;

    /**
     * @return string
     */
    public function getTable(): string;

    /**
     * @return array
     */
    public function getHiddenColumns(): array;

    /**
     * @param string $column
     * @return false|int|string
     */
    public function getAttributeForColumn(string $column);

    /**
     * @param string $attribute
     * @return mixed
     */
    public function getColumnForAttribute(string $attribute);
   
    /**
     * @return array
     */
    public function getMandatoryAttributes() : array;

    /**
     * @return bool
     */
    public function hasSoftDeletes(): bool;

    /**
     * @return bool
     */
    public function hasTimeStamps(): bool;

    /**
     * @return array
     */
    public function getDBExceptions(): array;
}
