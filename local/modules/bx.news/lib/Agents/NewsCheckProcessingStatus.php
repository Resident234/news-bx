<?php

namespace BX\News\Agents;


/**
 * Class ActionDeadline
 * @package BX\News\Agents
 */
class NewsCheckProcessingStatus extends AbstractAgents
{
    /**
     * @return void
     */
    public static function exec(): void
    {
          //регулярно проверять статус каждой незакрытой задачи
    }
}