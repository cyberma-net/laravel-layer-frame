<?php
/**

 *
 
 * Date: 22.02.2021
 */

namespace Cyberma\LayerFrame\Contracts\Repositories;


interface IRepositoryFactory
{
    public function createRepository(): IRepository;
}
