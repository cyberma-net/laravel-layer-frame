<?php

namespace Cyberma\LayerFrame\Contracts\Errors;

interface IErrorBag
{
    /**
     * @param string $message
     * @param array $errors
     * @param string $errorCode
     * @param int $httpCode
     */
    public function fill($message = '', $errors = [], $errorCode = '0001', $httpCode = 400): void;

    /**
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * @return string
     */
    public function getErrorCode(): string;

    /**
     * @param string $errorCode
     */
    public function setErrorCode(string $errorCode): void;

    /**
     * @return array
     */
    public function getErrors(): array;

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     */
    public function setMessage(string $message): void;

    /**
     * @return int
     */
    public function getHttpCode();

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode);
}
