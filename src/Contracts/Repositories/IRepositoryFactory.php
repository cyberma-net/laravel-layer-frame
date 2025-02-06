<?php

namespace Cyberma\LayerFrame\Contracts\Repositories;


interface IRepositoryFactory
{
    public function createRepository(): IRepository;
}
