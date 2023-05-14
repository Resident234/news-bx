<?php

namespace BX\News\Agents;


use BX\News\HelperManager;
use BX\News\Integration\TasksService;
use BX\News\Orm\Tables\NewsProcessingTable;
use CIBlockElement;
use Exception;

/**
 * Регулярно проверять статус каждой незакрытой задачи
 *
 * Если Сервис отвечает, что задача выполнена - ставить отметку в соответствующей новости и более не проверять по ней статус.
 *
 * Class ActionDeadline
 * @package BX\News\Agents
 */
class NewsCheckProcessingStatus extends AbstractAgents
{
    /**
     * Обеспечить обработку новостей на стороннем сервисе - сервисе обработки новостей
     *
     * После выполнения обработки новости на Сервисе устанавливать отметку.
     *
     * @return void
     * @throws Exception
     */
    public static function exec(): void
    {
        $tasks = (NewsProcessingTable::getList())->fetchAll();
        foreach ($tasks as $task) {
            try {
                $status = TasksService::checkWork($task['REQUEST_ID']);
                if ($status === TasksService::STATUS_SUCCESS) {
                    NewsProcessingTable::delete($task['ID']);
                    $iblockId = (int)HelperManager::getInstance()->Iblock()->getIblockIdIfExists('news', 'news');
                    $propertyEnums = HelperManager::getInstance()->Iblock()->getPropertyEnums(['IBLOCK_ID' => $iblockId, 'CODE' => 'PROCESSED'], 'XML_ID');
                    $propertyProcessedId = $propertyEnums['Y']['ID'];
                    CIBlockElement::SetPropertyValuesEx(
                        $task['NEWS_ID'],
                        $iblockId,
                        ['PROCESSED' => $propertyProcessedId]
                    );
                }
            } catch (Exception $e) {
                /**
                 * Если реквест не существует, то новость успели удалить, пока таск обрабатывался.
                 * Тогда реквест из таблицы убираем, он нам больше незачем.
                 */
                NewsProcessingTable::delete($task['ID']);
            }
        }
    }
}