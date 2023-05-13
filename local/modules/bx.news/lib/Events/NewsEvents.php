<?php /** @noinspection PhpUnused */

namespace BX\News\Events;

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
            ['FROM' => 'iblock', 'EVENT' => 'OnAfterIBlockElementDelete', 'METHOD' => 'processingNews'],//TODO: подумать, что делать при удалении
        ];
    }


    /**
     * @param $fields
     */
    public static function processingNews(&$fields): void
    {
        
    }
}