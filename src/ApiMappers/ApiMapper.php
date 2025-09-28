<?php

namespace Cyberma\LayerFrame\ApiMappers;

use Cyberma\LayerFrame\Contracts\ApiMappers\IApiMapper;
use Cyberma\LayerFrame\Contracts\Models\IModel;


class ApiMapper implements IApiMapper
{

    private $customMappers = []; /** @var array<string, callable> */


    public function setCustomMapper(string $attributeName, callable $callback): void
    {
        $this->customMappers[$attributeName] = $callback;
    }

    /**
     * @param array<string, callable> $customMappers
     */
    public function setCustomMappers(array $customMappers): void
    {
        $this->customMappers = $customMappers;
    }


    public function mapModelToApi(IModel $model, array $apiMap) : array
    {
        $attributes = $model->toArray();

        $outAttributes = [];
        foreach($apiMap as $attr => $apiName) {
            //if attribute is numeric, that means, that it is not associative ('attrName' => 'newAttrName'), just attributes listed in simple array to avoid repeating of the attr name

            if(array_key_exists($attr, $this->customMappers)) {
                $outAttributes[$apiName] = ($this->customMappers[$attr])($model);
            }
            elseif(is_numeric($attr)) {
                $outAttributes[$apiName] = array_key_exists($apiName, $attributes) ? $attributes[$apiName] : null;
            }
            else {
                $outAttributes[$apiName] = array_key_exists($attr, $attributes) ? $attributes[$attr] : null;
            }
        }

        return $outAttributes;
    }
}
