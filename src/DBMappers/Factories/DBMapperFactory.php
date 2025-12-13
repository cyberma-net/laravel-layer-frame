<?php

namespace Cyberma\LayerFrame\DBMappers\Factories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapperFactory;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBMappers\DBMapper;


class DBMapperFactory implements IDBMapperFactory
{
    private IModelMap $modelMap;

    /**
     * DBMapperFactory constructor.
     * @param IModelMap $modelMap
     */
    public function __construct(IModelMap $modelMap)
    {
        $this->modelMap = $modelMap;
    }

    public function createDBMapper(): DBMapper
    {
        return new DBMapper($this->modelMap);
    }
}
