<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists('openurbantech_mapmyride')) {
    return;
}

class openurbantech_mapmyride extends CModule {

    public $MODULE_ID = "openurbantech.mapmyride";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = "Y";
    protected $models = [
    ];

    public function __construct() {
        $arModuleVersion = array();

        include(substr(__FILE__, 0, -10) . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage("MODULE_MAPMYRIDE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("MODULE_MAPMYRIDE_DESCRIPTION");

        $this->PARTNER_NAME = GetMessage("SPER_PARTNER");
        $this->PARTNER_URI = GetMessage("PARTNER_URI");
    }

    function InstallDB($arParams = array()) {
        global $DB, $APPLICATION;
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            $connection = \Bitrix\Main\Application::getConnection();
            foreach ($this->models as $modelName) {
                $model = sprintf('\\Openurbantech\\Mapmyride\\Model\\%sTable', $modelName);
                $tableName = $model::getTableName();
                if (!$connection->isTableExists($tableName)) {
                    $model::getEntity()->createDbTable();
                }
            }
        }
        return true;
    }

    function UnInstallDB($arParams = array()) {
        global $APPLICATION, $DB, $DOCUMENT_ROOT;
        if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            $connection = \Bitrix\Main\Application::getConnection();
            foreach ($this->models as $modelName) {
                $model = sprintf('\\Openurbantech\\Mapmyride\\Model\\%sTable', $modelName);
                $tableName = $model::getTableName();
                if ($connection->isTableExists($tableName)) {
                    $connection->dropTable($tableName);
                }
            }
        }
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    function InstallEvents() {
        return true;
    }

    function UnInstallEvents() {
        return true;
    }

    function InstallFiles($arParams = array()) {
        return true;
    }

    function UnInstallFiles() {
        return true;
    }

    function DoInstall() {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallFiles();
        $this->InstallDB();
        CModule::IncludeModule($this->MODULE_ID);
    }

    function DoUninstall() {
        global $DOCUMENT_ROOT, $APPLICATION, $step, $errors;
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }

}

?>