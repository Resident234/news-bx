<?php

namespace BX\News;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CDBResult;
use CMain;
use Exception;
use ReflectionClass;
use ReflectionException;

class Helper
{
    /**
     * @deprecated
     * @var string
     */
    public $lastError = '';

    private $mode = [
        'test' => 0,
        'out_equal' => 0,
    ];

    /**
     * Helper constructor.
     * @throws Exception
     */
    public function __construct()
    {
        if (!$this->isEnabled()) {
            $this->throwException(
                __METHOD__,
                ''
                /*Locale::getMessage(
                    'ERR_HELPER_DISABLED',
                    [
                        '#NAME#' => $this->getHelperName(),
                    ]
                )*/
            );
        }
    }

    /**
     * @return string
     * @deprecated
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    public function isEnabled()
    {
        return true;
    }

    /**
     * @param array $names
     * @return bool
     */
    protected function checkModules($names = [])
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            try {
                if (!Loader::includeModule($name)) {
                    return false;
                }
            } catch (LoaderException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $method
     * @param $msg
     * @param string ...$vars
     * @throws Exception
     */
    protected function throwException($method, $msg, ...$vars)
    {
        $args = func_get_args();
        $method = array_shift($args);
        $msg = call_user_func_array('sprintf', $args);

        $msg = $this->getMethod($method) . ': ' . strip_tags($msg);

        $this->lastError = $msg;

        throw new Exception($msg);
    }

    /**
     * @param $method
     * @throws Exception
     */
    protected function throwApplicationExceptionIfExists($method)
    {
        /* @global $APPLICATION CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(
                $method,
                $APPLICATION->GetException()->GetString()
            );
        }
    }


    protected function getHelperName()
    {
        try {
            $classInfo = new ReflectionClass($this);
            return $classInfo->getShortName();
        } catch (ReflectionException $e) {
            return 'Helper';
        }
    }

    protected function hasDiff($exists, $fields)
    {
        return ($exists != $fields);
    }

    /**
     * @param $exists
     * @param $fields
     * @return bool
     */
    protected function hasDiffStrict($exists, $fields)
    {
        return ($exists !== $fields);
    }

    /**
     * @param $method
     * @param $fields
     * @param array $reqKeys
     * @throws Exception
     */
    protected function checkRequiredKeys($method, $fields, $reqKeys = [])
    {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                $this->throwException(
                    $method,
                    '',//todo в ленг фразу
                    /*Locale::getMessage(
                        'ERR_EMPTY_REQ_FIELD',
                        [
                            '#NAME#' => $name,
                        ]
                    )*/
                );
            }
        }
    }

    /**
     * @param CDBResult $dbres
     * @param bool $indexKey
     * @param bool $valueKey
     * @return array
     */
    protected function fetchAll(CDBResult $dbres, $indexKey = false, $valueKey = false)
    {
        $res = [];

        while ($item = $dbres->Fetch()) {
            if ($valueKey) {
                $value = $item[$valueKey];
            } else {
                $value = $item;
            }

            if ($indexKey) {
                $indexVal = $item[$indexKey];
                $res[$indexVal] = $value;
            } else {
                $res[] = $value;
            }
        }

        return $res;
    }

    private function getMethod($method)
    {
        $path = explode('\\', $method);
        $short = array_pop($path);
        return $short;
    }

}
