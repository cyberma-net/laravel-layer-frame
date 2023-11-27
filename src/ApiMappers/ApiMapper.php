<?php
/**

 *
 
 * Date: 21.02.2021
 */

namespace Cyberma\LayerFrame\ApiMappers;

use Cyberma\LayerFrame\Contracts\ApiMappers\IApiMapper;
use Cyberma\LayerFrame\Contracts\Models\IModel;


class ApiMapper implements IApiMapper
{
    public function mapModelToApi(IModel $model, array $apiMap) : array
    {
        $attributes = $model->toArray();

        $outAttributes = [];
        foreach($apiMap as $attr => $apiName) {
            //if attribute is numeric, that means, that it is not associative ('attrName' => 'newAttrName'), just attributes listed in simple array to avoid repeating of the attr name

            if(is_numeric($attr)) {
                $outAttributes[$apiName]  = array_key_exists($apiName, $attributes) ? $attributes[$apiName] : null;
            }
            else {
                $outAttributes[$apiName]  = array_key_exists($attr, $attributes) ? $attributes[$attr] : null;
            }
        }

        return $outAttributes;
    }
}
