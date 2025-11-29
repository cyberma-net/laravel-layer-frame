<?php

namespace Cyberma\LayerFrame\Exceptions;


class ExceptionHandler
{
    //creates json or array response based on the Exception
    public function translateToResponse (Exception $e, ?string $format = 'application/json')
    {
        return $this->prepareResponse ($e, empty($format) ? 'application/json' : $format);
    }

    /**
     * @param Exception $e
     * @param $format
     * @return \Illuminate\Http\Response
     */
    private function prepareResponse (Exception $e, $format)
    {
        $response = [
            'error' => true,
            'message' =>  ($e instanceof CodeException) ? 'Internal error: ' . $e->getMessage() : $e->getMessage() ,
            'data' => $e->getData(),
            'code' => $e->getLfCode(),
            'track' => env('APP_DEBUG', false) ? $e->getTrace() : null,
        ];

        return response($response, $e->getHttpError());
    }

    /**
     * Processes exceptions for an array of arrays. E.g.
     *
     * [contactPersons => [
     *     0 => [
     *        'name' => 'Name 1',
     *        'email' => 'email@test.com
     *      ],
     *     1 => [
     *        'name' => 'Name 2',
     *        'email' => 'email2@test.com
     *      ],
     *   ]
     * ]
     *
     * Validator returns array in this format: [contactPersons.0.name => ['Too long', 'Required'] ]
     *
     * @param array $errorData
     * @param string $attributeName
     * @return array
     */
    public function handleListAttributes(array $errorData, string $attributeName) : array
    {
        $errorDataKeys = array_keys($errorData);

        $outErrors = [
            $attributeName => [],
        ];

        $outErrors = array_merge($errorData, $outErrors);

        foreach($errorDataKeys as $key) {
            $errName = explode('.', $key);
            if($errName[0] == $attributeName) {
                $outErrors[$attributeName][$errName[1]] = [$errName[2] => $errorData[$key]];
                unset($outErrors[$key]);  //we dont need the original error, it is in the array
            }
        }

        return $outErrors;
    }
}
