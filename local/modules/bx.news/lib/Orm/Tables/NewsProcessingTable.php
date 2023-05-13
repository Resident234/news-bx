<?php

namespace BX\News\Orm\Tables;

use Bitrix\Main\{ArgumentException,
    ORM\Fields\FloatField,
    ORM\Fields\Relations\Reference,
    ORM\Fields\StringField,
    ORM\Query\Join,
    SystemException};
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Sevensuns\Utils\Orm\Collection\GroupResponsibleCollection;
use Sevensuns\Utils\Orm\Tables\Iblock\GroupsTable;

/**
 * Таблица хранит id новостей, для которых в данный момент выполняются задачи
 *
 * При изменении/добавлении новости запись сюда добавляется. После того, как новость обработана, запись удаляется.
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
        ];
    }
}