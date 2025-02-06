<?php

namespace Cyberma\LayerFrame\Contracts\Pagination;

use Cyberma\LayerFrame\Exceptions\Exception;

interface IPaginator
{
    /**
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $order
     */
    public function setPaginator(int $page = 1, int $perPage = 30, string $sortBy = 'createdAt', string $order = 'asc');

    /**
     * @return array
     */
    public function getPagination(): array;

    /**
     * @return int
     */
    public function getLimit(): int;

    /**
     * @return int
     */
    public function getOffset(): int;

    /**
     * @param array $attributeMap
     * @return string
     * @throws Exception
     */
    public function getOrderByColumn(array $attributeMap);

    /**
     * @return array
     */
    public function getOrderBy(): array;

}
