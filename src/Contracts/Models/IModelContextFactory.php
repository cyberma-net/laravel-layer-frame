<?php

namespace Cyberma\LayerFrame\Contracts\Models;

interface IModelContextFactory
{
    public function createModelContext(array $data = []): IModelContext;
}
