<?php

namespace Cyberma\LayerFrame\Contracts\ApiMappers;

use Cyberma\LayerFrame\Contracts\Models\IModel;

interface IApiMapper
{
    public function mapModelToApi(IModel $model, array $apiMap): array;
}
