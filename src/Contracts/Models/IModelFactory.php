<?php

namespace  Cyberma\LayerFrame\Contracts\Models;


interface IModelFactory
{
    public function createModel(array $attributes = []): IModel;
}
