<?php

namespace BX\News\Agents;

use Exception;

/**
 * Class AbstractAgents
 * @package BX\News\Agents
 */
abstract class AbstractAgents
{
    /**
     * @return string
     */
    public static function getName(): string
    {
        return '\\'.static::class.'::execute();';
    }

    /**
     * @return string
     */
    public static function execute(): string
    {
        try {
            static::exec();
        } catch (Exception $e) {
            //@todo логирование добавить
        }
        return static::getName();
    }

    /**
     * @return void
     */
    abstract public static function exec():void ;
}