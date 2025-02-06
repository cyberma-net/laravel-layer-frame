<?php

namespace Cyberma\LayerFrame\Errors;

use Cyberma\LayerFrame\Contracts\Errors\IErrorBag;

class ErrorBag implements IErrorBag
{
    private string $errorCode = '0001';
    private array $errors = [];
    private string $message = '';
    private int $httpCode = 200;


    /**
     * @param string $message
     * @param array $errors
     * @param string $errorCode
     * @param int $httpCode
     */
    public function fill ($message = '', $errors = [], $errorCode = '0001', $httpCode = 200): void
    {
        $this->message = $message;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
        $this->httpCode = $httpCode;
    }

    /**
     * @return bool
     */
    public function hasErrors () : bool
    {
        return !(empty($this->errors) && empty($this->message));
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     */
    public function setErrorCode(string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return is_array($this->errors)
            ? $this->errors
            : [$this->errors];
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode)
    {
        $this->httpCode = $httpCode;
    }
}
