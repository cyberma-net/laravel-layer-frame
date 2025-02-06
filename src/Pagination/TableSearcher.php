<?php

namespace Cyberma\LayerFrame\Pagination;

use Cyberma\LayerFrame\Contracts\Pagination\ITableSearcher;
use Cyberma\LayerFrame\Contracts\InputParsers\IInputParser;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Pagination\InputModels\SearcherInput;


class TableSearcher implements ITableSearcher
{

    protected $searchAt = null;
    protected $searchFor = null;
    protected $operator = '=';

    const ALLOWED_SEARCH_OPERATORS = [
        'eq' => '=',
        'hi' => '>',
        'lo' => '<',
        'eqLo' => '<=',
        'eqHi' => '>=',
        'like' => 'like',
        'dateEq' => 'date=',
        'dateEqHi' => 'date>=',
        'dateEqLo' => 'date<=',
        'dateLo' => 'date<',
        'dateHi' => 'date>',
        'null' => 'null',
        'not_null' => 'not null',
    ];
    /**
     * @var IInputParser
     */
    private $inputParser;
    /**
     * @var SearcherInput
     */
    private $searcherInput;


    public function __construct(IInputParser $inputParser, SearcherInput $searcherInput)
    {
        $this->inputParser = $inputParser;
        $this->searcherInput = $searcherInput;
    }


    public static function getAllowedSearchOperators ()
    {
        return array_keys(static::ALLOWED_SEARCH_OPERATORS);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     */
    public function setSearcher (string|null $searchAt, string $searchFor, string $operator = 'eq')
    {
        // 2. parse the data sent by the api
        //    and put it to the registrationInput inputFields
        $this->searcherInput = $this->inputParser->parse($this->searcherInput , [
            'searchAt' => $searchAt,
            'searchFor' => $searchFor,
            'searchOperator' => $operator
        ], 'urlSearch');


        $this->searchFor = $searchFor;
        $this->searchAt = $searchAt;
        $this->operator = $operator;
    }

    /**
     * @return array
     */
    public function getConditions () : array
    {
        if(array_key_exists($this->operator, static::ALLOWED_SEARCH_OPERATORS)) {
            $operator = static::ALLOWED_SEARCH_OPERATORS[$this->operator];
        }
        elseif (in_array($this->operator, static::ALLOWED_SEARCH_OPERATORS)) {
            $operator = $this->operator;
        }
        else {
            $operator = '=';
        }

        return [
            $this->searchAt => [$this->searchFor, $operator]
        ];
    }

    /**
     * @param string $name
     * @return float|int|string
     * @throws CodeException
     */
    public function __get(string $name)
    {
        switch ($name) {
            case  'searchAt' :
                return $this->searchAt;

            case  'searchFor' :
                return $this->searchFor;

            case  'operator' :
                return $this->operator;
        }

        throw new CodeException('Requested attribute $'. $name . ' does not exist in the class: ' . static::class, 'lf2111', ['class' => static::class]) ;
    }
}
