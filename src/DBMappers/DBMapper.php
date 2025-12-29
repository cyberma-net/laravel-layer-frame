<?php

namespace Cyberma\LayerFrame\DBMappers;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Illuminate\Support\Collection;


class DBMapper implements IDBMapper
{

    private IModelMap $modelMap;

    /**
     * DBMapper constructor.
     * @param IModelMap $modelMap
     */
    public function __construct(IModelMap $modelMap)
    {
        $this->modelMap = $modelMap;
    }

    /**
     *  Returns DB column names for given attributes (or all if empty / '*'),
     *  always including primary key columns.
     *
     * @param array $attributes
     * @return array
     */
    public function getColumnNames(array $attributes = []): array
    {
         // We don't want to use SELECT *, rather list all columns - security reasons/best practices
        // If no attributes or wildcard → let ModelMap decide which attributes are “visible”
        if ($attributes === [] || in_array('*', $attributes, true)) {
            $attrList = $this->modelMap->getAttributeNames($attributes); // attribute names
            $columns  = $this->mapAttributesNamesToColumns($attrList);
        }
        else {
            $columns = $this->mapAttributesNamesToColumns($attributes);
        }

        // Ensure PK columns are always selected
        foreach ($this->modelMap->getPrimaryKeyColumns() as $pkColumn) {
            if ($pkColumn !== null && !in_array($pkColumn, $columns, true)) {
                $columns[] = $pkColumn;
            }
        }

        return $columns;
    }

    /**
     * @param string $attributeName
     * @return string
     * @throws CodeException
     */
    public function mapAttributeNameToColumn(string $attributeName): string
    {
        $map = $this->modelMap->getFullAttributeMap();

        if(array_key_exists($attributeName, $map)) {
            return $map[$attributeName];
        }

        throw new CodeException('Attribute map doesn\'t exist.', 'lf2102', ['attributeName' => $attributeName]);
    }

    /**
     * @param array $attributes
     * @param array $include
     * @return array
     */
    public function mapAttributesNamesToColumns(array $attributes = [], array $include = [], bool $applyAliases = true): array
    {
        $allAttributes = false;
        if($attributes === [] || in_array('*', $attributes)) {
            $allAttributes = true;
        }
        elseif($include !== []) {
            $attributes = array_unique(array_merge($attributes, $include));
        }

        if(!$allAttributes) { // add mandatory attributes
            $attributes = array_unique(array_merge($attributes,  $this->modelMap->getMandatoryAttributes()));
        }

        $columns = [];
        foreach ($this->modelMap->getAttributeMap($attributes) as $attr => $column) {
            if ($allAttributes || (in_array($attr, $attributes) && !in_array($column, $columns)))  //add only what is missing
                $columns[$attr] = $column;
        }

        if($applyAliases) {
            $columns = $this->applyColumnNamesAliases($columns);
        }

        // We only need DB column names list here
        return array_values($columns);
    }

    /**
     * @param array $columnNames
     * @return array
     */
    protected function applyColumnNamesAliases (array $columnNames) : array
    {
        $aliasMap = $this->modelMap->getColumnAliasMap(); // ['column_name' => 'users.column_name as column_name']

        foreach ($aliasMap as $attr => $alias) {
            $columnIndex = array_search($attr, $columnNames);
            if($columnIndex === false)
                continue;

            $columnNames[$columnIndex] = $aliasMap[$attr];
        }

        return $columnNames;
    }

    /**
     * @param array $columns
     * @param array $specificJsons
     * @return array
     */
    public function encodeJsons(array $columns, array $specificJsons = []): array
    {
        $jsonColumns = empty($specificJsons)
            ? $this->modelMap->getJsons()
            : $specificJsons;

        $forceJsonObjectOnAttributes = $this->modelMap->getJsonsForceObject();

        foreach ($jsonColumns as $col) {
            if (!array_key_exists($col, $columns)) {
                continue;
            }

            $value = $columns[$col];

            if (in_array($col, $forceJsonObjectOnAttributes, true)) {
                $columns[$col] = json_encode($value, JSON_FORCE_OBJECT);
            } else {
                $columns[$col] = json_encode($value);
            }
        }

        return $columns;
    }

