<?php

namespace Cyberma\LayerFrame\Contracts\Repositories\DBExporters;

use Cyberma\LayerFrame\Contracts\Models\IModel;


interface IModelDBExporter
{
    public function exportModel(IModel $model, array $whichAttributes = [], array $except = []): array;
}
