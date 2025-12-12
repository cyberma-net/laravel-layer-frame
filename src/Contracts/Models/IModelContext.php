<?php

namespace Cyberma\LayerFrame\Contracts\Models;

interface IModelContext
{
    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function with(string $key, mixed $value): static;
}
