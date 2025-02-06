<?php

namespace Cyberma\LayerFrame\Contracts\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;


interface IBaseRepositoryFactory
{
    /**
     * @param IDBStorage $dbStorage
     * @param IDBMapper $dbMapper
     * @param IModelMap $modelMap
     * @return \Cyberma\LayerFrame\Contracts\Repositories\IRepository
     */
    public function createRepository(IDBStorage $dbStorage, IDBMapper $dbMapper, IModelMap $modelMap): IRepository;
}
