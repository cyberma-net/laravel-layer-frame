<?php
/**
 *
 * Date: 23.04.2021
 */

namespace Cyberma\LayerFrame\Contracts\ApiMappers;

use Cyberma\LayerFrame\Contracts\Models\IModel;

interface IApiMapper
{
    public function mapModelToApi(IModel $model, array $apiMap): array;
}
