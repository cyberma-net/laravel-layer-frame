<?php
/**
 * Created by PhpStorm.
 
 * Date: 18.1.2018
 * Time: 15:58
 */

namespace Cyberma\LayerFrame\Exceptions;

use Throwable;

class CodeException extends Exception
{

    protected $data;

    /**
     * CodeException constructor.
     * @param string $message
     * @param string $code
     * @param int $httpCode
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int|string $code = 0, $data = null, int $httpCode = 500, Throwable $previous = null)
    {

        if ($data instanceof \Exception) {
            $exceptionObject = $data;
            $data = ['message' => $exceptionObject->getMessage(),
                     'line' => $exceptionObject->getLine(),
                     'file' => $exceptionObject->getFile(),
            ];
        }

        parent::__construct($message, $code, $data, $httpCode, $previous);
    }

    /**
     * @return null
     */
    public function getData()
    {
        return config('app.debug') == true
              ?  $this->data
              : [];
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
