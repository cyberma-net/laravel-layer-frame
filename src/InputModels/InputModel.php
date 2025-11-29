<?php

namespace  Cyberma\LayerFrame\InputModels;

use Cyberma\LayerFrame\Contracts\Errors\IErrorBag;
use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Contracts\InputModels\IInputModel;
use Illuminate\Support\MessageBag;


class InputModel implements IInputModel
{

    protected $inputFields = [];

    protected $validatorRules = [];   //array for multiple validators separated by action;  actions can be "save" "create" etc, see ItemInput for example

    protected $errorMessages = [];    //mesages for multiple validators;  e.g. ['creation' => ...,  'verification' => ...]

    protected $errorCodes = [];

    protected $receivedInputFields = [];   //a list of inputFields that actually came via Input. Only these inputs will be provided via toArray(). This is neccessary for being able to patch only a part of the DB data


    public function __construct()
    {
        $this->errorMessages = $this->prepareErrorMessages();
    }

    /**
     * @param string $currentInput
     * @return void
     */
    protected function prepareValidationRules (string $validatedSet) : void
    {
        //implemented in children
    }

    /**
     * Implement in children
     */
    protected function prepareErrorMessages(): array
    {
        return [];
    }

    /**
     * @param string $sourceRules
     * @param string $targetRules
     */
    protected function mergeRules(string $sourceRules, string $targetRules)
    {
        $this->validatorRules[$targetRules] = array_merge($this->validatorRules[$sourceRules], $this->validatorRules[$targetRules]);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasInputField (string $fieldName) : bool
    {
        return array_key_exists($fieldName, $this->inputFields);
    }

    /**
     * @param array $ignore
     * @return array
     */
    public function toArray(array $ignore = []) : array
    {
        $out = [];

        foreach($this->receivedInputFields as $inp) {
            if (in_array($inp, $ignore)) continue;

            $out[$inp] = $this->inputFields[$inp];
        }

        return $out;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function selectedAttributestoArray(array $attributes) : array
    {
        $out = [];

        foreach($this->receivedInputFields as $inp) {
            if (!in_array($inp, $attributes)) continue;

            $out[$inp] = $this->inputFields[$inp];
        }

        return $out;
    }

    /**
     * @param string $validatedSet
     * @param array $errors
     * @return IErrorBag
     */
    public function getErrorBag(string $validatedSet, array $errors = []): IErrorBag
    {

        $errBag = resolve(IErrorBag::class);
        $errBag->fill(
            $this->getErrorMessage($validatedSet),
            $errors,
            $this->getErrorCode($validatedSet)
        );

        return $errBag;
    }

    /**
     * @param string $validatedSet
     * @param MessageBag|null $errors
     * @throws Exception
     */
    public function throwException (string $validatedSet, ?MessageBag $errors = null)
    {
        throw new Exception ($this->getErrorMessage($validatedSet), $this->getErrorCode($validatedSet), is_null($errors) ? [] : $errors->getMessages());
    }

    /**
     * @param string $validatedSet
     * @return mixed|string
     */
    public function getErrorMessage(string $validatedSet)
    {
        if(!array_key_exists($validatedSet, $this->errorMessages)) {
            return _('Something went wrong during input validation');
        }

        return $this->errorMessages[$validatedSet];
    }

    /**
     * Implement this in child classes, if any special validation is required, otherwise don't implement it
     *
     * @param mixed $requestData
     * @param string $validatedSet
     */
    public function doExtraValidations($requestData, string $validatedSet)
    {
        return;
    }

    /**
     * @param string $validatedSet
     * @return mixed|string
     */
    public function getErrorCode(string $validatedSet)
    {
        if(!array_key_exists($validatedSet, $this->errorCodes)) {
            return '1033';
        }

        return $this->errorCodes[$validatedSet];
    }

    /**
     * @param string $validatedSet
     * @return array
     * @throws CodeException
     */
    public function getValidationRules(string $validatedSet): array
    {
        if(!array_key_exists($validatedSet, $this->validatorRules)) {
            throw new CodeException('Internal error in input parser. Please report this error.', 'lf2112');
        }

        return $this->validatorRules[$validatedSet];
    }

    /**
     * @param array $rawData
     */
    public function hydrate(array $rawData)
    {
        foreach($rawData as $name => $value) {
            if(array_key_exists($name, $this->inputFields)) {
                $this->inputFields[$name] =  $name == 'slug' ? strtolower($value) :  $value;  //make slug lower case
                if(!in_array($name, $this->receivedInputFields)) {
                    $this->receivedInputFields[] = $name;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CodeException
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->inputFields))
            return $this->inputFields[$name];

        throw new CodeException('Requested attribute $'. $name . ' does not exist in InputModel: ' . static::class, 'lf2117', ['attribute' => $name, 'class' => static::class]) ;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws CodeException
     */
    public function __set(string $name, $value)
    {
        if(array_key_exists($name, $this->inputFields)) {
            $this->inputFields[$name] = $value;

            if(!in_array($name, $this->receivedInputFields)) {
                $this->receivedInputFields[] = $name;
            }

        }
    }

    /**
     * @param string $property
     * @return bool
     */
    public function __isset(string $property): bool
    {
        return array_key_exists($property, $this->inputFields)
            && $this->inputFields[$property] !== null;
    }

    /**
     * @param string $validatedSet
     * @return array
     */
    public function getValidationMessages(string $validatedSet) : array
    {
        $messages = $this->prepareValidationMessages($validatedSet);

        if (isset ($messages[$validatedSet]))
            return $messages[$validatedSet];
        else
            return [];
    }

    /**
     * @return array
     */
    public function prepareValidationMessages() : array //implement this in child classes. Add custom validation messages here if needed
    {
        return [];
    }
}
