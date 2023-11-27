<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 18.1.2018
 * Time: 15:58
 */

namespace Cyberma\LayerFrame\Exceptions;

use Throwable;

class DatabaseException extends Exception
{
    protected $data;

    public function __construct($message = "", $code = 0, $data = null, $httpCode = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $data, $httpCode, $previous);

        $this->data = $data;
    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