    /**
     * @param \stdClass $columns
     * @return \stdClass
     */
    public function decodeJsons(\stdClass &$columns): \stdClass
    {
        foreach ($this->modelMap->getJsons() as $col) {
            if (!property_exists($columns, $col)) {
                continue;
            }

            $value = $columns->$col;

            // Only decode JSON-like strings; avoid touching non-JSON scalars
            if (is_string($value) && $value !== '') {
                $first = $value[0];
                if ($first === '{' || $first === '[') {
                    $decoded = json_decode($value, true);
                    // If decode fails, keep original (avoid silent data loss)
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $columns->$col = $decoded;
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * @param \stdClass|null $row
     * @return array|null
     */
    public function demapSingle(?\stdClass $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $row = $this->decodeJsons($row);
        $attributes = $this->mapColumnsToAttributes($row);

        $attributes = $this->modelMap->doCustomDemapping($attributes, $row);

        return $attributes;
    }


    public function demap(Collection|array $rows, ?string $collectionKeyAttribute = 'id'): Collection
    {
        $attributesList = new Collection();

        foreach ($rows as $row) {
            $row = $this->decodeJsons($row);
            $attributes = $this->mapColumnsToAttributes($row);

            // allow custom array-level transformations
            $attributes = $this->modelMap->doCustomDemapping($attributes, $row);

            // Determine key
            $key = $attributes[$collectionKeyAttribute] ?? ($attributes['id'] ?? null);

            if ($key !== null) {
                $attributesList->put($key, $attributes);
            } else {
                $attributesList->push($attributes);
            }
        }

        return $attributesList;
    }

    /**
     * @param \stdClass $row
     * @param array $reverseAliases
     * @return array
     */
    public function mapColumnsToAttributes(\stdClass $row, array $reverseAliases = []) : array
    {
        $attributes = [];
        foreach ($this->modelMap->getFullAttributeMap() as $attr => $column) {
            if (property_exists($row, $column))
                $attributes[$attr] = $row->$column;
        }

        if(empty($reverseAliases)) return $attributes;

        foreach($reverseAliases as $alias => $attr) {
            if (property_exists($row, $alias))
                $attributes[$attr] = $row->$alias;
        }

        return $attributes;
    }

    /**
     * @param array $conditions
     * @return array
     * @throws CodeException
     */
    public function mapConditionsColumnNames(array $conditions): array
    {
        if(count($conditions) > 0 && is_string(array_values($conditions)[0])) {
            $incomingConditions = $conditions;
            $conditions = [];
            $conditions[] = $incomingConditions;
        }

        $modelMap = $this->modelMap->getFullAttributeMap();
        foreach($conditions as $key => $criterium) {
            if (!array_key_exists(0, $criterium)) {
                throw new CodeException(
                    'Invalid condition format; expected [attribute, value] or [attribute, operator, value].',
                    'lf2100',
                    ['criterium' => $criterium]
                );
            }

            if(!array_key_exists($criterium[0], $modelMap)) {
                throw new CodeException('Get by $conditions does not have a correct attribute. Attribute does not exist.', 'lf2101',
                    ['modelMap' => $modelMap, 'attribute' => $criterium[0]]);
            }

            $conditions[$key][0] = $modelMap[$criterium[0]];
        }

        return $conditions;
    }

    /**
     * @param IModel $model
     * @return array
     * @throws CodeException
     */
    public function map(IModel $model, array $attributes = [], array $except = []): array
    {
        $columns = $this->mapAttributesToColumns($model->getDirty($attributes, $except));

        // add primary key, if it is not null in the model
        foreach($this->modelMap->getPrimaryKey() as $key) {
            if(!empty($model->{$key})) {
                $columns[$this->mapAttributeNameToColumn($key)] = $model->{$key};
            }
        }

        $columns = $this->modelMap->doCustomMapping($columns, $model, $attributes);

        return $this->encodeJsons($columns);
    }

    /**
     * @param array $attributesWithValues
     * @return array
     */
    public function mapAttributesToColumns(array $attributesWithValues = []): array
    {
        $primaryKey = $this->modelMap->getPrimaryKey();
        $attributesMap = $this->modelMap->getAttributeMap(array_keys($attributesWithValues));

        $columns = [];
        foreach ($attributesWithValues as $attr => $value) {
            if (array_key_exists($attr, $attributesMap)) {
                //if value is an object, we cannot store it to DB directly. toDBArray() is called. Each model implements it

                if(is_object($value) && $value instanceof IModel) {
                    $value = $this->modelMap->demapAttribute($attr, $value);
                }

                $columns[$attributesMap[$attr]] = $value;
            }
        }

        // if primary key attribute is null or missing, unset corresponding column (for insert)
        foreach ($primaryKey as $pkAttr) {
            if (!isset($attributesWithValues[$pkAttr])) {
                $columnName = $attributesMap[$pkAttr] ?? null;
                if ($columnName !== null && array_key_exists($columnName, $columns)) {
                    unset($columns[$columnName]);
                }
            }
        }

        return $columns;
    }

    /**
     * @param $orderBy
     * @return array
     */
    public function mapOrderBy($orderBy = []): array
    {
        $mappedOrderBy = [];
        if(is_array($orderBy) && array_key_exists('attribute', $orderBy)) {
            $mappedOrderBy['column'] = $this->mapAttributeNameToColumn($orderBy['attribute']);
            $mappedOrderBy['order'] = $orderBy['order'];
        }

        return $mappedOrderBy;
    }
}
