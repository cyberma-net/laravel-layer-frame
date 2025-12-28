<?php

namespace Cyberma\LayerFrame\Contracts\Models;

use Cyberma\LayerFrame\Contracts\Models\IModel;
use Cyberma\LayerFrame\Contracts\Models\IModelContext;

interface IModelFactory
{
    /**
     * Create a new model instance with the given attributes and optional context
     * @param array $attributes
     * @param IModelContext|null $context
     * @return IModel
     */
    public function createModel(array $attributes = [], ?IModelContext $context = null): IModel;
}
