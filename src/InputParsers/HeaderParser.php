<?php
/**

 *

 * Date: 21.02.2021
 */

namespace Cyberma\LayerFrame\InputParsers;

use Cyberma\LayerFrame\Exceptions\Exception;
use Cyberma\LayerFrame\Contracts\InputModels\IInputModel;
use Cyberma\LayerFrame\InputModels\HeaderInputModel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;


class HeaderParser
{
    /**
     * @param HeaderInputModel $inputModel
     * @param array $apiHeader
     * @return IInputModel
     * @throws Exception
     */
    public function parseApiHeader(HeaderInputModel $inputModel, array $apiHeader) : HeaderInputModel
    {
        if (empty($apiHeader))  //validator requires an array
            $apiHeader = [];

        $validator = $this->getValidator($apiHeader, $inputModel->getValidationRulesForHeader(), []);

        if ($validator->fails()) {
            throw new Exception(_('Request header is not correct. Please, report this error.'), 'lf2108', [], 500);
        }

        $inputModel->fillHeaderAttributes($apiHeader);

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
