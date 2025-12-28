<?php

namespace Cyberma\LayerFrame\Contracts\Pagination;

interface ITableSearcher
{
    /**
     * Get all allowed search operators
     * @return array
     */
    public static function getAllowedSearchOperators(): array;

    /**
     * Set search parameters
     * @param string $searchAt The attribute/column to search in
     * @param string $searchFor The value to search for
     * @param string $operator The search operator (default: 'eq')
     * @return void
     */
    public function setSearcher(string $searchAt, string $searchFor, string $operator = 'eq'): void;

    /**
     * Get the search conditions as an array
     * @return array
     */
    public function getConditions(): array;
}
