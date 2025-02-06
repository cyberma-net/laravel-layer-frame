<?php

namespace  Cyberma\LayerFrame\InputParsers;

use Cyberma\LayerFrame\Contracts\InputModels\IInputModel;
use Cyberma\LayerFrame\Contracts\InputParsers\IInputParser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;


class InputParser implements IInputParser
{

    /**
     * @param IInputModel $inputModel
     * @param array $requestData
     * @param string $validatedSet
     * @param array $additionalInputs
     * @return IInputModel
     * @throws \Cyberma\LayerFrame\Exceptions\Exception
     */
    public function parse(IInputModel $inputModel, array $requestData, string $validatedSet, array $additionalInputs = []): IInputModel
    {

        if (empty($requestData))  //validator requires an array
            $requestData = [];

        if(!empty($additionalInputs)) {
            $requestData = array_merge($requestData, $additionalInputs);
        }

        $validator = $this->getValidator($requestData, $inputModel->getValidationRules($validatedSet), $inputModel->getValidationMessages($validatedSet));

        if ($validator->fails()) {
            $inputModel->throwException($validatedSet, $validator->errors());
        }

        $inputModel->doExtraValidations($requestData, $validatedSet); //throws Exception, if fails

        $inputModel->fillAttributes($requestData);

        return $inputModel;
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return Validator
     */
    protected function getValidator (array $data, array $rules, array $messages = []) : Validator
    {
        return empty($messages) ? ValidatorFacade::make($data, $rules) : ValidatorFacade::make($data, $rules, $messages);
    }
}
