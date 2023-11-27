<?php
/**
 * Created by PhpStorm.
 
 * Date: 18.1.2018
 * Time: 17:12
 */

namespace Cyberma\LayerFrame\Exceptions;

use Exception as LaravelException;
use Throwable;

class Exception extends LaravelException
{

    protected $data, $httpCode, $lfCode;

    protected $customData = null;

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param int $httpCode
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', string|int $code = 0, $data = null, int $httpCode = 200, Throwable $previous = null)
    {
        $this->lfCode = (string)$code;

        if(is_string($code)) {
            $code = (int)substr($code, 3);
        }

        parent::__construct($message, $code, $previous);

        $this->httpCode = $httpCode;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getHttpError()
    {
        return $this->httpCode;
    }

    /**
     * @param mixed $httpError
     */
    public function setHttpError($httpError)
    {
        $this->httpCode = $httpError;
    }

    /**
     * @return mixed
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    /**
     * @param mixed $customData
     */
    public function setCustomData($customData): void
    {
        $this->customData = $customData;
    }

    /**
     * @return string
     */
    public function getLfCode(): string
    {
        return $this->lfCode;
    }
}
