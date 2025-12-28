<?php

namespace Cyberma\LayerFrame\Contracts\Models;

interface IModelContextFactory
{
    /**
     * Create a new model context instance with the given data
     * @param array $data
     * @return IModelContext
     */
    public function createModelContext(array $data = []): IModelContext;
}
