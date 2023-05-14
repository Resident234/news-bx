<?php /** @noinspection PhpUnused */

namespace BX\News\Events;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use BX\News\HelperManager;
use BX\News\Integration\TasksService;
use BX\News\Orm\Tables\NewsProcessingTable;
use CIBlockElement;
use Exception;

/**
 * Class HelpEvents
 * @package BX\News\Events
 */
class NewsEvents extends AbstractEvents
{
    /**
     * @return array
     */
    public static function getEvents(): array
    {
        return [
            ['FROM' => 'iblock', 'EVENT' => 'OnAfterIBlockElementAdd', 'METHOD' => 'processingNews'],
            ['FROM' => 'iblock', 'EVENT' => 'OnAfterIBlockElementUpdate', 'METHOD' => 'processingNews'],
            ['FROM' => 'iblock', 'EVENT' => 'OnAfterIBlockElementDelete', 'METHOD' => 'removeNewsDepedencies'],
        ];
    }

    /**
     * Отметка “Обработана”
     *
     * При создании новости отметка не установлена.
     * После редактирования новости с установленной отметкой отметка должна сбрасываться.
     *
     * @param $fields
     * @throws LoaderException
     * @throws Exception
     */
    public static function processingNews(&$fields)
    {
        Loader::includeModule('bx.news');
        if ((int)$fields['IBLOCK_ID'] === (int)HelperManager::getInstance()->Iblock()->getIblockIdIfExists('news', 'news')) {
            try {
                $requestId = TasksService::createWork($fields['NAME'], (string)$fields['ID']);
                $res = NewsProcessingTable::save(['NEWS_ID' => $fields['ID'], 'REQUEST_ID' => $requestId]);
                if (!$res->isSuccess()) {
                    throw new Exception(join('; ', $res->getErrorMessages()));
                }
                CIBlockElement::SetPropertyValuesEx($fields['ID'], $fields['IBLOCK_ID'], ['PROCESSED' => '']);
            } catch (Exception $e) {
                //todo логируем
            }
        }
        return true;
    }

    /**
     * При удалении необработанной новости удаляться будет запись из таблицы активных задач
     *
     * @param $fields
     * @return true
     * @throws LoaderException
     */
    public static function removeNewsDepedencies(&$fields)
    {
        Loader::includeModule('bx.news');
        if (
            (int)$fields['IBLOCK_ID'] === (int)HelperManager::getInstance()->Iblock()->getIblockIdIfExists('news', 'news')
        ) {
            try {
                $res = NewsProcessingTable::remove(['NEWS_ID' => $fields['ID']]);
                if (!$res->isSuccess()) {
                    throw new Exception(join('; ', $res->getErrorMessages()));
                }
            } catch (Exception $e) {
                //todo логируем
            }
        }
        return true;
    }
}