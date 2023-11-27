<?php
/**

 
 * Date: 2.3.2018
 * Time: 22:42
 */

namespace Cyberma\LayerFrame\Pagination\InputModels;

use Cyberma\LayerFrame\InputModels\InputModel;


class PaginatorInput extends InputModel
{

    protected $inputFields = [
        'page' => null,
        'perPage' => null,
        'sortBy' => null,
        'sortDirection' => null,
    ];

    protected $validatorRules = [
        'urlPaginate' => [
            'page' => 'sometimes|numeric|min:1',
            'perPage' => 'sometimes|numeric|min:5',
            'sortBy' => 'sometimes|nullable|string|max:100',
            'sortDirection' => 'sometimes|in:asc,desc',
        ],
    ];

    protected $errorCodes = [
        'urlPaginate' => 1148
      ];

    protected $errorMessages = [];


    /**
     *
     */
    protected function prepareErrorMessages(): array
    {
        return [
            'urlPaginate' => _('Pagination parameters are not correct.'),
        ];
    }


    /**
     * @return array
     */
    public function prepareValidationMessages() : array
    {
        return [
            'urlPaginate' => [
                'page.*' => _i('The %s parameter is wrong.', 'page'),
                'perPage.*' => _i('The %s parameter is wrong.', 'perPage'),
                'sortBy.*' => _i('The %s parameter is wrong.', 'sortBy'),
                'sortDirection.*' => _i('The %s parameter is wrong.', 'sortDirection'),
            ]
        ];
    }
}
