<?php

namespace Cyberma\LayerFrame\Contracts\Models;

interface IModelContext
{
    /**
     * Get a value from the context by key
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Check if a key exists in the context
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Create a new context instance with an additional key-value pair
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function with(string $key, mixed $value): static;
}
