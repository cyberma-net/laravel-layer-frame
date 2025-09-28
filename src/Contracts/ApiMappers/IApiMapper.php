<?php

namespace Cyberma\LayerFrame\Contracts\ApiMappers;

use Cyberma\LayerFrame\Contracts\Models\IModel;

interface IApiMapper
{
    public function mapModelToApi(IModel $model, array $apiMap): array;


    public function setCustomMapper(string $attributeName, callable $callback): void;


    public function setCustomMappers(array $customMappers): void;
}
