<?php
/**

 *

 * Date: 19.01.2021
 */

namespace Cyberma\LayerFrame\Utils;


class ArrayFunctions
{
    /**
     * @param array $array
     * @param array $removeThis
     * @return array
     */
    public static function removeFromBeginning (array $array, array $removeThis) : array
    {
        $arrayValues = array_values($array);
        $arrayKeys = array_keys($array);
        $removeThis = array_values($removeThis);

        foreach($removeThis as $i => $val) {

            if(!array_key_exists($i, $arrayValues))
                continue;

            if($arrayValues[$i] === $removeThis[$i]) {
                unset(
                    $array[ $arrayKeys[$i] ]
                );
            }
        }

        return $array;
    }
}
