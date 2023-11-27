<?php
namespace Cyberma\LayerFrame\DBStorage\Traits;

use Cyberma\LayerFrame\Exceptions\CodeException;
use Illuminate\Database\QueryException;
use Cyberma\LayerFrame\Exceptions\Exception;


trait DBErrors
{

    /**
     * @param QueryException $exception
     * @return mixed
     * @throws CodeException
     * @throws Exception
     */
    protected function processSQLerrors (QueryException $exception)
    {

        $errorInfo = $exception->errorInfo;
        if (empty($errorInfo)) {   //error between DB and PHP
            throw new CodeException(_('Database communication error.'), 'lf2103', ['message' => $exception->getMessage(), 'bindings' => $exception->getBindings()]);
        }

        $exception = $this->getException($errorInfo[1], $errorInfo[2]);

        if ($exception === false) { // check for common errors
            $this->processCommonSQLerrors($errorInfo); // throws exception
        }

        throw new Exception($exception[1], $exception[2], $exception[3],  isset($exception[4]) ? $exception[4] : 400);
    }

    /**
     * @param $code
     * @param string $message
     * @return array|false
     */
    protected function getException ($code, string $message)
    {

        $exceptions = $this->modelMap->getDBExceptions();

        if (!isset($exceptions[$code]))
            return false;

        $exceptions = $exceptions[$code]() ;  //process only selected exceptions


        if (!is_array($exceptions[0]))  //there is only a single exception under one error code; it can be array of errors under one error code
            return $exceptions;

        foreach ($exceptions as $exception) {    //multiple exceptions under one error code, e.g. for multiple columns
            if (strpos($message, $exception[0]) !== false)
                return $exception;
        }

        return false;
    }

    /**
     * @param array $errorInfo
     * @return mixed
     * @throws Exception
     */
    protected function processCommonSqlErrors (array $errorInfo)
    {
        $exception = $this->getCommonException($errorInfo[1], $errorInfo[2]);

        if($exception) {
            throw new Exception($exception[1], $exception[2], $exception[3], isset($exception[4]) ? $exception[4] : 400);
        }

        throw new Exception($errorInfo[2], 'lf2' . $errorInfo[1], $errorInfo, 500);
    }

    /**
     * @param string $code
     * @param string $message
     * @return false|mixed
     */
    protected function getCommonException (string $code, string $message)
    {
        $exceptions = $this->getCommonDBExceptions();

        if (!isset($exceptions[$code]))
            return false;

        $exceptions = $exceptions[$code]() ;  //process only selected expcetions

        foreach ($exceptions as $exception) {
            if (strpos($message, $exception[0]) !== false)
                return $exception;
        }

        return false;
    }
}
