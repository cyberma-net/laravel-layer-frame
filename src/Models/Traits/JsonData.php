<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 26.1.2021
 * Time: 11:19
 */

namespace  Cyberma\LayerFrame\Models\Traits;

trait JsonData
{

    protected $_data = [];


    /**
     * @param string|null $key
     * @return array|null
     */
    public function getData (string $key = null)
    {

        if(is_null($key))
            return $this->_data;

        if(isset($this->_data[$key]))
            return $this->_data[$key];

        return null;
    }

    /**
     * @param string $key
     * @param $item
     */
    public function setData (string $key, $item)
    {

        if( !array_key_exists('data', $this->originalAttributes)) {  //set originalAttribute only once and if  it has been changed
            $this->originalAttributes['data'] = $this->_data;   //marks attribute as dirty (changed)
        }
        $this->_data[$key] = $item;
    }

    /**
     * @param string $key
     */
    protected function deleteDataItem(string $key)
    {
        if(isset($this->_data[$key])) {
            $this->saveOriginalAttribute('data');
            unset($this->_data[$key]);
        }
    }
}
