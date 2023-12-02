<?php
/**
 *
 * Date: 27.02.2021
 */

namespace Cyberma\LayerFrame\Contracts\DBMappers;


interface IDBMapperFactory
{
    /**
     * @return IDBMapper
     */
    public function createDBMapper(): IDBMapper;
}
