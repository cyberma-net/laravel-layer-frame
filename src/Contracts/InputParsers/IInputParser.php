<?php
/**

 *
 
 * Date: 22.03.2021
 */

namespace Cyberma\LayerFrame\Contracts\InputParsers;

use Cyberma\LayerFrame\Contracts\InputModels\IInputModel;

interface IInputParser
{
    /**
     * @param IInputModel $inputModel
     * @param array $requestData
     * @param string $validatedSet
     * @param array $additionalInputs
     * @return IInputModel
     * @throws \Cyberma\LayerFrame\Exceptions\Exception
     */
    public function parse(IInputModel $inputModel, array $requestData, string $validatedSet, array $additionalInputs = []): IInputModel;
}
