<?php

namespace Cyberma\LayerFrame\Exceptions;

use Exception as LaravelException;
use Throwable;

class Exception extends LaravelException
{

    protected $data, $httpStatus, $lfCode;

    protected $customData = null;

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param int $httpStatus
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', string|int $lfCode = '0000', $data = null, int $httpStatus = 400, ?Throwable $previous = null)
    {
        $this->lfCode = (string)$lfCode;

        parent::__construct($message, 0, $previous);

        $this->httpStatus = $httpStatus;
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
        return $this->httpStatus;
    }

    /**
     * @param mixed $httpError
     */
    public function setHttpError($httpError)
    {
        $this->httpStatus = $httpError;
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

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'lfCode'  => $this->lfCode,
            'data'    => $this->data,
            'status'  => $this->httpStatus,
        ];
    }
}
