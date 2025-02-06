<?php

namespace Cyberma\LayerFrame\Contracts\DBMappers;


interface IDBMapperFactory
{
    /**
     * @return IDBMapper
     */
    public function createDBMapper(): IDBMapper;
}
