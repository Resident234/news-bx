<?php

namespace BX\News;

use BX\News\Helpers\AgentHelper;
use BX\News\Helpers\IblockHelper;
use Exception;


/**
 * @method IblockHelper             Iblock()
 * @method AgentHelper              Agent()
 */
class HelperManager
{

    private $cache = [];

    private static $instance = null;

    private $registered = [];

    /**
     * @return HelperManager
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws Exception
     * @return Helper
     */
    public function __call($name, $arguments)
    {
        return $this->callHelper($name);
    }

    /**
     * @param $name
     * @throws Exception
     * @return Helper
     */
    protected function callHelper($name)
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $helperClass = '\\BX\\News\\Helpers\\' . $name . 'Helper';
        if (class_exists($helperClass)) {
            $this->cache[$name] = new $helperClass;
            return $this->cache[$name];
        }

        if (isset($this->registered[$name])) {
            $helperClass = $this->registered[$name];
            if (class_exists($helperClass)) {
                $this->cache[$name] = new $helperClass;
                return $this->cache[$name];
            }
        }

        throw new Exception("Helper $name not found");
    }
}
