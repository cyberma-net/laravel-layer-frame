<?php

namespace Cyberma\LayerFrame\DBMappers;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Illuminate\Support\Collection;


class DBMapper implements IDBMapper
{

    /**
     * @var IModelMap
     */
    private IModelMap $modelMap;
    /**
     * @var IModelFactory
     */
    private IModelFactory $modelFactory;

    /**
     * DBMapper constructor.
     * @param IModelMap $modelMap
     * @param IModelFactory $modelFactory
     */
    public function __construct(IModelMap $modelMap, IModelFactory $modelFactory)
    {
        $this->modelMap = $modelMap;
        $this->modelFactory = $modelFactory;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getColumnNames(array $attributes = []): array
    {
        if ($attributes === [] || in_array('*', $attributes)) {   //we don't want to use SELECT *, rather list all columns - security reasons/best practices
            $columns = $this->mapAttributesNamesToColumns($this->modelMap->getAttributes($attributes));
        }

        // primary key is always present in the query
        foreach($this->modelMap->getPrimaryKeyColumns() as $key) {
            if(!in_array($key, $columns)) {
                $columns[] = $key;
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
        $map = $this->modelMap->getAttributeMap();

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

        return $columns;
    }

    /**
     * @param array $columnNames
     * @return array
     */
    protected function applyColumnNamesAliases (array $columnNames) : array
    {
        $aliasMap = $this->modelMap->getColumnAliasMap();

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
    public function encodeJsons (array $columns, array $specificJsons = []) : array
    {
        $jsonAttributes = empty($specificJsons) ? $this->modelMap->getJsons() : $specificJsons;

        $forceJsonObjectOnAttributes = $this->modelMap->getJsonsForceObject();  //which columns shall have {} instead of [] in the json

        foreach($jsonAttributes as $attr) {
            if (isset($columns[$attr])) {
                $columns[$attr] = in_array($attr, $forceJsonObjectOnAttributes)
                    ? json_encode($columns[$attr], JSON_FORCE_OBJECT)
                    : str_replace('[]', '{}', json_encode($columns[$attr]));
            }
        }

        return $columns;
    }

    /**
     * @param \stdClass $columns
     * @return \stdClass
     */
    public function decodeJsons (\stdClass &$columns) : \stdClass
    {
        foreach($this->modelMap->getJsons() as $attr) {
            if (property_exists ($columns, $attr) && !is_array($columns->$attr))
                $columns->$attr = json_decode($columns->$attr, true);
        }

        return $columns;
    }

    /**
     * @param \stdClass|null $row
     * @return IModel|null
     */
    public function demapSingle (?\stdClass $row) : ?IModel
    {
        if(empty($row)) return null;

        if(!is_array($row)){
            $rows = [$row];
        }

        $model = $this->demap($rows);

        return is_null($model) || $model->count() == 0
            ? null
            : $model->pop();
    }

    /**
     * @param Collection|array $rows
     * @param string|null $collectionKeyParameter
     * @return Collection
     */
    public function demap (Collection|array $rows, string $collectionKeyParameter = null) : Collection
    {
        $models = new Collection();

        if (empty($rows)) return $models;

        foreach ($rows as $row) {

            $row = $this->decodeJsons($row);

            $attributes = $this->mapColumnsToAttributes($row);
            $newModel = $this->modelFactory->createModel();  /** @var IModel $newModel */

            $newModel->fill($attributes);  //don't mark attributes as dirty

            $newModel = $this->modelMap->doCustomDemapping($newModel, $row, $collectionKeyParameter);

            if(is_null($collectionKeyParameter)) {
                $models->push($newModel);
            }
            else {
                $models->put($newModel->$collectionKeyParameter, $newModel);
            }
        }

        return $models;
    }

    /**
     * @param \stdClass $row
     * @param array $reverseAliases
     * @return array
     */
    public function mapColumnsToAttributes (\stdClass $row, array $reverseAliases = []) : array
    {
        $attributes = [];
        foreach ($this->modelMap->getAttributeMap() as $attr => $column) {
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

        $modelMap = $this->modelMap->getAttributeMap();
        foreach($conditions as $key => $criterium) {
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
        $columns = $this->mapAttributesToColumns($model->getChangedAttributes($attributes, $except));

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
     * @param array $attributes
     * @return array
     */
    public function mapAttributesToColumns(array $attributes): array
    {
        $primaryKey = $this->modelMap->getPrimaryKey();
        $attributesMap =  $this->modelMap->getAttributeMap();

        $columns = [];
        foreach ($attributes as $attr => $value) {
            if (array_key_exists($attr, $attributesMap)) {
                //if value is an object, we cannot store it to DB directly. toDBArray() is called. Each model implements it

                if(is_object($value) && $value instanceof IModel) {
                    $value = $this->modelMap->demapAttribute($attr, $value);
                }

                $columns[$attributesMap[$attr]] = $value;
            }
        }

        //if primary key is null, then unset it - necessary for insert
        foreach($primaryKey as $key) {
            if(!isset($attributes[$key]) && array_key_exists($key, $columns)) {
                unset ($columns[$key]);
            }
        }

        return $columns;
    }

    /**
     * @param array $columns
     * @param array $attributes
     * @return array
     */
    protected function removeHiddenColumns (array &$columns, array $attributes = []): array
    {
        //remove hidden columns if they are not explicitely listed in attributes
        foreach ($this->modelMap->getHiddenColumns() as $hid) {
            $attrForRemoval = array_search($hid, $columns) && !array_search($hid, $attributes);
            if ($attrForRemoval !== false) {
                unset($columns[$attrForRemoval]);
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
        if(array_key_exists('attribute', $orderBy)) {
            $mappedOrderBy['column'] = $this->mapAttributeNameToColumn($orderBy['attribute']);
            $mappedOrderBy['order'] = $orderBy['order'];
        }

        return $mappedOrderBy;
    }
}
