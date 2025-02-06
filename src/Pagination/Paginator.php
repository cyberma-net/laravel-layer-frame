<?php

namespace Cyberma\LayerFrame\Pagination;

use Cyberma\LayerFrame\Contracts\InputModels\IInputModel;
use Cyberma\LayerFrame\Contracts\InputParsers\IInputParser;
use Cyberma\LayerFrame\Contracts\Pagination\IPaginator;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Pagination\InputModels\PaginatorInput;


class Paginator implements IPaginator
{

    protected int $page = 1;
    protected int $perPage = 30;
    protected string $orderBy = 'createdAt';
    protected string $order = 'asc';

    protected IInputParser $inputParser;
    protected IInputModel $paginatorInput;


    public function __construct(IInputParser $inputParser, PaginatorInput $paginatorInput)
    {

        $this->inputParser = $inputParser;
        $this->paginatorInput = $paginatorInput;
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param string $orderBy
     * @param string $order
     */
    public function setPaginator (int $page = 1, int $perPage = 30, string $orderBy = 'createdAt', string $order = 'asc')
    {
        // 2. parse the data sent by the api
        //    and put it to the registrationInput inputFields
        $this->paginatorInput = $this->inputParser->parse($this->paginatorInput, [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'order' => $order
        ], 'urlPaginate');

        if(!is_numeric($page) or $page < 1) {
            $page = 1;
        }

        if(!is_numeric($perPage) || $perPage < 2) {
            $perPage = 2;
        }

        if($order != 'asc' && $order != 'desc') {
            $order = 'asc';
        }

        $this->page = $page;
        $this->perPage = $perPage;
        $this->orderBy = $orderBy;
        $this->order = $order;
    }


    /**
     * @param string $name
     * @return float|int|string
     * @throws CodeException
     */
    public function __get(string $name)
    {
        switch ($name) {

            case  'page' :
                return $this->page;

            case  'perPage' :
            case  'take' :
                return $this->perPage;

            case  'orderBy' :
                return $this->orderBy;

            case  'order' :
                return $this->order;

            case  'skip' :
                return ($this->page -1) * $this->perPage;
        }

        throw new CodeException('Requested attribute $'. $name . ' does not exist in the class: ' . self::class, 'lf2116', ['class' => static::class]) ;
    }


    /**
     * @param string $name
     * @param $value
     * @throws CodeException
     */
    public function __set(string $name, $value)
    {
        switch ($name) {

            case  'page' :
                $this->page = $value;
                break;

            case  'perPage' :
            case  'take' :
                $this->perPage = $value;
                break;

            case  'orderBy' :
                $this->orderBy = $value;
                break;

            case  'order' :
                $this->order = $value;
                break;

            case  'skip' :
                $this->perPage = $value;
                break;

            default:
                throw new CodeException('Requested attribute $'. $name . ' does not exist in the class: ' . self::class, 'lf2116', ['class' => static::class]) ;
        }
    }


    public function getPagination(): array
    {
        return [
          'page' => $this->page,
          'perPage' => $this->perPage,
        ];
    }


    public function getOrderBy(): array
    {
        return [
            'attribute' => $this->orderBy,
            'order' => $this->order,
        ];
    }

    /**
     * @return int
     */
    public function getLimit() : int
    {
        return  $this->perPage;
    }


    /**
     * @return int
     */
    public function getOffset() : int
    {
        return ($this->page -1) * $this->perPage;
    }


    /**
     * @param array $attributeMap
     * @return string
     * @throws Exception
     */
    public function getOrderByColumn (array $attributeMap)
    {
        if (array_key_exists($this->orderBy, $attributeMap)) {
            return $attributeMap[$this->orderBy];
        }

        throw new Exception('Pagination parameter orderBy is not correct. Such parameter does not exist.', 'lf2110', [], 400) ;
    }
}
