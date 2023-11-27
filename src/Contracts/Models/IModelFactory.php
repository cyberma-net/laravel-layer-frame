<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 19.10.2018
 * Time: 12:10
 */

namespace  Cyberma\LayerFrame\Contracts\Models;


interface IModelFactory
{
    public function createModel(array $attributes = []): IModel;
}
