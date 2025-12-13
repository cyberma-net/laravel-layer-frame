<?php

namespace Cyberma\LayerFrame\ModelMaps;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Illuminate\Support\Collection;


class ModelMap implements IModelMap
{
    const TABLE = '';

    const PRIMARY_KEY = ['id']; //parameter names, not column names; in most cases ['id']

    const PRIMARY_KEY_AUTO_INCREMENT = true;  // in majority cases, ID is autoincrement

    const ATTRIBUTES_MAP = [
        //'attributeName' => 'column_name
    ];

    const HAS_TIMESTAMPS = true;

    const HAS_SOFT_DELETES = false;

    const JSON_COLUMNS = [];   //list of columns that will be transformed to json

    const JSON_COLUMNS_FORCE_OBJECT = [];    //force json_encode to use {}, instead of simple array [].

    const HIDDEN_COLUMNS = [];    //will not be retrieved from db. Typically deleted_at

    const SEARCHABLE_ATTRIBUTES = [];   //simple list of attributes that can be searched using dbMapper->search

    const MANDATORY_ATTRIBUTES = ['id'];    //these attributes will be always retrieved from the DB, even if they are not selected in the $attributes

    const COLUMN_ALIAS_MAP = [    // [ 'id' => 'users.id as id' ]   -use this for connected tables, where names might colide; column name as the array key!
        // 'id' => 'users.id as id'
    ];

    /**
     * @param IModel $model
     * @param array $whichAttributes
     * @param array $except
     * @return array
     */
    public function exportDirtyAttributes(IModel $model, array $whichAttributes = [], array $except = []): array
    {
        return $model->getDirty($whichAttributes, $except);
    }

    /**
     * doCustomMapping is called from DBMapper and can be overriden in the child if custom mapping is needed. Columns already contain data after
     * the standard mapping process
     *
     * @param array $columns
     * @param IModel $model
     * @param array $attributes
     * @return array
     */
    public function doCustomMapping(array $columns, IModel $model, array $attributes = []): array
    {
        return $columns;
    }

    /**
     * doCustomDemapping is called from DBMapper and can be overriden in the child if custom demapping is needed. Columns already contain data after
     * the standard demapping process
     *
     * @param array $attributes  key => modelAttributes
     * @param \stdClass $row
     * @return array - return collection even for a single model attribute set
     */
    public function doCustomDemapping(array $attributes, \stdClass $row) : array
    {
        return $attributes;
    }

    /**
     * Can demap any object in a custom way. This method is called from DBMapper during demap, if the attribute value is an object
     *
     * @param string $attr
     * @param $value
     * @return array
     */
    public function demapAttribute(string $attr, $value): array
    {
        return $value instanceof IModel ? $value->toArray() : $value;
    }

    /**
     * @return bool
     */
    public function hasMandatoryAttributes() : bool
    {
        return !empty(static::MANDATORY_ATTRIBUTES);
    }

