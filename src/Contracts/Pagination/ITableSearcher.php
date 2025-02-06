<?php

namespace Cyberma\LayerFrame\Contracts\Pagination;

interface ITableSearcher
{
    public static function getAllowedSearchOperators();

    /**
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     */
    public function setSearcher(string $searchAt, string $searchFor, string $operator = 'eq');

    /**
     * @return array
     */
    public function getConditions(): array;
}
