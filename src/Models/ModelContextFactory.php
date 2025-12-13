<?php

namespace Cyberma\LayerFrame\Models;

use Cyberma\LayerFrame\Contracts\Models\IModelContext;
use Cyberma\LayerFrame\Contracts\Models\IModelContextFactory;

class ModelContextFactory implements IModelContextFactory
{
    public function createModelContext(array $data = []): IModelContext
    {
        return new ModelContext($data);
    }
}

