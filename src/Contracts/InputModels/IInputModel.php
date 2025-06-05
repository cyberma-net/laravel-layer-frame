<?php

namespace Cyberma\LayerFrame\Contracts\InputModels;

use Cyberma\LayerFrame\Contracts\Errors\IErrorBag;
use Illuminate\Support\MessageBag;

interface IInputModel
{

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasInputField(string $fieldName): bool;

    /**
     * @return array
     */
    public function toArray(array $ignore = []): array;

    /**
     * @return array
     */
    public function selectedAttributestoArray(array $attributes): array;

    /**
     * @param string $validatedSet
     * @param array $errors
     * @return IErrorBag
     */
    public function getErrorBag(string $validatedSet, array $errors = []): IErrorBag;

    /**
     * @param string $validatedSet
     * @param MessageBag|null $errors
     * @return mixed
     */
    public function throwException (string $validatedSet, ?MessageBag $errors = null);

    /**
     * @param string $validatedSet
     * @return mixed|string
     */
    public function getErrorMessage(string $validatedSet);

    /**
     * @param mixed $requestData
     * @param string $validatedSet
     */
    public function doExtraValidations($requestData, string $validatedSet);

    /**
     * @param string $validatedSet
     * @return mixed|string
     */
    public function getErrorCode(string $validatedSet);

    /**
     * @param string $validatedSet
     * @return array
     */
    public function getValidationRules(string $validatedSet): array;

    /**
     * @param array $rawData
     */
    public function fillAttributes(array $rawData);

    /**
     * @param string $validatedSet
     * @return array
     */
    public function getValidationMessages(string $validatedSet) : array;

    public function prepareValidationMessages(): array;

}
