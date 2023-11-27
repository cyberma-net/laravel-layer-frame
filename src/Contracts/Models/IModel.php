<?php
namespace Cyberma\LayerFrame\Contracts\Models;

/**

 *
 
 * Date: 21.02.2021
 */
interface IModel
{
    /**
     * All attributes will be marked as changed (dirty) by default
     *
     * @param array $attributes
     * @param array $ignoreAttributes
     */
    public function setAttributes(array $attributes, array $ignoreAttributes = []): void;

    /**
     * Fill does not assign original attributes, should be used for DB read only
     * No attribute will be marked as dirty
     * LS columns are expected to come as arrays
     * @param array $attributes
     * @param array $ignoreAttributes
     */
    public function fill(array $attributes, array $ignoreAttributes = []): void;

    /**
     * @param string $name
     */
    public function resetAttributeToOriginal(string $name);

    /**
     * @param $key
     * @return mixed
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function getAttribute(string $name);

    /**
     * @param string $attr
     * @return bool
     */
    public function hasAttribute(string $attr): bool;

    /**
     * @return array
     */
    public function toArray(array $whichAttributes = [], array $except = []): array;

    /**
     * @return array
     */
    public function getAllNotNullAttributes(): array;

    public function makeAllAttributesDirty();

    /**
     * @param string|array $attribute
     * @param mixed $oldValue
     */
    public function makeAttributeDirty(string|array $attributes, $oldValue = null) : void;

    /**
     * @param array $whichAttributes
     * @return array
     */
    public function getAttributes(array $whichAttributes = []) : array;


    /**
     * @param array $selectedAttributes
     * @param array $except
     * @return array
     */
    public function getChangedAttributes (array $selectedAttributes = [], array $except = []): array;

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name);

    /**
     * @param string $name
     * @param $value
     * @return mixed
     */
    public function __set(string $name, $value);

    /**
     * @param string $property
     * @return bool
     */
    public function __isset(string $property) : bool;
}
