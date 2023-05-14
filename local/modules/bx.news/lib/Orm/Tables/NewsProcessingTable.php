<?php

namespace BX\News\Orm\Tables;

use Bitrix\Main\{ArgumentException,
    ORM\Data\Result,
    ORM\Fields\StringField,
    ORM\Query\Filter\ConditionTree,
    SystemException};
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Exception;

/**
 * Таблица хранит id новостей, для которых в данный момент выполняются задачи
 *
 * При изменении/добавлении новости запись сюда добавляется. После того как новость обработана, запись удаляется.
 *
 * Class NewsProcessingTable
 * @package BX\News\Orm\Tables
 */
class NewsProcessingTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return 'bx_news_processing';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap()
    {
        return [
            'ID'          => new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            'NEWS_ID'     => new IntegerField('NEWS_ID', ['required' => true]),
            'REQUEST_ID'  => new StringField('REQUEST_ID', ['required' => true]),
        ];
    }
    
    /**
     * @param array $fields
     * @return Result
     * @throws Exception
     */
    public static function save(array $fields): Result
    {
        if ($rowExist = static::query()->addSelect('*')->where('NEWS_ID', $fields['NEWS_ID'])->exec()->fetch()) {
            return static::update($rowExist['ID'], $fields);
        } else {
            return static::add($fields);
        }
    }
}