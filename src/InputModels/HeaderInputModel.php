<?php
/**

 *
 
 * Date: 21.02.2021
 */

namespace Cyberma\LayerFrame\InputModels;


class HeaderInputModel
{

    protected $validationRulesForHeader = [
        'lang' => 'sometimes|in:en_US'
    ];

    protected $headerAttributes = [];    //attributes from the API call header, e.g. lang

    /**
     * @return array
     */
    public function getValidationRulesForHeader(): array
    {
        return $this->validationRulesForHeader;
    }


    /**
     * @param array $attr
     */
    public function fillHeaderAttributes(array $attr) : void
    {
        foreach($attr as $name => $value) {
            $this->headerAttributes[$name] = $value;
        }
    }

    /**
     * @param array $validationRulesForHeader
     */
    public function setValidationRulesForHeader(array $validationRulesForHeader): void
    {
        $this->validationRulesForHeader = $validationRulesForHeader;
    }
}
