<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use BX\News\Agents\NewsCheckProcessingStatus;
use BX\News\Events\NewsEvents;
use BX\News\HelperManager;
use BX\News\Orm\Tables\NewsProcessingTable;

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
        $this->MODULE_NAME = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = '';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'BX.News';
        $this->PARTNER_URI = '';
    }

    public function doInstall()
    {
        $this->includeEvents();
        $this->includeHelpers();
        $this->includeAgents();
        $this->includeTables();
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
            'NAME' => Loc::getMessage('PROPERTY_PROCESSED_NAME'),
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
            'SEARCHABLE' => 'Y',
            'FILTRABLE' => 'Y',
            'IS_REQUIRED' => 'N',
            'VERSION' => '2',
            'USER_TYPE' => NULL,
            'USER_TYPE_SETTINGS' => NULL,
            'HINT' => '',
            'VALUES' =>
                array (
                    0 =>
                        array (
                            'VALUE' => Loc::getMessage('ENUM_YES'),
                            'DEF' => 'N',
                            'SORT' => '10',
                            'XML_ID' => 'Y',
                        ),
                ),
        ));
        $connection = Application::getConnection();
        if (!$connection->isTableExists(NewsProcessingTable::getTableName())) {
            NewsProcessingTable::getEntity()->createDbTable();
        }
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function doUninstall()
    {
        $this->includeEvents();
        $this->includeHelpers();
        $this->includeAgents();
        $this->includeTables();
        NewsEvents::unBind();
        HelperManager::getInstance()->Agent()->deleteAgentIfExists($this->MODULE_ID, NewsCheckProcessingStatus::getName());
        $connection = Application::getConnection();
        if ($connection->isTableExists(NewsProcessingTable::getTableName())) {
            $connection->dropTable(NewsProcessingTable::getTableName());
        }
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    protected function includeEvents(): void
    {
        require_once dirname(__DIR__) . '/lib/Events/AbstractEvents.php';
        require_once dirname(__DIR__) . '/lib/Events/NewsEvents.php';
    }

    protected function includeHelpers(): void
    {
        require_once dirname(__DIR__) . '/lib/HelperManager.php';
        require_once dirname(__DIR__) . '/lib/Helper.php';
        require_once dirname(__DIR__) . '/lib/Helpers/AgentHelper.php';
        require_once dirname(__DIR__) . '/lib/Helpers/IblockHelper.php';
    }

    protected function includeAgents(): void
    {
        require_once dirname(__DIR__) . '/lib/Agents/AbstractAgents.php';
        require_once dirname(__DIR__) . '/lib/Agents/NewsCheckProcessingStatus.php';
    }

    protected function includeTables(): void
    {
        require_once dirname(__DIR__) . '/lib/Orm/Tables/NewsProcessingTable.php';
    }
}