    /**
     * @return string[]
     */
    public function getMandatoryAttributes() : array
    {
        $mandatory = static::MANDATORY_ATTRIBUTES;
        foreach(static::PRIMARY_KEY as $key) {
            if(!in_array($key, $mandatory)) {
                $mandatory[] = $key;
            }
        }

        return $mandatory;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function isAttributeSearchable(string $attribute) : bool
    {
        return in_array($attribute, static::SEARCHABLE_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getColumnAliasMap() : array
    {
        return static::COLUMN_ALIAS_MAP;
    }

    /**
     * @return array
     */
    public function getJsons () : array
    {
        return static::JSON_COLUMNS;
    }

    /**
     * @return array
     */
    public function getJsonsForceObject () : array
    {
        return static::JSON_COLUMNS_FORCE_OBJECT;
    }

    /**
     * @param array $attributes - attribute names only
     * @return array
     */
    public function getAttributeMap(array $attributes = []) : array
    {
        // Case 1: no specific attributes → return all visible (non-hidden)
        if ($attributes === []) {
            return array_filter(
                static::ATTRIBUTES_MAP,
                fn($column) => !in_array($column, static::HIDDEN_COLUMNS, true)
            );
        }

        if (in_array('*', $attributes)) {
            return array_filter(
                static::ATTRIBUTES_MAP,
                fn($value, $key) => !in_array($value, static::HIDDEN_COLUMNS) || in_array($key, $attributes),
                ARRAY_FILTER_USE_BOTH
            );
        }

        return array_filter(static::ATTRIBUTES_MAP, fn($key) => in_array($key, $attributes), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return array
     */
    public function getFullAttributeMap() : array
    {
        return static::ATTRIBUTES_MAP;
    }

    /**
     * @param array $includeHiddenAttributes
     * @return array
     */
    public function getAttributeNames(array $includeHiddenAttributes = []) : array
    {
        $allAttributes = array_keys(static::ATTRIBUTES_MAP);
        if($includeHiddenAttributes === []) {
            return $allAttributes;
        }
        elseif(in_array('*', $includeHiddenAttributes)) {
            $allAttributes[] = '*';

            return $allAttributes;
        }

        return array_filter($allAttributes, fn($item) => !in_array($this->getColumnForAttribute($item), static::HIDDEN_COLUMNS) || in_array($item, $includeHiddenAttributes));
    }

    /**
     * @return array
     */
    public function getAllColumns(): array
    {
        return array_values(
            array_filter(
                static::ATTRIBUTES_MAP,
                fn($column) => !in_array($column, static::HIDDEN_COLUMNS, true)
            )
        );
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): array
    {
        return static::PRIMARY_KEY;
    }

    /**
     * @return bool
     */
    public function isPrimaryAutoIncrement(): bool
    {
        if(count(static::PRIMARY_KEY) > 1) {
            return false;
        }

        return static::PRIMARY_KEY_AUTO_INCREMENT;
    }

    /**
     * @return array
     */
    public function getPrimaryKeyColumns(): array
    {
        $primaryKeysColumns = [];
        foreach(static::PRIMARY_KEY as $key) {
            if (!array_key_exists($key, static::ATTRIBUTES_MAP)) {
                throw new CodeException("Primary key '$key' not found in ATTRIBUTES_MAP", 'lf2120');
            }

            $primaryKeysColumns[] = array_key_exists($key, static::ATTRIBUTES_MAP)
                ? static::ATTRIBUTES_MAP[$key]
                : null;
        }

        return $primaryKeysColumns;
    }

    /**
     * @return string
     */
    public function getTable() : string
    {
        return static::TABLE;
    }

    /**
     * @return array
     */
    public function getHiddenColumns () : array
    {
        return static::HIDDEN_COLUMNS;
    }

    /**
     * @param string $column
     * @return false|int|string
     */
    public function getAttributeForColumn(string $column)
    {
        $result = array_search($column, static::ATTRIBUTES_MAP, true);

        return $result === false ? null : $result;
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    public function getColumnForAttribute(string $attribute)
    {
        return static::ATTRIBUTES_MAP[$attribute];
    }

    /**
     * @return bool
     */
    public function hasSoftDeletes(): bool
    {
       return static::HAS_SOFT_DELETES;
    }


    /**
     * @return bool
     */
    public function hasTimeStamps(): bool
    {
        return static::HAS_TIMESTAMPS;
    }

    /**
     * @param string $code
     * @param string $message
     * @return array|bool
     */
    public function getDBException(string $code, string $message): array|bool
    {
        $exceptions = $this->getDBExceptions();

        if (!isset($exceptions[$code]))
            return false;

        $exceptions = $exceptions[$code]();  //process only selected exceptions

        if (!is_array($exceptions[0]))  //there is only a single exception under one error code; it can be array of errors under one error code
            return $exceptions;

        foreach ($exceptions as $exception) {    //multiple exceptions under one error code, e.g. for multiple columns
            if (strpos($message, $exception[0]) !== false)
                return $exception;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getDBExceptions(): array
    {
        return [
            //example:
           /* 1062 =>
                function() {
                    return [
                        ['users_email_unique', _('A user with the entered email already exists.'), 'lf2101', ['email' => _('Email already taken.')]],
                        ['users_phone_unique', _('A user with the entered phone number already exists.'), 'lf2102', ['phone' => _('Phone number already in use.')]]
                    ];
                },
            */
        ];
    }
}
