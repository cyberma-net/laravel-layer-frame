<?php
/**
 *
 * Class that can handle any type of content. Usually use as a data carrier for additional data
 */

namespace Cyberma\LayerFrame\Utils;


class Context
{
    private $data;

    /**
     * Context constructor.
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get (?string $key = null)
    {
        return !empty($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * @return mixed|null
     */
    public function getAll ()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return Context
     */
    public static function create ($data = null): Context
    {
        return new Context ($data);
    }
}
