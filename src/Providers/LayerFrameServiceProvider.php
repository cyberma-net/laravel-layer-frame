<?php

namespace Cyberma\LayerFrame\Providers;

use Cyberma\LayerFrame\ApiMappers\ApiMapper;
use Cyberma\LayerFrame\Contracts\ApiMappers\IApiMapper;
use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\Errors\IErrorBag;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelContextFactory;
use Cyberma\LayerFrame\Contracts\Pagination\IPaginator;
use Cyberma\LayerFrame\Contracts\Pagination\ITableSearcher;
use Cyberma\LayerFrame\Contracts\Repositories\IRepository;
use Cyberma\LayerFrame\Contracts\InputParsers\IInputParser;
use Cyberma\LayerFrame\DBMappers\DBMapper;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Cyberma\LayerFrame\Errors\ErrorBag;
use Cyberma\LayerFrame\Exceptions\ExceptionHandler;
use Cyberma\LayerFrame\InputParsers\InputParser;
use Cyberma\LayerFrame\ModelMaps\ModelMap;
use Cyberma\LayerFrame\Models\ModelContextFactory;
use Cyberma\LayerFrame\Pagination\InputModels\PaginatorInput;
use Cyberma\LayerFrame\Pagination\InputModels\SearcherInput;
use Cyberma\LayerFrame\Pagination\Paginator;
use Cyberma\LayerFrame\Pagination\TableSearcher;
use Cyberma\LayerFrame\Repositories\Repository;
use Illuminate\Support\ServiceProvider;


class LayerFrameServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerGeneralObjects();
        $this->registerInterfaceObjects();
        $this->registerDBObjects();
    }


    private function registerGeneralObjects()
    {
        $this->app->singleton(ExceptionHandler::class, function () {
            return new ExceptionHandler();
        });

        $this->app->bind(IErrorBag::class, function () {
            return new ErrorBag();
        });

        $this->app->singleton(IInputParser::class, function () {
            return new InputParser();
        });

        $this->app->singleton(IApiMapper::class, function () {
            return new ApiMapper();
        });

        $this->app->singleton(IModelContextFactory::class, function () {
            return new ModelContextFactory();
        });
    }


    private function registerInterfaceObjects()
    {
        $this->app->singleton(ITableSearcher::class, function() {
            return new TableSearcher(new InputParser(), new SearcherInput());
        });

        $this->app->singleton(IPaginator::class, function() {
            return new Paginator(new InputParser(), new PaginatorInput());
        });
    }


    private function registerDBObjects()
    {
        $this->app->bind(IModelMap::class, ModelMap::class);
        $this->app->bind(IDBStorage::class, function($app, array $params) {
            return new DBStorage($params['modelMap']);
        });
        $this->app->bind(IDBMapper::class, function($app, array $params) {
            return $app->make(DBMapper::class, $params);
        });
        $this->app->bind(IRepository::class, function($app, array $params) {
            $dbStorage = array_key_exists('dbStorage', $params) ? $params['dbStorage'] : $app->make(IDBStorage::class, $params);
            $dbMapper = array_key_exists('dbMapper', $params) ? $params['dbMapper'] : $app->make(IDBMapper::class, $params);
            $modelMap = $params['modelMap'];

            return new Repository($dbStorage, $dbMapper, $modelMap);
        });
    }
}
