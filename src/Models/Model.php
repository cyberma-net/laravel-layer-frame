<?php

namespace Cyberma\LayerFrame\Models;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Exceptions\CodeException;


class Model implements IModel
{
    /*---------------------------------
      Public attributes (magic)
      - all protected properties starting with "_"
      - exposed as camelCase keys (id, name, ...)
    ---------------------------------*/
    protected $_id;

    protected $_createdAt;

    protected $_updatedAt;

    //// These are handled by the class itself, don't change them!
    ///     /**
    //     * Internal registry:
    //     *  'id' => &$_id
    //     *  'createdAt' => &$_createdAt
    //     */
       protected $attributes = [];   //filled automatically by recognizing members' names;  $attrName => pointer to var;  id => &$_id

      /**
       * Tracks original values of dirty attributes
       *  'id' => 10
       */
       protected $originalAttributes = [];    //will be filled with everything that has changed. Used for data updates; Key is set to the !internal! variable name, e.g. _id


    const DEFAULT_ATTRIBUTES = [];  // internal attrName => defaultValue;  e.g.  '_id' => 1

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->registerAttributes();
        $this->setMany($attributes);
    }

    /**
     * Registers all the variables defined in the class with names starting with _
     */
    protected function registerAttributes() : void
    {
        foreach(get_object_vars($this) as $var => $value){
            if(substr($var, 0, 1) == '_') {
                $this->attributes[substr($var, 1)] = &$this->$var;
            }
        }
    }


    /**
     * Dirty tracking on
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws CodeException
     */
    public function set(string $name, mixed $value): void
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new CodeException("Attribute '$name' does not exist in ".static::class);
        }

        $current = $this->attributes[$name];

        if (!array_key_exists($name, $this->originalAttributes) && $current !== $value) {
            $this->originalAttributes[$name] = $current;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CodeException
     */
    public function get(string $name)
    {
        if(array_key_exists($name, $this->attributes)) {  //check for language specific attributes first
            return $this->attributes[$name];
        }

        throw new CodeException('Requested attribute does not exist in Model.', 'lf2115', ['attribute' => $name]);
    }

    /**
     * All attributes dirty
     *
     * @param array $attributes
     * @param array $ignore
     * @return void
     * @throws CodeException
     */
    public function setMany(array $attributes, array $ignore = []): void
    {
        foreach ($attributes as $name => $value) {
            if (in_array($name, $ignore)) continue;

            if (array_key_exists($name, $this->attributes)) {
                $this->set($name, $value);
            }
        }
    }

    /**
     * Fill does not assign original attributes, should be used for DB read only
     * No attribute will be marked as dirty
     * LS columns are expected to come as arrays
     * @param array $attributes
     * @param array $ignore
     */
    public function hydrate(array $attributes, array $ignore = []) : void
    {
        foreach ($attributes as $name => $value) {
            if(in_array($name, $ignore))  //ignore this attribute
                continue;

            if (array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CodeException
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->attributes))
            return $this->attributes[$name];

        throw new CodeException('Requested attribute $'. $name . ' does not exist in the class: ' . static::class, 'lf2114', ['class' => static::class]) ;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws CodeException
     */
    public function __set(string $name, $value)
    {
        $internalPropName = '_' . $name;

        if (!property_exists($this,$internalPropName) && !property_exists($this, $name)) {
                throw new CodeException('Requested attribute for set does not exist in the class: ' . static::class, '109903', ['attribute' => $name, 'class' => static::class]) ;
        }

        if(array_key_exists($name, $this->attributes)) {
            if( !array_key_exists($name, $this->originalAttributes) && $this->attributes[$name] !== $value) {   //set originalAttribute only once and if  it has been changed
                $this->originalAttributes[$name] = $this->attributes[$name];
            }

            $this->attributes[$name] = $value;

            return;
        }

        try {
            $this->$internalPropName = $value;
        } catch (\TypeError $e) {
            throw new \TypeError(
                "Invalid value for attribute '{$name}': {$e->getMessage()}",
                0,
                $e
            );
        }

        // Register reference
        $this->attributes[$name] = &$this->$internalPropName;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @param $attributes
     * @param array $except
     */
    protected function setAttributesToDefault(array $attributes, array $except = []) : void
    {
        foreach($attributes as $attribute) {
            if(in_array($attribute, $except)) continue;

            if($this->attributeExists($attribute)) {
                $this->$attribute = static::DEFAULT_ATTRIBUTES[$attribute];
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function attributeExists(string $name) : bool
    {
        return property_exists($this, '_' . $name);
    }

    /**
     * @param string $name
     */
    public function resetAttributeToOriginal(string $name)
    {
        if (isset($this->originalAttributes[$name])) {
            if(array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $this->originalAttributes[$name];
            }
        }
    }

    /**
     * @param array $names
     * @return array
     */
    public function attributes(array $names = []) : array
    {
        if($names === []) {
            return $this->attributes;
        }

        $out = [];
        foreach($names as $name) {
            if (array_key_exists($name, $this->attributes)) {
                $out[$name] = $this->attributes[$name];
            }
        }

        return $out;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name) : bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @param array $names
     * @param array $except
     * @return array
     */
    public function toArray(array $names = [], array $except = []) : array
    {
        $out = [];
        if($names !== []) {
            return $this->toArraySpecifiedAttributes($names, $except);
        }

        foreach($this->attributes as $name => $value) {
            if(in_array($name, $except)) continue;
            $out[$name] = $value;
        }

        return $out;
    }

    /**
     * @param array $names
     * @param array $except
     * @return array
     */
    protected function toArraySpecifiedAttributes(array $names, array $except = []) : array
    {
        $out = [];
        foreach($names as $name) {
            if(in_array($name, $except)) continue;

            if(array_key_exists($name, $this->attributes)) {
                $out[$name] = $this->attributes[$name];
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getAllNotNullAttributes() : array
    {
        $attrs = [];
        foreach($this->attributes as $attr => $value) {
            if(!is_null($value)) {
                $attrs[$attr] = $value;
            }
        }

        return $attrs;
    }

    /**
     * @param bool $force
     * @return void
     */
    public function makeAllAttributesDirty(bool $force = false): void
    {
        foreach($this->attributes as $attr => $value) {
            $this->markDirty($attr, [], $force);
        }
    }

    /**
     * @param string|array $names
     * @param array $oldValues - ['attributeName' => 'value']
     * @param bool $force
     */
    public function markDirty(string|array $names, array $oldValues = [], bool $force = false) : void
    {
        if(!is_array($names)) {
            $names = [$names];
        }

        foreach($names as $name) {
            if($force) {
                $this->originalAttributes[$name] = null;
                continue;
            }

            if(!array_key_exists($name, $this->originalAttributes)) {  //set originalAttribute only once and keep the oldest value
                $this->originalAttributes[$name] = isset($oldValues[$name]) ? $oldValues[$name] : null;   //marks attribute as dirty (changed)
            }
        }

    }

    /**
     * @param array $selectedNames
     * @param array $except
     * @return array
     */
    public function getDirty(array $selectedNames = [], array $except = []): array
    {
        $allAttributes = $this->toArray($selectedNames, $except);
        $dirty = [];
        foreach($this->originalAttributes as $name => $value) {
            if (array_key_exists($name, $allAttributes) && $value !== $allAttributes[$name])  //take only changed values
                $dirty[$name] = $allAttributes[$name];
        }

        return $dirty;
    }

    /**
     * @param string[] $names, [] means all of them
     * @return void
     */
    public function resetDirty(array $names = []): void
    {
        if(empty($names)) {
            $this->originalAttributes = [];

            return;
        }

        foreach($names as $name) {
            unset($this->originalAttributes[$name]);
        }
    }
}
