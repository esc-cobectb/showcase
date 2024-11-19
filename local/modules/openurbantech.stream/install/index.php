<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Openurbantech\Stream\SourceTable;
use Openurbantech\Stream\ElementTable;

Loc::loadMessages(__FILE__);

if (class_exists('openurbantech_stream')) {
    return;
}

class openurbantech_stream extends CModule
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
        $this->MODULE_ID = 'openurbantech.stream';
        $this->MODULE_VERSION = '0.1.0';
        $this->MODULE_VERSION_DATE = '2022-01-14 19:56:54';
        $this->MODULE_NAME = Loc::getMessage('OUT_STREAM_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OUT_STREAM_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage('OUT_PARTNER_NAME');
        $this->PARTNER_URI = "https://openurbantech.ru/";
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
    }

    public function doUninstall()
    {
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) 
        {
            $connection = Application::getInstance()->getConnection();
            $installTables = [
                new SourceTable(),
                new ElementTable(),
            ];
            $connection = Application::getInstance()->getConnection();
            foreach ($installTables as $tableManager) 
            {
                $tableName = $tableManager->getTableName();
                if (!$connection->isTableExists($tableName)) {
                    $tableManager->getEntity()->createDbTable();
                }
            }
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            $uninstallTables = [
                new SourceTable(),
                new ElementTable(),
            ];
            $connection = Application::getInstance()->getConnection();
            foreach ($uninstallTables as $tableManager) 
            {
                $tableName = $tableManager->getTableName();
                if ($connection->isTableExists($tableName)) {
                    $connection->dropTable($tableName);
                }
            }
        }
    }
}
