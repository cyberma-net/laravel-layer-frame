<?php

namespace Cyberma\LayerFrame\Models;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Exceptions\CodeException;


/**

 *
 
 * Date: 21.02.2021
 */

class Model implements IModel
{

    /* ATTRIBUTES
     * - available for magic get/set
     * - start with $_
     */

    const DEFAULT_ATTRIBUTES = [];  // internal attrName => defaultValue;  e.g.  '_id' => 1

    // Common attributes that all models have
    protected $_id;

    protected $_createdAt;

    protected $_updatedAt;

    //// These are handled by the class itself, don't change them!
       protected $attributes = [];   //filled automatically by recognizing members' names;  $attrName => pointer to var;  id => &$_id

       protected $originalAttributes = [];    //will be filled with everything that has changed. Used for data updates; Key is set to the !internal! variable name, e.g. _id
    //// end of automatically handled


    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->registerAttributes();
        $this->setAttributes($attributes);
    }

    /**
     * Registers all the variables defined in the class with names starting with _, ex_
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
     * All attributes will be marked as changed (dirty) by default
     *
     * @param array $attributes
     * @param array $ignoreAttributes
     */
    public function setAttributes(array $attributes, array $ignoreAttributes = []) : void
    {
        foreach ($attributes as $name => $value) {
            if(in_array($name, $ignoreAttributes))  //ignore this attribute
                continue;

            if(array_key_exists($name, $this->attributes)) {

                if( $this->attributes[$name] !== $value && !array_key_exists($name, $this->originalAttributes)) {  //set originalAttribute only once and if  it has been changed
                    $this->originalAttributes[$name] = $this->attributes[$name];  //marks attribute as dirty (changed)
                }
                $this->attributes[$name] = $value;
            }
        }
    }

    /**
     * Fill does not assign original attributes, should be used for DB read only
     * No attribute will be marked as dirty
     * LS columns are expected to come as arrays
     * @param array $attributes
     * @param array $ignoreAttributes
     */
    public function fill (array $attributes, array $ignoreAttributes = []) : void
    {
        foreach ($attributes as $name => $value) {
            if(in_array($name, $ignoreAttributes))  //ignore this attribute
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
     * @param mixed $value
     * @throws CodeException
     */
    public function __set(string $name, $value)
    {
        if(array_key_exists($name, $this->attributes)) {

            if( $this->attributes[$name] !== $value && !array_key_exists($name, $this->originalAttributes)) {   //set originalAttribute only once and if  it has been changed
                $this->originalAttributes[$name] = $this->attributes[$name];
            }

            $this->attributes[$name] = $value;
        }
        else {
            throw new CodeException('Requested attribute for set does not exist in the class: ' . static::class, '109903', ['attribute' => $name, 'class' => static::class]) ;
        }
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
     * @param $key
     * @return mixed
     * @throws CodeException
     */
    public function getAttribute(string $name)
    {
        if(array_key_exists($name, $this->attributes)) {  //check for language specific attributes first
            return $this->attributes[$name];
        }

        throw new CodeException('Requested attribute does not exist in Model.', 'lf2115', ['attribute' => $name]);
    }

    /**
     * @param array $whichAttributes
     * @return array
     */
    public function getAttributes(array $whichAttributes = []) : array
    {
        if($whichAttributes === []) {
            return $this->attributes;
        }

        $out = [];
        foreach($whichAttributes as $attrName) {
            $out[$attrName] = $this->attributes['_' . $attrName];
        }

        return $out;
    }

    /**
     * @param string $attr
     * @return bool
     */
    public function hasAttribute (string $attr) : bool
    {
        return array_key_exists($attr, $this->attributes);
    }

    /**
     * @param string $property
     * @return bool
     */
    public function __isset(string $property) : bool
    {
        return !empty($this->attributes[$property]);
    }

    /**
     * @return array
     */
    public function toArray(array $whichAttributes = [], array $except = []) : array
    {
        $out = [];
        if($whichAttributes !== []) {
            return $this->toArraySpecifiedAttributes($whichAttributes, $except);
        }

        foreach($this->attributes as $attr => $value) {
            if(in_array($attr, $except)) continue;
            $out[$attr] = $value;
        }

        return $out;
    }

    /**
     * @param array $whichAttributes
     * @param array $except
     * @return array
     */
    protected function toArraySpecifiedAttributes(array $whichAttributes, array $except = []) : array
    {
        foreach($whichAttributes as $attr) {
            if(in_array($attr, $except)) continue;

            if(array_key_exists($attr, $this->attributes)) {
                $out[$attr] = $this->attributes[$attr];
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getAllNotNullAttributes () : array
    {

        $attrs = [];
        foreach($this->attributes as $attr => $value) {
            if(!is_null($value)) {
                $attrs[$attr] = $value;
            }
        }

        return $attrs;
    }

    public function makeAllAttributesDirty(bool $force = false)
    {
        foreach($this->attributes as $attr => $value) {
            $this->makeAttributeDirty($attr, null, $force);
        }
    }

    /**
     * @param string|array $attribute
     * @param mixed $oldValue
     */
    public function makeAttributeDirty(string|array $attributes, $oldValue = null, bool $force = false) : void
    {
        if(!is_array($attributes)) {
            $attributes = [$attributes];
        }

        if(!is_array($oldValue)) {
            $oldValue = [$oldValue];
        }

        foreach($attributes as $i => $attribute) {
            if($force) {
                $this->originalAttributes[$attribute] = null;
                continue;
            }

            if(!array_key_exists($attribute, $this->originalAttributes)) {  //set originalAttribute only once and if  it has been changed
                $this->originalAttributes[$attribute] = count($oldValue) > $i ? $oldValue[$i] : null;   //marks attribute as dirty (changed)
            }
        }

    }

    /**
     * @param array $selectedAttributes
     * @param array $except
     * @return array
     */
    public function getChangedAttributes (array $selectedAttributes = [], array $except = []): array
    {

        $allAttributes = $this->toArray($selectedAttributes, $except);
        $output = [];
        foreach($this->originalAttributes as $name => $value) {
            if (array_key_exists($name, $allAttributes) && $value !== $allAttributes[$name])  //take only changed values
                $output[$name] = $allAttributes[$name];
        }

        return $output;
    }
}
