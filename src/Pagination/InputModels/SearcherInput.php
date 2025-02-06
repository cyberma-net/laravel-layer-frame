<?php

namespace Cyberma\LayerFrame\Pagination\InputModels;

use Cyberma\LayerFrame\InputModels\InputModel;
use Cyberma\LayerFrame\Pagination\TableSearcher;
use Illuminate\Validation\Rule;

class SearcherInput extends InputModel
{

    protected $inputFields = [
        'searchAt' => null,
        'searchFor' => null,
        'searchOperator' => null,
    ];

    protected $validatorRules = [
        'urlSearch' => [
            'searchAt' => 'sometimes|nullable|alpha_num|max:60',
            'searchFor' => 'sometimes|alpha_num|max:200',
            'searchOperator' => 'sometimes|nullable',
        ],
    ];

    protected $errorCodes = [
        'urlSearch' => 'lf2118'
      ];

    protected $errorMessages = [];


    protected function prepareErrorMessages(): array
    {
        return [
            'urlSearch' => _('Search parameters are not correct.'),
        ];
    }

    /**
     * @param string $currentInput
     * @return void
     */
    protected function prepareValidationRules (string $currentInput) : void
    {
        switch ($currentInput) {

            case 'urlSearch' :
               $this->validatorRules['urlSearch']['searchOperator'] = ['sometimes', Rule::in(TableSearcher::getAllowedSearchOperators())];
               break;
        }
    }


    /**
     * @return array|null
     */
    public function prepareValidationMessages() : array
    {
        return [
            'urlSearch' => [
                'searchFor.max' => _('The searched value is too long.'),
                'searchFor.*' => _('The searched value is not valid.'),
                'searchAt.max' => _('The searched attribute name is too long.'),
                'searchAt.*' => _('The searched attribute name is not valid.'),
            ]
        ];
    }
}
