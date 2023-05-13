<?php

use Bitrix\Main\ModuleManager;

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
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function doUninstall()
    {
        $this->includeEvents();
        NewsEvents::unBind();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    protected function includeEvents(): void
    {
        require_once dirname(__DIR__) . '/lib/Events/AbstractEvents.php';
        require_once dirname(__DIR__) . '/lib/Events/NewsEvents.php';
    }
}