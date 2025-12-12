<?php

namespace Cyberma\LayerFrame\Models;

use Cyberma\LayerFrame\Contracts\Models\IModelContext;

class ModelContext implements IModelContext
{
    public function __construct(protected array $data = []) {}

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function with(string $key, mixed $value): static
    {
        $clone = clone $this;
        $clone->data[$key] = $value;

        return $clone;
    }
}

