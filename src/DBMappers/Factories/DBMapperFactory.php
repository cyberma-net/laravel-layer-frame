<?php

namespace Cyberma\LayerFrame\DBMappers\Factories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapperFactory;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\Contracts\Repositories\DBExporters\IModelDBExporter;
use Cyberma\LayerFrame\DBMappers\DBMapper;


class DBMapperFactory implements IDBMapperFactory
{
    /**
     * @var IModelMap
     */
    private IModelMap $modelMap;
    /**
     * @var IModelFactory
     */
    private IModelFactory $modelFactory;

    /**
     * DBMapperFactory constructor.
     * @param IModelMap $modelMap
     * @param IModelFactory $modelFactory
     * @param IModelDBExporter $modelDBExporter
     */
    public function __construct(IModelMap $modelMap, IModelFactory $modelFactory)
    {

        $this->modelMap = $modelMap;
        $this->modelFactory = $modelFactory;
    }

    /**
     * @return DBMapper
     */
    public function createDBMapper(): DBMapper
    {
        return new DBMapper($this->modelMap, $this->modelFactory);
    }
}
