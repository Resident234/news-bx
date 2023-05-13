<?php

use Bitrix\Main\ModuleManager;
use BX\News\Agents\NewsCheckProcessingStatus;
use BX\News\Events\NewsEvents;
use BX\News\HelperManager;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
if (class_exists('bx_news')) {
    return;
}

/**
 * Class news_utils
 */
class bx_news extends CModule
{
    /** @var string */
    public $MODULE_ID;

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var string */
    public $MODULE_GROUP_RIGHTS;

    /** @var string */
    public $PARTNER_NAME;

    /** @var string */
    public $PARTNER_URI;

    public function __construct()
    {
        $this->MODULE_ID = 'bx.news';
        $this->MODULE_VERSION = '0.0.1';
        $this->MODULE_VERSION_DATE = '2023-05-13 17:25:00';
        $this->MODULE_NAME = 'Новости';
        $this->MODULE_DESCRIPTION = '';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'BX.News';
        $this->PARTNER_URI = '';
    }

    public function doInstall()
    {
        $this->includeEvents();
        NewsEvents::bind();
        HelperManager::getInstance()->Agent()->saveAgent([
            'MODULE_ID'      => $this->MODULE_ID,
            'NAME'           => NewsCheckProcessingStatus::getName(),
            'ACTIVE'         => 'Y',
            'IS_PERIOD'      => 'N',
            'NEXT_EXEC'      => '',
            'AGENT_INTERVAL' => 60
        ]);

        $iblockId = HelperManager::getInstance()->Iblock()->getIblockIdIfExists('news', 'news');
        HelperManager::getInstance()->Iblock()->saveProperty($iblockId, array (
            'NAME' => 'Обработана',
            'ACTIVE' => 'Y',
            'SORT' => '10',
            'CODE' => 'PROCESSED',
            'DEFAULT_VALUE' => '',
            'PROPERTY_TYPE' => 'L',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'C',
            'MULTIPLE' => 'N',
            'XML_ID' => NULL,
            'FILE_TYPE' => '',
            'MULTIPLE_CNT' => '5',
            'LINK_IBLOCK_ID' => '0',
            'WITH_DESCRIPTION' => 'N',
            'SEARCHABLE' => 'N',
            'FILTRABLE' => 'N',
            'IS_REQUIRED' => 'N',
            'VERSION' => '2',
            'USER_TYPE' => NULL,
            'USER_TYPE_SETTINGS' => NULL,
            'HINT' => '',
            'VALUES' =>
                array (
                    0 =>
                        array (
                            'VALUE' => 'Да',
                            'DEF' => 'N',
                            'SORT' => '10',
                            'XML_ID' => 'Y',
                        ),
                ),
        ));
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function doUninstall()
    {
        $this->includeEvents();
        NewsEvents::unBind();
        HelperManager::getInstance()->Agent()->deleteAgentIfExists($this->MODULE_ID, NewsCheckProcessingStatus::getName());
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    protected function includeEvents(): void
    {
        require_once dirname(__DIR__) . '/lib/Events/AbstractEvents.php';
        require_once dirname(__DIR__) . '/lib/Events/NewsEvents.php';
    }

    protected function saveProperty($iblockId, $fields): int
    {
        $property = $this->getProperty($iblockId, $fields['CODE']);
        if ($property) {
            return (int)$property['ID'];
        }

        return $this->addProperty($iblockId, $fields);
    }

    protected function getProperty($iblockId, $code): array
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            'CODE' => $code,
        ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        /* do not use =CODE in filter */
        $property = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $property;
    }

    protected function addProperty($iblockId, $fields): int
    {
        $default = [
            'NAME' => '',
            'ACTIVE' => 'Y',
            'SORT' => '500',
            'CODE' => '',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => '',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
            'LINK_IBLOCK_ID' => 0,
        ];

        if (!empty($fields['VALUES'])) {
            $default['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID'])) {
            $default['PROPERTY_TYPE'] = 'E';
        }

        $fields = array_replace_recursive($default, $fields);

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        //todo исключение кинуть
    }
}