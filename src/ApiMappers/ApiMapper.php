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


    public function mapModelToApi(IModel $model, array $apiMap): array
    {
        $source = $model->toArray();
        $output = [];

        foreach ($apiMap as $key => $value) {
            if (!is_numeric($key)) {  // associative mapping: attr => apiName
                $attrName = $key;
                $apiName = $value;
            }
            else {  // numeric mapping: [ "id", "name" ]
                $attrName = $value;
                $apiName = $value;
            }

            // custom mapper
            if (isset($this->customMappers[$attrName])) {
                $output[$apiName] = ($this->customMappers[$attrName])($model);
                continue;
            }

            // default map from attributes
            $output[$apiName] = $source[$attrName] ?? null;
        }

        return $output;
    }
}
