<?php

namespace Cyberma\LayerFrame\DBMappers\Traits;

class DBMapperHiddenColumns
{
    /**
     * @param array $columns
     * @param array $attributes
     * @return array
     */
    public function removeHiddenColumns (array &$columns, array $attributes = []): array
    {
        //remove hidden columns if they are not explicitely listed in attributes
        foreach ($this->modelMap->getHiddenColumns() as $hid) {
            $attrForRemoval = array_search($hid, $columns) && !array_search($hid, $attributes);
            if ($attrForRemoval !== false) {
                unset($columns[$attrForRemoval]);
            }
        }

        return $columns;
    }
}
