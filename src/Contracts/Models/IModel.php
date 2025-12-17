<?php

namespace Cyberma\LayerFrame\Contracts\Models;

use Cyberma\LayerFrame\Exceptions\CodeException;

interface IModel
{
    /**
     * Dirty tracking on
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws CodeException
     */
    public function set(string $name, mixed $value): void;

    /**
     * All attributes will be marked as changed (dirty) by default
     *
     * @param array $attributes
     * @param array $ignore
     */
    public function setMany(array $attributes, array $ignore = []): void;

    /**
     * Fill does not assign original attributes, should be used for DB read only
     * No attribute will be marked as dirty
     * LS columns are expected to come as arrays
     * @param array $attributes
     * @param array $ignore
     */
    public function hydrate(array $attributes, array $ignore = []): void;

    /**
     * @param string $name
     */
    public function resetAttributeToOriginal(string $name);

    /**
     * @param string $name
     * @return mixed
     * @throws \Cyberma\LayerFrame\Exceptions\CodeException
     */
    public function get(string $name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * @param array $names
     * @param array $except
     * @return array
     */
    public function toArray(array $names = [], array $except = []) : array;

    /**
     * @return array
     */
    public function getAllNotNullAttributes(): array;

    /**
     * @param bool $force
     * @return void
     */
    public function makeAllAttributesDirty(bool $force = false): void;

    /**
     * @param string[] $names - [] means all
     * @return void
     */
    public function resetDirty(array $names = []): void;

    /**
     * @param string|array $names
     * @param mixed $oldValues - ['attributeName' => 'value'] or int/string
     * @param bool $force
     */
    public function markDirty(string|array $names, mixed $oldValues = null, bool $force = false) : void;

    /**
     * @param array $names
     * @return array
     */
    public function attributes(array $names = []) : array;

    /**
     * @param array $selectedNames
     * @param array $except
     * @return array
     */
    public function getDirty (array $selectedNames = [], array $except = []): array;

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
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool;
}
